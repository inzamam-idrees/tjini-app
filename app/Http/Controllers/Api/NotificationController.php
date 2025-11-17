<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Notification;
use App\Models\Dispatchee;
use App\Services\FirebaseNotificationService;
use Illuminate\Http\Response;

class NotificationController extends Controller
{
    /**
     * List notifications for a secondary parent
     */
    public function listSecondaryParentNotifications(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }

        if (!$user->hasRole('parent')) {
            return response()->json(['message' => 'User is not a parent'], Response::HTTP_FORBIDDEN);
        }

        if ($user->is_primary) {
            return response()->json(['message' => 'User is not a secondary parent'], Response::HTTP_FORBIDDEN);
        }

        // If the requesting user is a parent, return only notifications their transfers
        $notifications = Notification::with(['fromUser', 'toUser'])
            ->where(function ($q) use ($user) {
                $q->where('to_user_id', $user->id);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($notifications);
    }

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
            $notifications = Notification::with(['fromUser', 'toUser'])
                ->where(function ($q) use ($user) {
                    $q->where('from_user_id', $user->id)
                    ->orWhere(function ($q2) use ($user) {
                        $q2->whereIn('type', ['school-start', 'school-end'])
                            ->where('to_user_id', $user->id);
                    });
                })
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

        $notifications = Notification::with(['fromUser', 'toUser'])
            // ->where('school_id', $user->school_id)
            ->where(function ($q) use ($user) {
                $q->where('school_id', $user->school_id)
                ->orWhere(function ($q2) use ($user) {
                    $q2->whereIn('type', ['school-start', 'school-end'])
                        ->where('to_user_id', $user->id);
                });
            })
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

        // If a specific recipient is provided, send only to that user
        if (isset($payload['toUserId'])) {
            $toId = $payload['toUserId'];
            $receiver = User::find($toId);
            if (!$receiver) {
                return response()->json(['message' => 'Recipient not found'], 404);
            }
            // Prevent cross-school notifications
            if ($receiver->school_id != $schoolId) {
                return response()->json(['message' => 'Recipient is not in the same school'], 422);
            }

            $tokens = $receiver->device_token ? [$receiver->device_token] : [];
            if (empty($tokens)) {
                return response()->json(['message' => 'Recipient has no device token'], 200);
            }

            if ($payload['type'] == "arrival-time") {
                Dispatchee::create([
                    'user_id' => $fromId,
                    'type' => $payload['type'] ?? 'arrival-time',
                    'time' => intval($payload['value'] ?? 0),
                    'school_id' => $schoolId,
                ]);
            }

            $firebase->sendToTokens($tokens, $payload['type'] ?? 'Notification', $payload['message'] ?? '', [
                'fromUserId' => $fromId,
                'type' => $payload['type'] ?? '',
                'message' => $payload['message'] ?? '',
            ]);

            // Save notification in database for the single recipient
            Notification::create([
                'from_user_id' => $fromId,
                'type' => $payload['type'] ?? '',
                'message' => $payload['message'] ?? '',
                'value' => $payload['value'] ?? '',
                'school_id' => $schoolId,
                'to_user_id' => $receiver->id,
                'sender_role' => $fromUser->getRoleNames()->first() ?? 'unknown'
            ]);

            return response()->json(['message' => 'Notified transfer parent'], 200);
        }

        // Parent sending: notify all dispatchers and viewers of their school
        if ($fromUser->hasRole('parent')) {
            $receivers = User::role(['viewer', 'dispatcher'])->where('school_id', $schoolId)->get();
            $tokens = $receivers->pluck('device_token')->filter()->unique()->values()->all();
            if (empty($tokens)) {
                return response()->json(['message' => 'No viewers/dispatchers to notify'], 200);
            }

            if ($payload['type'] == "arrival-time") {
                Dispatchee::create([
                    'user_id' => $fromId,
                    'type' => $payload['type'] ?? 'arrival-time',
                    'time' => intval($payload['value'] ?? 0),
                    'school_id' => $schoolId,
                ]);
            }

            $firebase->sendToTokens($tokens, $payload['type'] ?? 'Parent', $payload['message'] ?? '', [
                'fromUserId' => $fromId,
                'type' => $payload['type'] ?? '',
                'message' => $payload['message'] ?? '',
            ]);
            $receiverId = User::role(['viewer', 'dispatcher'])->where('school_id', $schoolId)->first()->id ?? null;
            if ($receiverId) {
                // Save notification in database
                Notification::create([
                    'from_user_id' => $fromId,
                    'type' => $payload['type'] ?? '',
                    'message' => $payload['message'] ?? '',
                    'value' => $payload['value'] ?? '',
                    'school_id' => $schoolId,
                    'to_user_id' => $receiverId,
                    'sender_role' => $fromUser->getRoleNames()->first() ?? 'unknown'
                ]);
            }
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

            if ($payload['type'] == "arrival-time") {
                Dispatchee::create([
                    'user_id' => $fromId,
                    'type' => $payload['type'] ?? 'arrival-time',
                    'time' => intval($payload['value'] ?? 0),
                    'school_id' => $schoolId,
                ]);
            }

            $firebase->sendToTokens($tokens, $payload['type'] ?? 'Dispatcher', $payload['message'] ?? '', [
                'fromUserId' => $fromId,
                'type' => $payload['type'] ?? '',
                'message' => $payload['message'] ?? '',
            ]);

            foreach ($parents as $parent) {
                // Save notification in database
                Notification::create([
                    'from_user_id' => $fromId,
                    'type' => $payload['type'] ?? '',
                    'message' => $payload['message'] ?? '',
                    'value' => $payload['value'] ?? '',
                    'school_id' => $schoolId,
                    'to_user_id' => $parent->id,
                    'sender_role' => $fromUser->getRoleNames()->first() ?? 'unknown'
                ]);
            }
            return response()->json(['message' => 'Notified primary parents'], 200);
        }

        return response()->json(['message' => 'Role not allowed for notification'], 403);
    }

    /**
     * Update dispatchee status and optionally sum time value
     *
     * Payload example:
     *   {status, value?}
     *
     * If value is provided, it will be summed with the existing time value
     */
    public function updateDispatchee(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }

        $payload = $request->all();
        $parent_id = $payload['parent_id'] ?? null;
        $status = $payload['status'] ?? null;
        $value = $payload['value'] ?? null;

        // Determine which user's dispatchee record to update
        if ($user->hasRole('parent')) {
            $targetUserId = $user->id;
        } elseif ($user->hasAnyRole(['dispatcher', 'viewer'])) {
            $targetUserId = $parent_id;
            if (!$targetUserId) {
                return response()->json(['message' => 'parent_id is required for dispatcher/viewer'], 422);
            }
            $parent = User::find($targetUserId);
            if (!$parent || !$parent->hasRole('parent')) {
                return response()->json(['message' => 'Parent not found'], 404);
            }
            // Optional: prevent cross-school updates
            if ($user->school_id && $parent->school_id && $user->school_id !== $parent->school_id) {
                return response()->json(['message' => 'Parent is not in the same school'], 422);
            }
        } else {
            return response()->json(['message' => 'Role not allowed to update dispatchee'], Response::HTTP_FORBIDDEN);
        }

        // Find the dispatchee record matching the id and target user
        $dispatchee = Dispatchee::where('user_id', $targetUserId)->first();

        if (!$dispatchee) {
            return response()->json(['message' => 'Dispatchee not found'], 404);
        }

        if ($status !== null) {
            $dispatchee->status = $status;
        }

        if ($value !== null) {
            $additionalTime = intval($value);
            $currentTimeValue = is_numeric($dispatchee->time) ? intval($dispatchee->time) : 0;
            $dispatchee->time = $currentTimeValue + $additionalTime;
        }

        $dispatchee->save();

        return response()->json([
            'message' => 'Dispatchee updated successfully',
            'data' => $dispatchee
        ], 200);
    }

    /**
     * List dispatchee records for the authenticated user
     */
    public function listDispatchees(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }
        $dispatchees = Dispatchee::with('user')
            ->where('school_id', $user->school_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($dispatchees);
    }
}