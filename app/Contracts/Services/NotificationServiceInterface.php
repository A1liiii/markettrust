<?php

namespace App\Contracts\Services;

use App\Models\Notification;

interface NotificationServiceInterface
{
    /**
     * Dispatch notification with deduplication.
     */
    public function notify(
        int $recipientUserId,
        ?int $transactionId,
        string $eventType,
        string $message
    ): ?Notification;
}
