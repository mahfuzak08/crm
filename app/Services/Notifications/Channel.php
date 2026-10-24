<?php
declare(strict_types=1);

namespace App\Services\Notifications;

/**
 * A delivery channel (email, sms, whatsapp, system ...).
 * New providers (e.g. an SMS gateway) implement this interface and are
 * registered in NotificationService::channels() — no provider is hard-coded
 * into business logic.
 */
interface Channel
{
    public function name(): string;

    public function isConfigured(): bool;

    /** @return true on success; throw on failure */
    public function send(string $recipient, string $subject, string $body, array $options = []): bool;
}
