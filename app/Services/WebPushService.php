<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\User;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushService
{
    private WebPush $webPush;

    public function __construct()
    {
        $publicKey = config('services.vapid.public_key');
        $privateKey = config('services.vapid.private_key');

        if (! $publicKey || ! $privateKey) {
            return;
        }

        $this->webPush = new WebPush([
            'VAPID' => [
                'subject' => config('services.vapid.subject', 'mailto:admin@sabere.com'),
                'publicKey' => $publicKey,
                'privateKey' => $privateKey,
            ],
        ]);
    }

    public function sendToUser(User $user, string $type, string $title, string $body, string $url = '/'): bool
    {
        $preferences = $user->notificationPreference;

        if ($preferences && ! $preferences->acceptsPush($type)) {
            return false;
        }

        if (! isset($this->webPush)) {
            return false;
        }

        $subscriptions = PushSubscription::where('user_id', $user->id)->get();

        if ($subscriptions->isEmpty()) {
            return false;
        }

        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'url' => $url,
            'icon' => '/icon-192x192.png',
            'badge' => '/icon-72x72.png',
            'type' => $type,
        ]);

        foreach ($subscriptions as $subscription) {
            $this->webPush->queueNotification(
                Subscription::create([
                    'endpoint' => $subscription->endpoint,
                    'publicKey' => $subscription->p256dh_key,
                    'authToken' => $subscription->auth_token,
                ]),
                $payload
            );
        }

        $this->flushAndHandleResponses();

        return true;
    }

    private function flushAndHandleResponses(): void
    {
        foreach ($this->webPush->flush() as $report) {
            if (! $report || ! $report->isSuccess()) {
                $endpoint = $report->getEndpoint();

                PushSubscription::where('endpoint', $endpoint)->delete();
            }
        }
    }
}
