<?php

namespace Tarikweiss\Tjson\Util;

/**
 * Class VersionUtil
 *
 * @package Tarikweiss\Tjson\Util
 */
class VersionUtil
{
    /**
     * @return bool
     */
    public static function isPhp8OrNewer(): bool
    {
        return PHP_VERSION_ID >= 80000;
    }
}