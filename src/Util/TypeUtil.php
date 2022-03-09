<?php

namespace Tarikweiss\Tjson\Util;

/**
 * Class TypeUtil
 *
 * @package Tarikweiss\Tjson\Util
 */
class TypeUtil
{
    private const MAPPING_SHORT_LONG = [
        'int'  => 'integer',
        'bool' => 'boolean',
    ];


    /**
     * Check if types match, either retrieved by gettype() [which may return integer instead of int, etc.] or the short type [retrieved by reflection, etc.].
     *
     * @param string $typeA
     * @param string $typeB
     *
     * @return bool
     */
    public static function doTypesMatch(string $typeA, string $typeB): bool
    {
        if (array_key_exists($typeA, static::MAPPING_SHORT_LONG) === true) {
            $typeA = static::MAPPING_SHORT_LONG[$typeA];
        }
        if (array_key_exists($typeB, static::MAPPING_SHORT_LONG) === true) {
            $typeB = static::MAPPING_SHORT_LONG[$typeB];
        }

        return $typeA === $typeB;
    }
}