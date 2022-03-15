<?php

namespace Tarikweiss\Tjson\Util;

/**
 * Class PropertyUtil
 *
 * @package Tarikweiss\Tjson\Util
 */
class PropertyUtil
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


    /**
     * @param \ReflectionProperty $reflectedProperty
     *
     * @return bool
     */
    public static function isRequired(\ReflectionProperty $reflectedProperty): bool
    {
        /**
         * @var \Tarikweiss\Tjson\Attributes\Required $requiredAttributeInstance
         */

        $required   = $reflectedProperty->hasType();
        $reader     = new \Doctrine\Common\Annotations\AnnotationReader();
        $annotation = $reader->getPropertyAnnotation($reflectedProperty, \Tarikweiss\Tjson\Attributes\Required::class);
        if ($annotation instanceof \Tarikweiss\Tjson\Attributes\Required === true) {
            $required = $annotation->isRequired();
        }

        if (\Tarikweiss\Tjson\Util\VersionUtil::isPhp8OrNewer() === true) {
            $attributes = $reflectedProperty->getAttributes(\Tarikweiss\Tjson\Attributes\Required::class);
            foreach ($attributes as $attribute) {
                $requiredAttributeInstance = $attribute->newInstance();
                $required                  = $requiredAttributeInstance->isRequired();
            }
        }

        return $required;
    }


    /**
     * @param \ReflectionProperty $reflectedProperty
     *
     * @return bool
     */
    public static function isOmitted(\ReflectionProperty $reflectedProperty): bool
    {
        /**
         * @var \Tarikweiss\Tjson\Attributes\Omit $omitAttributeInstance
         */
        $omit = false;

        $reader         = new \Doctrine\Common\Annotations\AnnotationReader();
        $omitAnnotation = $reader->getPropertyAnnotation($reflectedProperty, \Tarikweiss\Tjson\Attributes\Omit::class);
        if ($omitAnnotation instanceof \Tarikweiss\Tjson\Attributes\Omit === true) {
            $omit = $omitAnnotation->isOmit();
        }

        if (\Tarikweiss\Tjson\Util\VersionUtil::isPhp8OrNewer() === true) {
            $omitAttributes = $reflectedProperty->getAttributes(\Tarikweiss\Tjson\Attributes\Omit::class);
            foreach ($omitAttributes as $omitAttribute) {
                $omitAttributeInstance = $omitAttribute->newInstance();
                $omit = $omitAttributeInstance->isOmit();
            }
        }

        return $omit;
    }
}