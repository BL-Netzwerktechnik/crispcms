<?php

namespace crisp\Events;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Generic Theme Events.
 */
final class ThemePageErrorEvent extends Event
{
    public const ROUTE_NOT_FOUND = 'theme.route_not_found';

    public const SERVER_ERROR = 'theme.server_error';

    public function __construct(
        private string $message
    ) {
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
