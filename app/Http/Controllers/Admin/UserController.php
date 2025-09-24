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
        dd($request->all());
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'school_id' => 'nullable|exists:schools,id',
        ]);

        if ($role == 'parent') {
            $validated['is_primary'] = $request->has('is_primary') ? $request->input('is_primary') : null;
            $validated['relation'] = $request->input('relation');
            $validated['child_name'] = $request->input('child_name');
        } else {
            $validated['school_id'] = $request->input('school_id');
        }
    }

    public function edit($role, $id)
    {
        $title = 'Edit ' . ucfirst($role);
        $user = User::find($id);
        $schools = School::all();
        return view('admin.users.edit', compact('user', 'schools', 'title', 'role'));
    }

    public function update(Request $request, $role, $id)
    {
        dd($request->all());
    }

    public function destroy($role, $id)
    {
        $user = User::find($id);
        $user->delete();
        return redirect()->route('admin.users.index', $role)->with('success', 'User deleted successfully');
    }   
}
