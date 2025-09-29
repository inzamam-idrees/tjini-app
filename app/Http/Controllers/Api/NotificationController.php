<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Services\FirebaseNotificationService;

class NotificationController extends Controller
{
    /**
     * Parent responds via mobile app. Notify viewers and dispatchers of the same school.
     * Expects authenticated parent (sanctum) and optional payload in request.
     */
    public function parentRespond(Request $request, FirebaseNotificationService $firebase)
    {
        $user = $request->user();
        if (!$user || !method_exists($user, 'hasRole') || !$user->hasRole('parent')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $schoolId = $user->school_id;
        if (!$schoolId) {
            return response()->json(['message' => 'Parent has no school assigned'], 422);
        }

        $payload = $request->input('payload', []);

        $receivers = User::role(['viewer', 'dispatcher'])->where('school_id', $schoolId)->get();
        $tokens = $receivers->pluck('device_token')->filter()->unique()->values()->all();
        if (empty($tokens)) {
            return response()->json(['message' => 'No viewers/dispatchers to notify'], 200);
        }

        $title = 'Parent Response';
        $body = "Parent {$user->fullName()} responded";

        $firebase->sendToTokens($tokens, $title, $body, array_merge(['parent_id' => (string)$user->id], is_array($payload) ? $payload : ['payload' => (string)$payload]));

        return response()->json(['message' => 'Notified viewers and dispatchers'], 200);
    }
}
