<?php

namespace Tjson\Util;

/**
 * Class ReflectionUtil
 *
 * @package Tjson\Util
 */
class ReflectionUtil
{
    /**
     * @return array<\ReflectionProperty>
     */
    public static function getReflectedProperties(object $object): array
    {
        $reflectedInstance = new \ReflectionObject($object);

        return array_filter($reflectedInstance->getProperties(), function ($reflectionProperty) {
            return $reflectionProperty->isStatic() === false;
        });
    }


    public static function isNullable(\ReflectionProperty $reflectedProperty): bool
    {
        if ($reflectedProperty->hasType() === true) {
            return $reflectedProperty
                ->getType()
                ->allowsNull()
            ;
        }

        return true;
    }
}