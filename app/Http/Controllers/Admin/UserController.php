<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\School;
class UserController extends Controller
{
    public function index($role)
    {
        $title = ucfirst($role) . 's';
        $roles = $role == 'staff' ? ['viewer', 'dispatcher'] : [$role];
        $users = User::role($roles)->get();
        return view('admin.users.index', compact('users', 'title', 'role'));
    }

    public function create($role)
    {
        $title = 'Create ' . ucfirst($role);
        $schools = School::all();
        return view('admin.users.create', compact('schools', 'title', 'role'));
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
            $parentValidated = $request->validate([
                'relation' => 'required|string|max:255',
                'child_name' => 'required|string|max:255',
                'is_primary' => 'nullable|boolean',
            ]);

            $childName = $parentValidated['child_name'];
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
            $isPrimary = filter_var($parentValidated['is_primary'] ?? false, FILTER_VALIDATE_BOOLEAN);
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
            $validated['relation'] = $parentValidated['relation'];
            $validated['child_name'] = $childName;
            $validated['is_primary'] = $isPrimary;

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

        // Fallback: treat other roles (if any) as simple role assignment
        $user = User::create($validated);
        $user->assignRole($role);
        return redirect()->route('admin.users.index', $role)->with('success', ucfirst($role) . ' created successfully');
    }

    public function edit($role, $id)
    {
        $title = 'Edit ' . ucfirst($role);
        $user = User::find($id);
        $schools = School::all();
        return view('admin.users.edit', compact('user', 'schools', 'title', 'role'));
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
            $parentValidated = $request->validate([
                'relation' => 'required|string|max:255',
                'child_name' => 'required|string|max:255',
                'is_primary' => 'nullable|boolean',
            ]);

            $childName = $parentValidated['child_name'];
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

            // If marking this parent as primary, unset primary for other parents of same child
            $isPrimary = filter_var($parentValidated['is_primary'] ?? false, FILTER_VALIDATE_BOOLEAN);
            if ($isPrimary) {
                $demoteQuery = User::role('parent')->where('child_name', $childName)->where('id', '!=', $user->id);
                if ($schoolId) {
                    $demoteQuery->where('school_id', $schoolId);
                } else {
                    $demoteQuery->whereNull('school_id');
                }
                $demoteQuery->update(['is_primary' => false]);
            }

            $validated['relation'] = $parentValidated['relation'];
            $validated['child_name'] = $childName;
            $validated['is_primary'] = $isPrimary;

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
