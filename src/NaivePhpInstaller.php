<?php

namespace Opencart\Extension;

class NaivePhpInstaller
{
    static $registry;

    public function __get($name)
    {
        return self::$registry->get($name);
    }

    public function install($file) {
        include($file);
    }
}