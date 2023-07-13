<?php

namespace crisp\models;

use Twig\Environment;

abstract class ThemePage
{
    public abstract function preRender(): void;
    public abstract function postRender(): void;
}