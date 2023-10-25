<?php

namespace crisp\models;

abstract class ThemePage
{
    abstract public function preRender(): void;

    abstract public function postRender(): void;
}
