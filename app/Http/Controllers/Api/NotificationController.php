<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Notification;
use App\Services\FirebaseNotificationService;
use Illuminate\Http\Response;

class NotificationController extends Controller
{
    /**
     * List notifications for a school (dispatcher/viewer access)
     */
    public function listNotifications(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }

        // If the requesting user is a parent, return only notifications they created (their own)
        if ($user->hasRole('parent')) {
            $notifications = Notification::with('fromUser')
                ->where('from_user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json($notifications);
        }

        // Only dispatcher/viewer may request all school notifications
        if (!$user->hasAnyRole(['dispatcher', 'viewer'])) {
            return response()->json(['message' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }

        if (!$user->school_id) {
            return response()->json(['message' => 'No school assigned'], Response::HTTP_FORBIDDEN);
        }

        $notifications = Notification::with('fromUser')
            ->where('school_id', $user->school_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($notifications);
    }
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
        $fromId = $payload['fromUserId'] ?? null;
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

        // Parent sending: notify all dispatchers and viewers of their school
        if ($fromUser->hasRole('parent')) {
            $receivers = User::role(['viewer', 'dispatcher'])->where('school_id', $schoolId)->get();
            $tokens = $receivers->pluck('device_token')->filter()->unique()->values()->all();
            if (empty($tokens)) {
                return response()->json(['message' => 'No viewers/dispatchers to notify'], 200);
            }
            $firebase->sendToTokens($tokens, $payload['type'] ?? 'Parent', $payload['message'] ?? '', [
                'fromUserId' => $fromId,
                'type' => $payload['type'] ?? '',
                'message' => $payload['message'] ?? '',
            ]);
            // Save notification in database
            $notification = Notification::create([
                'from_user_id' => $fromId,
                'type' => $payload['type'] ?? '',
                'message' => $payload['message'] ?? '',
                'value' => $payload['value'] ?? '',
                'school_id' => $schoolId,
                'sender_role' => $fromUser->getRoleNames()->first() ?? 'unknown'
            ]);
            return response()->json(['message' => 'Notified viewers and dispatchers'], 200);
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
                'fromUserId' => $fromId,
                'type' => $payload['type'] ?? '',
                'message' => $payload['message'] ?? '',
            ]);
            // Save notification in database
            $notification = Notification::create([
                'from_user_id' => $fromId,
                'type' => $payload['type'] ?? '',
                'message' => $payload['message'] ?? '',
                'value' => $payload['value'] ?? '',
                'school_id' => $schoolId,
                'sender_role' => $fromUser->getRoleNames()->first() ?? 'unknown'
            ]);
            return response()->json(['message' => 'Notified parents'], 200);
        }

        return response()->json(['message' => 'Role not allowed for notification'], 403);
    }
}
