<?php

namespace Tarikweiss\Tjson;

/**
 * Class JsonEncoder
 *
 * @package Tarikweiss\Tjson
 */
class JsonEncoder
{
    /**
     * @param mixed $object
     *
     * @return string
     * @throws \Tarikweiss\Tjson\Exception\AmbiguousNameDefinitionException
     */
    public function encode($object)
    {
        $isArray  = is_array($object);
        $isObject = is_object($object);

        if ($isArray === false && $isObject === false) {
            return json_encode($object);
        }

        $preparedData = null;
        if ($isObject === true) {
            $preparedData = $this->prepareObject($object);
        }
        if ($isArray === true) {
            $preparedData = $this->prepareArray($object);
        }

        return json_encode($preparedData);
    }


    /**
     * @param array $array
     *
     * @return array
     * @throws \Tarikweiss\Tjson\Exception\AmbiguousNameDefinitionException
     */
    private function prepareArray(array $array): array
    {
        $arrayToEncode = [];
        foreach ($array as $object) {
            $arrayToEncode[] = $this->prepareObject($object);
        }

        return $arrayToEncode;
    }


    /**
     * @param object $object
     *
     * @return array
     * @throws \Tarikweiss\Tjson\Exception\AmbiguousNameDefinitionException
     */
    private function prepareObject(object $object, int $depth = 1): array
    {
        $mappedProperties    = [];
        $reflectedProperties = \Tarikweiss\Tjson\Util\ReflectionUtil::getReflectedProperties($object);
        foreach ($reflectedProperties as $reflectedProperty) {
            $jsonPropertyName = \Tarikweiss\Tjson\Util\TypeUtil::getJsonPropertyNameByClassProperty($reflectedProperty);
            if (array_key_exists($jsonPropertyName, $mappedProperties) === true) {
                throw new \Tarikweiss\Tjson\Exception\AmbiguousNameDefinitionException('There is a duplicate of the property name definition for \'' . $jsonPropertyName . '\'');
            }

            $reflectedProperty->setAccessible(true);
            if ($reflectedProperty->isInitialized($object) === false) {
                // Behave smooth, if we have an uninitialized, strong typed property.
                // @todo make this behaviour configurable, because maybe it should throw an exception...
                continue;
            }
            $reflectedPropertyValue = $reflectedProperty->getValue($object);
            if (is_object($reflectedPropertyValue) === true) {
                $reflectedPropertyValue = $this->prepareObject($reflectedPropertyValue, $depth++);
            }
            $mappedProperties[$jsonPropertyName] = $reflectedPropertyValue;
        }

        return $mappedProperties;
    }


    /**
     * @param \ReflectionProperty $reflectedProperty
     *
     * @return bool
     */
    private function isOmitted(\ReflectionProperty $reflectedProperty): bool
    {
        /**
         * @var \Tarikweiss\Tjson\Attributes\Omit $omitAttributeInstance
         */
        $omit = false;

        $reader         = new \Doctrine\Common\Annotations\AnnotationReader();
        $omitAnnotation = $reader->getPropertyAnnotation($reflectedProperty, \Tarikweiss\Tjson\Attributes\Omit::class);
        if ($omitAnnotation instanceof \Tarikweiss\Tjson\Attributes\Omit === true && $omitAnnotation->isOmit() === true) {
            $omit = true;
        }

        if (\Tarikweiss\Tjson\Util\VersionUtil::isPhp8OrNewer() === true) {
            $omitAttributes = $reflectedProperty->getAttributes(\Tarikweiss\Tjson\Attributes\Omit::class);
            foreach ($omitAttributes as $omitAttribute) {
                $omitAttributeInstance = $omitAttribute->newInstance();
                if ($omitAttributeInstance->isOmit() === true) {
                    $omit = true;
                }
            }
        }

        return $omit;
    }
}