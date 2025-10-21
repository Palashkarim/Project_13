<?php
// VAPID push notifications (webpush)
require_once __DIR__ . '/../../vendor/autoload.php'; // if using minishlink/web-push

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

function send_push($subscriptionArray, $payload) {
    // $subscriptionArray: ['endpoint'=>..., 'keys'=>['p256dh'=>..., 'auth'=>...]]
    // TODO: use real VAPID keys from env
    $auth = [
        'VAPID' => [
            'subject' => 'mailto:' . (getenv('MAIL_FROM') ?: 'no-reply@example.com'),
            'publicKey' => getenv('PUSH_PUBLIC_KEY') ?: '',
            'privateKey' => getenv('PUSH_PRIVATE_KEY') ?: ''
        ],
    ];
    $webPush = new WebPush($auth);
    $sub = Subscription::create($subscriptionArray);
    $webPush->sendOneNotification($sub, $payload);
}
