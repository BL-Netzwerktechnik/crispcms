<?php

namespace crisp\models;

use crisp\core\RESTfulAPI;
use Twig\Environment;

abstract class ThemeAPI
{
    public abstract function execute(string $Interface, Environment $TwigTheme): void;
}