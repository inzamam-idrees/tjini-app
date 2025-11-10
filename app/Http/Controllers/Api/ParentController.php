<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Http\Response;

class ParentController extends Controller
{
    /**
     * Return related parents for a given parent user id.
     * Payload: { userId } or { user_id }
     * Response: { primary: {...} | null, secondaries: [...] }
     */
    public function relatedParents(Request $request)
    {
        $targetId = $request->input('userId') ?? $request->input('user_id');
        if (!$targetId) {
            return response()->json(['message' => 'Missing userId'], 422);
        }

        $target = User::find($targetId);
        if (!$target) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if (!method_exists($target, 'hasRole') || !$target->hasRole('parent')) {
            return response()->json(['message' => 'User is not a parent'], 422);
        }

        $schoolId = $target->school_id;

        // If target is primary, return them as primary and list secondaries
        if (filter_var($target->is_primary, FILTER_VALIDATE_BOOLEAN)) {
            $secondaries = User::role('parent')
                ->where('primary_parent_id', $target->id)
                ->when($schoolId, function ($q) use ($schoolId) { $q->where('school_id', $schoolId); })
                ->get();

            return response()->json([
                'primary' => $target,
                'secondaries' => $secondaries,
            ], 200);
        }

        // If target is not primary, find their primary parent and siblings (other secondaries)
        $primary = null;
        if ($target->primary_parent_id) {
            $primary = User::find($target->primary_parent_id);
        }

        $secondaries = collect();
        if ($primary) {
            $secondaries = User::role('parent')
                ->where('primary_parent_id', $primary->id)
                ->when($schoolId, function ($q) use ($schoolId) { $q->where('school_id', $schoolId); })
                ->get();
        }

        return response()->json([
            'primary' => $primary,
            'secondaries' => $secondaries,
        ], 200);
    }
}
