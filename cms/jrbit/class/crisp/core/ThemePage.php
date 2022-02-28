<?php

namespace crisp\core;

use Twig\Environment;

abstract class ThemePage
{
    public abstract function preRender(array $variables, Environment $TwigTheme): ?array;
    public abstract function postRender(array $variables, Environment $TwigTheme): ?array;
}