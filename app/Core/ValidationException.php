<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Thrown by validators and services for user-correctable errors.
 * errors: [field => message]; use '_' for general (non-field) errors.
 */
class ValidationException extends RuntimeException
{
    public function __construct(public readonly array $errors, string $message = 'Please correct the highlighted errors.')
    {
        parent::__construct($message);
    }

    /** Convenience for business-rule violations not tied to a single field. */
    public static function rule(string $message, string $field = '_'): self
    {
        return new self([$field => $message], $message);
    }
}
