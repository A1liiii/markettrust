<?php

namespace App\Services;

use App\Contracts\Services\NotificationServiceInterface;
use App\Models\Notification;

class NotificationService implements NotificationServiceInterface
{
    /**
     * Dispatch notification with deduplication.
     */
    public function notify(int $recipientUserId, ?int $transactionId, string $eventType, string $message): ?Notification
    {
        $existing = Notification::where('user_id', $recipientUserId)
            ->where('event_type', $eventType)
            ->when($transactionId, fn ($q) => $q->where('transaction_id', $transactionId))
            ->where('created_at', '>=', now()->subMinutes(5))
            ->first();

        if ($existing) {
            return $existing;
        }

        return Notification::create([
            'user_id' => $recipientUserId,
            'transaction_id' => $transactionId,
            'event_type' => $eventType,
            'message' => $message,
            'read_at' => null,
            'created_at' => now(),
        ]);
    }
}
