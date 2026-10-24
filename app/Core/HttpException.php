<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/** Expected HTTP failure (403/404/419/429...). Message is safe to show users. */
class HttpException extends RuntimeException
{
    public function __construct(public readonly int $status, string $message = '')
    {
        parent::__construct($message !== '' ? $message : self::defaultMessage($status), $status);
    }

    public static function defaultMessage(int $status): string
    {
        return match ($status) {
            403 => 'You do not have permission to perform this action.',
            404 => 'The requested page was not found.',
            405 => 'Method not allowed.',
            419 => 'Your session has expired. Please refresh the page and try again.',
            429 => 'Too many requests. Please wait and try again.',
            default => 'Something went wrong. Please try again.',
        };
    }
}
