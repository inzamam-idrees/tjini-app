<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\School;
use Illuminate\Support\Facades\Auth;
class UserController extends Controller
{
    public function index($role)
    {
        $title = ucfirst($role) . 's';
        $roles = $role == 'staff' ? ['viewer', 'dispatcher'] : [$role];
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        if ($user && method_exists($user, 'hasRole') && $user->hasRole('super_admin')) {
            $users = User::role($roles)->get();
        } elseif ($user && method_exists($user, 'hasRole') && $user->hasRole('admin')) {
            $users = User::role($roles)->where('school_id', $user->school_id)->get();
        } else {
            $users = collect();
        }
        return view('admin.users.index', compact('users', 'title', 'role'));
    }

    public function create($role)
    {
        $title = 'Create ' . ucfirst($role);
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        if ($user && method_exists($user, 'hasRole') && $user->hasRole('super_admin')) {
            $schools = School::all();
        } elseif ($user && method_exists($user, 'hasRole') && $user->hasRole('admin')) {
            $schools = School::where('id', $user->school_id)->get();
        } else {
            $schools = collect();
        }
        // prepare primary parent users (used to populate the primary-parent select)
        $primaryParents = collect();
        if ($role == 'parent') {
            if ($user && method_exists($user, 'hasRole') && $user->hasRole('super_admin')) {
                $primaryParents = User::role('parent')->where('is_primary', true)->get();
            } elseif ($user && method_exists($user, 'hasRole') && $user->hasRole('admin')) {
                $primaryParents = User::role('parent')->where('is_primary', true)->where('school_id', $user->school_id)->get();
            }
        }

        return view('admin.users.create', compact('schools', 'title', 'role', 'primaryParents'));
    }

    public function store(Request $request, $role)
    {
        /**
         * Store a newly created user for admins.
         * Behavior:
         * - When $role == 'parent':
         *   - Validates parent-specific fields: relation, child_name, is_primary
         *   - Limits parents per child to max 3 (optionally scoped by school_id)
         *   - If new parent is marked primary, demotes other parents for that child
         * - When $role == 'staff':
         *   - Expects 'staff_role' to be one of ['dispatcher','viewer'] and a required school_id
         *   - Assigns the specified staff role to the created user
         * - Otherwise assigns the provided $role to the created user
        */
        // Validate common fields
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'school_id' => 'nullable|exists:schools,id',
        ]);

        if ($role == 'parent') {
            // Additional parent-specific validation
            // If is_primary is unchecked (false) then primary_parent_id is required.
            $isPrimary = $request->boolean('is_primary');
            $parentRules = [
                'relation' => 'nullable|string|max:255',
                'child_name' => 'nullable|string|max:255',
                'is_primary' => 'nullable|boolean',
            ];
            if ($isPrimary) {
                $parentRules['primary_parent_id'] = 'nullable|exists:users,id';
            } else {
                $parentRules['primary_parent_id'] = 'required|exists:users,id';
            }

            $parentValidated = $request->validate($parentRules);

            $childName = $parentValidated['child_name'] ?? null;
            $schoolId = $request->input('school_id');

            // Count existing parents for this child (limit to same school if provided)
            $existingParentsQuery = User::role('parent')->where('child_name', $childName);
            if ($schoolId) {
                $existingParentsQuery->where('school_id', $schoolId);
            } else {
                $existingParentsQuery->whereNull('school_id');
            }

            $existingCount = $existingParentsQuery->count();

            if ($existingCount >= 3) {
                return redirect()->back()->withInput()->withErrors(['child_name' => 'Maximum of 3 parents allowed for this child']);
            }

            // If marking this parent as primary, unset primary for other parents of same child
            // Normalize is_primary to boolean
            $isPrimary = filter_var($parentValidated['is_primary'] ?? $isPrimary, FILTER_VALIDATE_BOOLEAN);
            if ($isPrimary) {
                $demoteQuery = User::role('parent')->where('child_name', $childName);
                if ($schoolId) {
                    $demoteQuery->where('school_id', $schoolId);
                } else {
                    $demoteQuery->whereNull('school_id');
                }
                $demoteQuery->update(['is_primary' => false]);
            }

            // Merge parent-specific fields into validated data for create
            $validated['relation'] = $parentValidated['relation'] ?? null;
            $validated['child_name'] = $childName;
            $validated['is_primary'] = $isPrimary;
            // include primary_parent_id when provided
            if (array_key_exists('primary_parent_id', $parentValidated)) {
                $validated['primary_parent_id'] = $parentValidated['primary_parent_id'];
            }

            // Create user and assign parent role
            $user = User::create($validated);
            $user->assignRole('parent');

            return redirect()->route('admin.users.index', 'parent')->with('success', 'Parent created successfully');
        }

        if ($role == 'staff') {
            // Staff can be either dispatcher or viewer
            $staffValidated = $request->validate([
                'staff_role' => 'required|string|in:dispatcher,viewer',
                'school_id' => 'required|exists:schools,id',
            ]);

            $validated['school_id'] = $staffValidated['school_id'];

            $user = User::create($validated);
            // assign the selected staff role
            $user->assignRole($staffValidated['staff_role']);

            return redirect()->route('admin.users.index', 'staff')->with('success', 'Staff created successfully');
        }

        if ($role == 'admin') {
            // only super admins may create school admins
            /** @var \App\Models\User|null $authUser */
            $authUser = Auth::user();
            if (!($authUser && method_exists($authUser, 'hasRole') && $authUser->hasRole('super_admin'))) {
                abort(403);
            }
            // Only super_admin should be able to create school admins. The create view already limits schools shown,
            // but enforce school_id required here and ensure only one admin per school.
            $adminValidated = $request->validate([
                'school_id' => 'required|exists:schools,id',
            ]);

            $schoolId = $adminValidated['school_id'];

            // Ensure there isn't already an admin for this school
            $existingAdmin = User::role('admin')->where('school_id', $schoolId)->first();
            if ($existingAdmin) {
                return redirect()->back()->withInput()->withErrors(['school_id' => 'This school already has an admin.']);
            }

            $validated['school_id'] = $schoolId;

            $user = User::create($validated);
            $user->assignRole('admin');

            return redirect()->route('admin.users.index', 'admin')->with('success', 'School admin created successfully');
        }

        // Fallback: treat other roles (if any) as simple role assignment
        $user = User::create($validated);
        $user->assignRole($role);
        return redirect()->route('admin.users.index', $role)->with('success', ucfirst($role) . ' created successfully');
    }

    public function edit($role, $id)
    {
        $title = 'Edit ' . ucfirst($role);
        $user = User::find($id);
        /** @var \App\Models\User|null $authUser */
        $authUser = Auth::user();
        if ($authUser && method_exists($authUser, 'hasRole') && $authUser->hasRole('super_admin')) {
            $schools = School::all();
        } elseif ($authUser && method_exists($authUser, 'hasRole') && $authUser->hasRole('admin')) {
            $schools = School::where('id', $authUser->school_id)->get();
        } else {
            $schools = collect();
        }
        // prepare primary parent users for the edit view as well
        $primaryParents = collect();
        if ($role == 'parent') {
            if ($authUser && method_exists($authUser, 'hasRole') && $authUser->hasRole('super_admin')) {
                $primaryParents = User::role('parent')->where('is_primary', true)->get();
            } elseif ($authUser && method_exists($authUser, 'hasRole') && $authUser->hasRole('admin')) {
                $primaryParents = User::role('parent')->where('is_primary', true)->where('school_id', $authUser->school_id)->get();
            }
        }

        return view('admin.users.create', compact('user', 'schools', 'title', 'role', 'primaryParents'));
    }

    /**
     * Update an existing user for admins.
     * Mirrors store logic for parent/staff roles.
    */
    public function update(Request $request, $role, $id)
    {
        $user = User::findOrFail($id);

        // Validate common fields
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'school_id' => 'nullable|exists:schools,id',
        ]);

        if ($request->filled('password')) {
            $request->validate([
                'password' => 'string|min:8|confirmed',
            ]);
            $validated['password'] = $request->input('password');
        }

        if ($role == 'parent') {
            // Conditional validation: primary_parent_id required when is_primary is false
            $isPrimary = $request->boolean('is_primary');
            $parentRules = [
                'relation' => 'nullable|string|max:255',
                'child_name' => 'nullable|string|max:255',
                'is_primary' => 'nullable|boolean',
            ];
            if ($isPrimary) {
                $parentRules['primary_parent_id'] = 'nullable|exists:users,id';
            } else {
                $parentRules['primary_parent_id'] = 'required|exists:users,id';
            }

            $parentValidated = $request->validate($parentRules);

            $childName = $parentValidated['child_name'] ?? null;
            $schoolId = $request->input('school_id');

            // Count existing parents for this child (excluding this user)
            $existingParentsQuery = User::role('parent')->where('child_name', $childName)->where('id', '!=', $user->id);
            if ($schoolId) {
                $existingParentsQuery->where('school_id', $schoolId);
            } else {
                $existingParentsQuery->whereNull('school_id');
            }
            $existingCount = $existingParentsQuery->count();
            if ($existingCount >= 3) {
                return redirect()->back()->withInput()->withErrors(['child_name' => 'Maximum of 3 parents allowed for this child']);
            }

            // Normalize is_primary to boolean (prefer validated value if present)
            $isPrimary = filter_var($parentValidated['is_primary'] ?? $isPrimary, FILTER_VALIDATE_BOOLEAN);
            if ($isPrimary) {
                $demoteQuery = User::role('parent')->where('child_name', $childName)->where('id', '!=', $user->id);
                if ($schoolId) {
                    $demoteQuery->where('school_id', $schoolId);
                } else {
                    $demoteQuery->whereNull('school_id');
                }
                $demoteQuery->update(['is_primary' => false]);
            }

            $validated['relation'] = $parentValidated['relation'] ?? null;
            $validated['child_name'] = $childName;
            $validated['is_primary'] = $isPrimary;
            if (array_key_exists('primary_parent_id', $parentValidated)) {
                $validated['primary_parent_id'] = $parentValidated['primary_parent_id'];
            }

            $user->update($validated);
            $user->syncRoles(['parent']);

            return redirect()->route('admin.users.index', 'parent')->with('success', 'Parent updated successfully');
        }

        if ($role == 'staff') {
            $staffValidated = $request->validate([
                'staff_role' => 'required|string|in:dispatcher,viewer',
                'school_id' => 'required|exists:schools,id',
            ]);
            $validated['school_id'] = $staffValidated['school_id'];
            $user->update($validated);
            $user->syncRoles([$staffValidated['staff_role']]);
            return redirect()->route('admin.users.index', 'staff')->with('success', 'Staff updated successfully');
        }

        if ($role == 'admin') {
            // only super admins may update school admins
            /** @var \App\Models\User|null $authUser */
            $authUser = Auth::user();
            if (!($authUser && method_exists($authUser, 'hasRole') && $authUser->hasRole('super_admin'))) {
                abort(403);
            }
            $adminValidated = $request->validate([
                'school_id' => 'required|exists:schools,id',
            ]);
            $schoolId = $adminValidated['school_id'];

            // Ensure there isn't already another admin for this school
            $existingAdmin = User::role('admin')->where('school_id', $schoolId)->where('id', '!=', $user->id)->first();
            if ($existingAdmin) {
                return redirect()->back()->withInput()->withErrors(['school_id' => 'This school already has an admin.']);
            }

            $validated['school_id'] = $schoolId;
            $user->update($validated);
            $user->syncRoles(['admin']);
            return redirect()->route('admin.users.index', 'admin')->with('success', 'School admin updated successfully');
        }

        // Fallback: treat other roles (if any) as simple role assignment
        $user->update($validated);
        $user->syncRoles([$role]);
        return redirect()->route('admin.users.index', $role)->with('success', ucfirst($role) . ' updated successfully');
    }

    public function destroy($role, $id)
    {
        $user = User::find($id);
        $user->delete();
        return redirect()->route('admin.users.index', $role)->with('success', 'User deleted successfully');
    }   
}
