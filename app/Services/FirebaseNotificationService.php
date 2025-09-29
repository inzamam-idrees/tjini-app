<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;

class FirebaseNotificationService
{
    protected $messaging;

    public function __construct()
    {
        $credentials = config('firebase.credentials');
        $factory = new Factory();
        if ($credentials) {
            $factory = $factory->withServiceAccount(base_path($credentials));
        }

        $this->messaging = $factory->createMessaging();
    }

    /**
     * Send notification to a single device token
     * @param string $token
     * @param string $title
     * @param string $body
     * @param array $data
     */
    public function sendToToken(string $token, string $title, string $body, array $data = []) : void
    {
        if (empty($token)) {
            return;
        }

        $message = CloudMessage::withTarget('token', $token)
            ->withNotification(['title' => $title, 'body' => $body])
            ->withData($data);

        try {
            $this->messaging->send($message);
        } catch (\Throwable $e) {
            // Log silently; do not break the flow
            logger()->error('Firebase send error: ' . $e->getMessage());
        }
    }

    /**
     * Send to multiple tokens
     */
    public function sendToTokens(array $tokens, string $title, string $body, array $data = []) : void
    {
        foreach ($tokens as $token) {
            $this->sendToToken($token, $title, $body, $data);
        }
    }
}
