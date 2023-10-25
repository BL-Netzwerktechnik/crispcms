<?php

namespace crisp\models;

abstract class HookFile
{
    abstract public function preRender(mixed ...$args): void;

    abstract public function postRender(mixed ...$args): void;

    abstract public function preExecute(mixed ...$args): void;

    abstract public function postExecute(mixed ...$args): void;
}
