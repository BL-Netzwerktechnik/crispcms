<?php

namespace crisp\Events;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Generic Theme Events.
 */
final class ApiErrorEvent extends Event
{
    public const ROUTE_NOT_FOUND = 'api.route_not_found';

    public function __construct(
        private string $message
    ) {
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
