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
        'int'   => 'integer',
        'bool'  => 'boolean',
        'float' => 'double',
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


    /**
     * @param \ReflectionProperty $reflectedProperty
     *
     * @return string
     */
    public static function getJsonPropertyNameByClassProperty(\ReflectionProperty $reflectedProperty): string
    {
        $jsonPropertyName = $reflectedProperty->getName();

        \Doctrine\Common\Annotations\AnnotationRegistry::registerLoader('class_exists');
        $doctrineAnnotationReader   = new \Doctrine\Common\Annotations\AnnotationReader();
        $mappedPropertyNameInstance = $doctrineAnnotationReader->getPropertyAnnotation($reflectedProperty, \Tarikweiss\Tjson\Attributes\MappedPropertyName::class);
        if ($mappedPropertyNameInstance !== null) {
            $jsonPropertyName = $mappedPropertyNameInstance->getName();
        }

        if (VersionUtil::isPhp8OrNewer() === true) {
            $reflectedAttributes = $reflectedProperty->getAttributes(\Tarikweiss\Tjson\Attributes\MappedPropertyName::class);
            foreach ($reflectedAttributes as $reflectedAttribute) {
                $mappedPropertyNameInstance = $reflectedAttribute->newInstance();
                $jsonPropertyName           = $mappedPropertyNameInstance->getName();
            }
        }

        return $jsonPropertyName;
    }
}