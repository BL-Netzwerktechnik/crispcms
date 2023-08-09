<?php

namespace crisp\models;

use Twig\Environment;

abstract class HookFile
{
    public abstract function preRender(mixed ...$args): void;
    public abstract function postRender(mixed ...$args): void;

    public abstract function preExecute(mixed ...$args): void;
    public abstract function postExecute(mixed ...$args): void;
}