<?php

namespace crisp\models;

use Twig\Environment;

abstract class ThemeAPI
{
    public abstract function execute(string $Interface): void;
}