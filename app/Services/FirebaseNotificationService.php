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
            // Resolve configured path. The config usually provides an absolute path
            // (storage_path(...)) but if a relative path was supplied, try common bases.
            $serviceAccountPath = $credentials;

            // If the configured path is not absolute, attempt to resolve it.
            if (!file_exists($serviceAccountPath)) {
                $tryPaths = [base_path($credentials), storage_path($credentials)];
                foreach ($tryPaths as $p) {
                    if (file_exists($p)) {
                        $serviceAccountPath = $p;
                        break;
                    }
                }
            }

            if (!file_exists($serviceAccountPath)) {
                // Log a helpful error; do not throw so app can continue without FCM
                logger()->error("Firebase service account file not found at configured path: {$credentials}");
                // Avoid calling createMessaging() which may try to read the configured path
                // and throw. Mark messaging as unavailable and return early.
                $this->messaging = null;
                return;
            } else {
                $factory = $factory->withServiceAccount($serviceAccountPath);
            }
        }

        try {
            $this->messaging = $factory->createMessaging();
        } catch (\Throwable $e) {
            // If messaging cannot be created (bad credentials, missing file), log and keep null
            logger()->error('Failed to initialize Firebase messaging: ' . $e->getMessage());
            $this->messaging = null;
        }
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

        // If messaging isn't initialized, skip sending
        if (empty($this->messaging)) {
            logger()->warning('Firebase messaging not initialized - skipping send to token');
            return;
        }

        // Ensure data values are strings (FCM expects string values in data payload)
        $sanitizedData = [];
        foreach ($data as $k => $v) {
            if (is_scalar($v) || $v === null) {
                $sanitizedData[$k] = (string) $v;
            } else {
                // Non-scalar data: JSON-encode to preserve structure
                $sanitizedData[$k] = json_encode($v);
            }
        }

        $message = CloudMessage::withTarget('token', $token)
            ->withNotification(['title' => ucwords(str_replace('-', ' ', $title)), 'body' => $body])
            ->withData($sanitizedData);

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
