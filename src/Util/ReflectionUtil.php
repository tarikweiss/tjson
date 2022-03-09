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


    /**
     * @param \ReflectionProperty $reflectedProperty
     *
     * @return string
     */
    public static function getJsonPropertyNameByProperty(\ReflectionProperty $reflectedProperty): string
    {
        $jsonPropertyName = $reflectedProperty->getName();

        $reflectedAttributes = $reflectedProperty->getAttributes(\Tarikweiss\Tjson\Attributes\MappedPropertyName::class);
        foreach ($reflectedAttributes as $reflectedAttribute) {
            $mappedPropertyNameInstance = $reflectedAttribute->newInstance();
            $jsonPropertyName           = $mappedPropertyNameInstance->getName();
        }

        return $jsonPropertyName;
    }
}