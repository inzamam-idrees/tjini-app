<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Services\FirebaseNotificationService;

class NotificationController extends Controller
{
    /**
     * Unified notification endpoint for parent and dispatcher actions.
     *
     * Parent:
     *   - Sends to all dispatchers of their school.
     * Dispatcher:
     *   - Sends to all parents (if allParents true) or only primary parent (if allParents false) of their school.
     *
     * Payload example:
     *   {type, message, from, allParents?}
     */
    public function notifyUsers(Request $request, FirebaseNotificationService $firebase)
    {
        $payload = $request->all();
        $fromId = $payload['from'] ?? null;
        if (!$fromId) {
            return response()->json(['message' => 'Missing from userId'], 422);
        }

        $fromUser = User::find($fromId);
        if (!$fromUser || !method_exists($fromUser, 'hasRole')) {
            return response()->json(['message' => 'Invalid from user'], 404);
        }

        $schoolId = $fromUser->school_id;
        if (!$schoolId) {
            return response()->json(['message' => 'User has no school assigned'], 422);
        }

        // Parent sending: notify all dispatchers of their school
        if ($fromUser->hasRole('parent')) {
            $receivers = User::role('dispatcher')->where('school_id', $schoolId)->get();
            $tokens = $receivers->pluck('device_token')->filter()->unique()->values()->all();
            if (empty($tokens)) {
                return response()->json(['message' => 'No dispatchers to notify'], 200);
            }
            $firebase->sendToTokens($tokens, $payload['type'] ?? 'Parent', $payload['message'] ?? '', [
                'from' => $fromId,
                'type' => $payload['type'] ?? '',
                'message' => $payload['message'] ?? '',
            ]);
            return response()->json(['message' => 'Notified dispatchers'], 200);
        }

        // Dispatcher sending: notify parents (all or primary)
        if ($fromUser->hasRole('dispatcher') || $fromUser->hasRole('viewer')) {
            $allParents = isset($payload['allParents']) ? filter_var($payload['allParents'], FILTER_VALIDATE_BOOLEAN) : false;
            if ($allParents) {
                $parents = User::role('parent')->where('school_id', $schoolId)->get();
            } else {
                $parents = User::role('parent')->where('school_id', $schoolId)->where('is_primary', true)->get();
            }
            $tokens = $parents->pluck('device_token')->filter()->unique()->values()->all();
            if (empty($tokens)) {
                return response()->json(['message' => 'No parents to notify'], 200);
            }
            $firebase->sendToTokens($tokens, $payload['type'] ?? 'Dispatcher', $payload['message'] ?? '', [
                'from' => $fromId,
                'type' => $payload['type'] ?? '',
                'message' => $payload['message'] ?? '',
            ]);
            return response()->json(['message' => 'Notified parents'], 200);
        }

        return response()->json(['message' => 'Role not allowed for notification'], 403);
    }
}
