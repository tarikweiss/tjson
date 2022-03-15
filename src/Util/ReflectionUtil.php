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
        $reflectedInstance = new \ReflectionObject($object);

        return array_filter($reflectedInstance->getProperties(), function ($reflectionProperty) {
            return $reflectionProperty->isStatic() === false;
        });
    }


    /**
     * @param \ReflectionProperty $reflectedProperty
     *
     * @return bool
     */
    public static function isNullable(\ReflectionProperty $reflectedProperty): bool
    {
        if (true === $reflectedProperty->hasType()) {
            return $reflectedProperty
                ->getType()
                ->allowsNull()
            ;
        }

        return true;
    }
}