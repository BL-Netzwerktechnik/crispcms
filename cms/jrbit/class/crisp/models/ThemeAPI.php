<?php

namespace crisp\models;

use crisp\core\RESTfulAPI;
use Twig\Environment;

abstract class ThemeAPI extends RESTfulAPI
{
    public abstract function execute(string $Interface, Environment $TwigTheme): ?array;
}