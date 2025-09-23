<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\School;

class SchoolController extends Controller
{
    /**
     * Show the school list.
     */
    public function index()
    {
        $schools = School::all();
        return view('admin.schools.index', compact('schools'));
    }

    /**
     * Handle a creat request to the application.
     */
    public function create()
    {
        return view('admin.schools.create');
    }

    /**
     * Handle a store request to the application.
     */
    public function store(Request $request)
    {
        return view('admin.schools.store');
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
    public function edit(School $school)
    {
        return view('admin.schools.edit', compact('school'));
    }

    /**
     * Handle a update request to the application.
     */
    public function update(Request $request, School $school)
    {
        return view('admin.schools.update', compact('school'));
    }

    /**
     * Handle a destroy request to the application.
     */
    public function destroy(School $school)
    {
        return view('admin.schools.destroy', compact('school'));
    }
}
