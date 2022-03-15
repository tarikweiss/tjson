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
     * @param int    $depth
     *
     * @return array|null
     * @throws \Tarikweiss\Tjson\Exception\AmbiguousNameDefinitionException
     * @throws \Tarikweiss\Tjson\Exception\RequiredPropertyNotFoundException
     */
    private function prepareObject(object $object, int $depth = 1): ?array
    {
        if ($depth > 512) {
            return null;
        }
        $mappedProperties    = [];
        $reflectedProperties = \Tarikweiss\Tjson\Util\ReflectionUtil::getReflectedProperties($object);
        foreach ($reflectedProperties as $reflectedProperty) {
            $jsonPropertyName = \Tarikweiss\Tjson\Util\PropertyUtil::getJsonPropertyNameByClassProperty($reflectedProperty);

            if (\Tarikweiss\Tjson\Util\PropertyUtil::isOmitted($reflectedProperty) === true) {
                continue;
            }

            if (array_key_exists($jsonPropertyName, $mappedProperties) === true) {
                throw new \Tarikweiss\Tjson\Exception\AmbiguousNameDefinitionException('There is a duplicate of the property name definition for \'' . $jsonPropertyName . '\'');
            }

            $required = \Tarikweiss\Tjson\Util\PropertyUtil::isRequired($reflectedProperty);

            $reflectedProperty->setAccessible(true);
            if ($reflectedProperty->isInitialized($object) === false) {
                if ($required === true) {
                    throw new \Tarikweiss\Tjson\Exception\RequiredPropertyNotFoundException(sprintf('The required property \'%s\' is not initialized.', $jsonPropertyName));
                }
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
}