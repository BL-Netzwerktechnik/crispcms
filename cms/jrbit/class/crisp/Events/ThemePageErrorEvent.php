<?php

namespace crisp\Events;

use crisp\core\Logger;
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
        if (Logger::isTraceEnabled()) {
            Logger::getLogger(__METHOD__)->log(Logger::LOG_LEVEL_TRACE, 'Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        }
    }

    public function getMessage(): string
    {
        if (Logger::isTraceEnabled()) {
            Logger::getLogger(__METHOD__)->log(Logger::LOG_LEVEL_TRACE, 'Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        }

        return $this->message;
    }
}
