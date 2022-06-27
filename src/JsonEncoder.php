<?php

namespace Tjson;

/**
 * Class JsonEncoder
 *
 * @package Tjson
 */
class JsonEncoder
{
    /**
     * @param mixed $object
     *
     * @return string
     * @throws \Tjson\Exception\AmbiguousNameDefinitionException
     * @throws \Tjson\Exception\RequiredPropertyNotFoundException
     */
    public function encode($object): string
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
     * @param int   $depth
     *
     * @return array
     * @throws \Tjson\Exception\AmbiguousNameDefinitionException
     * @throws \Tjson\Exception\RequiredPropertyNotFoundException
     */
    private function prepareArray(array $array, int $depth = 1): array
    {
        $arrayToEncode = [];
        foreach ($array as $object) {
            $arrayToEncode[] = $this->prepareObject($object, $depth++);
        }

        return $arrayToEncode;
    }


    /**
     * @param object $object
     * @param int    $depth
     *
     * @return array|null
     * @throws \Tjson\Exception\AmbiguousNameDefinitionException
     * @throws \Tjson\Exception\RequiredPropertyNotFoundException
     */
    private function prepareObject(object $object, int $depth = 1): ?array
    {
        if ($depth > 512) {
            return null;
        }
        $mappedProperties    = [];
        $reflectedProperties = \Tjson\Util\ReflectionUtil::getReflectedProperties($object);
        foreach ($reflectedProperties as $reflectedProperty) {
            $jsonPropertyName = \Tjson\Util\PropertyUtil::getJsonPropertyNameByClassProperty($reflectedProperty);

            if (\Tjson\Util\PropertyUtil::isOmitted($reflectedProperty) === true) {
                continue;
            }

            if (array_key_exists($jsonPropertyName, $mappedProperties) === true) {
                throw new \Tjson\Exception\AmbiguousNameDefinitionException('There is a duplicate of the property name definition for \'' . $jsonPropertyName . '\'');
            }

            $required = \Tjson\Util\PropertyUtil::isRequired($reflectedProperty);

            $reflectedProperty->setAccessible(true);
            if ($reflectedProperty->isInitialized($object) === false) {
                if ($required === true) {
                    throw new \Tjson\Exception\RequiredPropertyNotFoundException(sprintf('The required property \'%s\' is not initialized.', $jsonPropertyName));
                }
                continue;
            }
            $reflectedPropertyValue = $reflectedProperty->getValue($object);
            if (is_object($reflectedPropertyValue) === true) {
                $reflectedPropertyValue = $this->prepareObject($reflectedPropertyValue, $depth++);
            }
            if (is_array($reflectedPropertyValue) === true) {
                $reflectedPropertyValue = $this->prepareArray($reflectedPropertyValue, $depth++);
            }
            $mappedProperties[$jsonPropertyName] = $reflectedPropertyValue;
        }

        return $mappedProperties;
    }
}