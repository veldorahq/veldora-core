<?php

declare(strict_types=1);

namespace Veldora\Framework\Auth;

use Exception;

class AuthorizationException extends Exception
{
    /**
     * The HTTP status code.
     */
    protected int $status = 403;

    /**
     * Create a new AuthorizationException instance.
     */
    public function __construct(string $message = 'This action is unauthorized.', int $code = 403, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the HTTP status code.
     */
    public function getStatusCode(): int
    {
        return $this->status;
    }
}
