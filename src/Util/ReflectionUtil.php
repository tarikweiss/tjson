<?php

namespace Tarikweiss\Tjson\Util;

/**
 * Class ReflectionUtil
 *
 * @package Tarikweiss\Tjson\Util
 */
class ReflectionUtil
{
    /**
     * @param object $object
     *
     * @return \ReflectionProperty[]
     */
    public static function getReflectedProperties(object $object): array
    {
        $reflectedInstance   = new \ReflectionObject($object);

        return array_filter($reflectedInstance->getProperties(), function ($reflectionProperty) {
            return $reflectionProperty->isStatic() === false;
        });
    }
}