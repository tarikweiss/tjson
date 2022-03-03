<?php

namespace Tarikweiss\Tjson;

/**
 * Class JsonDecoder
 *
 * @package Tarikweiss\Tjson
 */
class JsonDecoder
{
    public function decode(string $json, string $className)
    {
        $decodedJson = json_decode($json, true);

        if (false === class_exists($className)) {
            throw new \Tarikweiss\Tjson\Exception\ClassNotFoundException('Class ' . $className . ' not found.');
        }

        $reflectionClass    = (new \ReflectionClass($className));
        $reflectionInstance = $reflectionClass->newInstanceWithoutConstructor();

        $reflectionObject    = (new \ReflectionObject($reflectionInstance));
        $reflectedProperties = $reflectionObject->getProperties();

        foreach ($reflectedProperties as $reflectedProperty) {
            echo $reflectedProperty->getName() . PHP_EOL;
            $reflectedAttributes = $reflectedProperty->getAttributes(\Tarikweiss\Tjson\Attributes\MappedPropertyName::class);
            foreach ($reflectedAttributes as $reflectedAttribute) {
                $mappedPropertyNameArguments = $reflectedAttribute->getArguments();
            }
        }
    }
}