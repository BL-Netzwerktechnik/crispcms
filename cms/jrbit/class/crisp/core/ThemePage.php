<?php

namespace crisp\core;

use Twig\Environment;

interface ThemePage
{
    public function preRender(array $variables, Environment $TwigTheme): ?array;
    public function postRender(array $variables, Environment $TwigTheme): ?array;
}