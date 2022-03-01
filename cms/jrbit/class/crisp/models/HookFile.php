<?php

namespace crisp\models;

use Twig\Environment;

abstract class HookFile
{
    public abstract function preRender(array $variables, Environment $TwigTheme, string $CurrentPage, string $CurrentFile): ?array;
    public abstract function postRender(array $variables, Environment $TwigTheme, string $CurrentPage, string $CurrentFile): ?array;

    public abstract function preExecute(string $Interface, Environment $TwigTheme): void;
    public abstract function postExecute(string $Interface, Environment $TwigTheme): void;
}