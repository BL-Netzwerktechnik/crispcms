<?php

namespace crisp\models;

abstract class ThemeAPI
{
    abstract public function execute(string $Interface): void;
}
