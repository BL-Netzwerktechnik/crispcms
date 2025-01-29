<?php

namespace crisp\Events;

use crisp\api\License;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Generic Theme Events
 */
final class ThemeEvents
{
    public const PRE_EXECUTE = 'theme.pre_execute';
    public const POST_EXECUTE = 'theme.post_execute';

    public const PRE_RENDER = 'theme.pre_render';
    public const POST_RENDER = 'theme.post_render';

    public const SETUP = 'theme.setup';
    public const SETUP_CLI = 'theme.setup_cli';
}