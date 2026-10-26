<?php
declare(strict_types=1);

namespace V7Shield;

/** Exception with a user-safe message and an HTTP-like status code. */
final class ServiceException extends \RuntimeException
{
    public function __construct(string $message, private readonly int $status = 400)
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return $this->status;
    }
}
