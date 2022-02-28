<?php

namespace crisp\core;

use Twig\Environment;

abstract class ThemeAPI extends RESTfulAPI
{
    public abstract function execute(string $Interface, Environment $TwigTheme): ?array;
}