<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\School;
use Illuminate\Support\Facades\Auth;

class SchoolController extends Controller
{
    /**
     * Show the school list.
     */
    public function index()
    {
        $title = 'School List';
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        if ($user && method_exists($user, 'hasRole') && $user->hasRole('super_admin')) {
            $schools = School::all();
        } elseif ($user && method_exists($user, 'hasRole') && $user->hasRole('admin')) {
            $schools = School::where('id', $user->school_id)->get();
        } else {
            $schools = collect();
        }
        return view('admin.schools.index', compact('schools', 'title'));
    }

    /**
     * Handle a creat request to the application.
     */
    public function create()
    {
        $title = 'Create School';
        return view('admin.schools.create', compact('title'));
    }

    /**
     * Handle a store request to the application.
     */
    public function store(Request $request)
    {
        // dd($request->all());
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);

        if (!$validated) {
            return redirect()->back()->withErrors($validated)->withInput();
        }

        School::create($validated);
        return redirect()->route('admin.schools.index')->with('success', 'School created successfully.');
    }

    /**
     * Handle a show request to the application.
     */
    public function show(School $school)
    {
        return view('admin.schools.show', compact('school'));
    }

    /**
     * Handle a edit request to the application.
     */
    public function edit($id)
    {
        $title = 'Edit School';
        $school = School::findOrFail($id);
        return view('admin.schools.create', compact('school', 'title'));
    }

    /**
     * Handle a update request to the application.
     */
    public function update(Request $request, $id)
    {
        $school = School::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);
        if (!$validated) {
            return redirect()->back()->withErrors($validated)->withInput();
        }

        $school->update($validated);
        return redirect()->route('admin.schools.index')->with('success', 'School updated successfully.');
    }

    /**
     * Handle a destroy request to the application.
     */
    public function destroy($id)
    {
        $school = School::findOrFail($id);
        if (!$school) {
            return redirect()->route('admin.schools.index')->with('error', 'School not found.');
        }
        
        $school->delete();
        return redirect()->route('admin.schools.index')->with('success', 'School deleted successfully.');
    }
}
