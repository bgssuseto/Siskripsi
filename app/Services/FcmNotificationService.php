<?php

namespace App\Services;

use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\Messaging\InvalidArgument;
use Kreait\Firebase\Exception\Messaging\NotFound;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;

/**
 * Sends push notifications to the Android app via Firebase Cloud Messaging.
 *
 * Stays inert until FIREBASE_CREDENTIALS is configured (see .env.example) —
 * every public method silently no-ops with a log line until then, so callers
 * never need to check whether FCM is set up before using it.
 */
class FcmNotificationService
{
    private ?Messaging $messaging = null;

    private bool $resolvedMessaging = false;

    public function sendToUser(User $user, string $title, string $body, array $data = []): void
    {
        $messaging = $this->messaging();
        if (!$messaging) {
            return;
        }

        $tokens = $user->deviceTokens()->pluck('token');
        if ($tokens->isEmpty()) {
            return;
        }

        foreach ($tokens as $token) {
            $this->sendToToken($messaging, $token, $title, $body, $data);
        }
    }

    private function sendToToken(Messaging $messaging, string $token, string $title, string $body, array $data): void
    {
        try {
            $message = CloudMessage::new()
                ->withToken($token)
                ->withNotification(FirebaseNotification::create($title, $body))
                ->withData($data);

            $messaging->send($message);
        } catch (NotFound|InvalidArgument $e) {
            // Token is dead (app uninstalled, FCM rotated it, etc.) — clean it up
            // so we stop wasting a request on it next time.
            DeviceToken::where('token', $token)->delete();
        } catch (\Throwable $e) {
            Log::warning('Gagal mengirim push notification FCM: ' . $e->getMessage());
        }
    }

    private function messaging(): ?Messaging
    {
        if ($this->resolvedMessaging) {
            return $this->messaging;
        }

        $this->resolvedMessaging = true;

        $credentialsPath = config('services.fcm.credentials');
        if (!$credentialsPath) {
            Log::info('FCM belum dikonfigurasi (FIREBASE_CREDENTIALS belum diisi di .env) — notifikasi dilewati.');
            return null;
        }

        $fullPath = str_starts_with($credentialsPath, '/') ? $credentialsPath : base_path($credentialsPath);
        if (!is_file($fullPath)) {
            Log::warning("FCM: file service account tidak ditemukan di {$fullPath} — notifikasi dilewati.");
            return null;
        }

        $this->messaging = (new Factory)->withServiceAccount($fullPath)->createMessaging();

        return $this->messaging;
    }
}
