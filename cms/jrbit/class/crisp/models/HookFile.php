<?php

namespace crisp\models;

use Twig\Environment;

abstract class HookFile
{
    public abstract function preRender(string $CurrentPage, string $CurrentFile): void;
    public abstract function postRender(string $CurrentPage, string $CurrentFile): void;

    public abstract function preExecute(string $Interface): void;
    public abstract function postExecute(string $Interface): void;
}