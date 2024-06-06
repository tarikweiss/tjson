<?php

namespace Tjson;

/**
 * Class JsonEncoder
 *
 * @package Tjson
 */
class JsonEncoder
{
    private int $maxDepth;


    public function __construct(int $maxDepth = 512)
    {
        $this->maxDepth = $maxDepth;
    }


    /**
     * @param mixed $object
     *
     * @return string
     * @throws \Tjson\Exception\AmbiguousNameDefinitionException
     * @throws \Tjson\Exception\RequiredPropertyNotFoundException
     */
    public function encode($object): string
    {
        return $this->prepareAny($object);
    }


    /**
     * @throws \Tjson\Exception\AmbiguousNameDefinitionException
     * @throws \Tjson\Exception\RequiredPropertyNotFoundException
     */
    private function prepareAny($object, int $depth = 1): string
    {
        $isArray  = is_array($object);
        $isObject = is_object($object);

        if ($isArray === false && $isObject === false) {
            return json_encode($object);
        }

        $preparedData = null;
        if ($isObject === true) {
            $preparedData = $this->prepareObject($object, $depth);
        }
        if ($isArray === true) {
            $preparedData = $this->prepareArray($object, $depth);
        }

        return json_encode($preparedData);
    }


    /**
     * @throws \Tjson\Exception\AmbiguousNameDefinitionException
     * @throws \Tjson\Exception\RequiredPropertyNotFoundException
     */
    private function prepareArray(array $array, int $depth = 1): array
    {
        if ($depth > $this->maxDepth) {
            return [];
        }

        $arrayToEncode = [];
        foreach ($array as $object) {
            $arrayToEncode[] = $this->prepareAny($object, $depth++);
        }

        return $arrayToEncode;
    }


    /**
     * @throws \Tjson\Exception\AmbiguousNameDefinitionException
     * @throws \Tjson\Exception\RequiredPropertyNotFoundException
     */
    private function prepareObject(object $object, int $depth = 1): ?array
    {
        if ($depth > $this->maxDepth) {
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