<?php

namespace Tjson\Util;

/**
 * Class VersionUtil
 *
 * @package Tjson\Util
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