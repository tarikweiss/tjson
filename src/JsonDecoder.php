<?php
declare(strict_types=1);

namespace Tarikweiss\Tjson;

/**
 * Class JsonDecoder
 *
 * @package Tarikweiss\Tjson
 */
class JsonDecoder
{
    /**
     * @param string $json
     * @param string $className
     *
     * @return mixed
     * @throws \ReflectionException
     * @throws \Tarikweiss\Tjson\Exception\AmbiguousNameDefinitionException
     * @throws \Tarikweiss\Tjson\Exception\AmbiguousTypeDefinitionException
     * @throws \Tarikweiss\Tjson\Exception\ClassNotFoundException
     * @throws \Tarikweiss\Tjson\Exception\NoMatchingTypeDefinitionException
     * @throws \Tarikweiss\Tjson\Exception\RequiredPropertyNotFoundException
     */
    public function decode(string $json, string $className): mixed
    {
        /**
         * @var \Tarikweiss\Tjson\Attributes\MappedPropertyName $mappedPropertyNameInstance
         */
        $decodedJson = json_decode($json, false);

        if (class_exists($className) === false) {
            throw new \Tarikweiss\Tjson\Exception\ClassNotFoundException('Class ' . $className . ' not found.');
        }

        $reflectionClass = (new \ReflectionClass($className));
        $mappableClass   = $reflectionClass->newInstanceWithoutConstructor();

        $reflectionObject    = (new \ReflectionObject($mappableClass));
        $reflectedProperties = $reflectionObject->getProperties();

        $processedJsonPropertyNames = [];

        foreach ($reflectedProperties as $reflectedProperty) {
            $jsonPropertyName = \Tarikweiss\Tjson\Util\ReflectionUtil::getJsonPropertyNameByProperty($reflectedProperty);
            if (array_key_exists($jsonPropertyName, $processedJsonPropertyNames) === true) {
                throw new \Tarikweiss\Tjson\Exception\AmbiguousNameDefinitionException('There is a duplicate of the property name definition for \'' . $jsonPropertyName . '\'');
            }
            $processedJsonPropertyNames[] = $jsonPropertyName;
            $required                     = $this->isRequired($reflectedProperty);

            $nullable = $this->isNullable($reflectedProperty);
            $types    = $this->getTypes($reflectedProperty);

            if (property_exists($decodedJson, $jsonPropertyName) === false) {
                if ($required === true) {
                    throw new \Tarikweiss\Tjson\Exception\RequiredPropertyNotFoundException(sprintf('Required class property \'%s\' not found in json.', $reflectedProperty->getName()));
                }
                continue;
            }

            $jsonValue     = $decodedJson->$jsonPropertyName;
            $jsonValueType = gettype($jsonValue);

            if (count($types) === 1) {
                if ($jsonValueType === 'object') {
                    $type = $types[0];

                    // Check if type is builtin and if not, then map that.
                    if ($type->isBuiltin() === false) {
                        $className = $type->getName();
                        // Maybe find a better solution for that...
                        $jsonValue = $this->decode(json_encode($jsonValue), $className);
                    }
                }
            }
            if (count($types) > 1) {
                $customTypesCount     = 0;
                $typeThatIsNotBuiltIn = null;
                $hasEvenMatch         = false;
                foreach ($types as $type) {
                    if ($type->isBuiltin() === false) {
                        $customTypesCount++;
                    }
                    if ($customTypesCount > 1) {
                        throw new \Tarikweiss\Tjson\Exception\AmbiguousTypeDefinitionException('Cannot infer type for class property' . $reflectedProperty->getName());
                    }
                    $jsonValueTypeDoesMatch = \Tarikweiss\Tjson\Util\TypeUtil::doTypesMatch($jsonValueType, $type->getName());
                    $hasEvenMatch           = $jsonValueTypeDoesMatch | $hasEvenMatch;
                }
                if ($typeThatIsNotBuiltIn !== null) {
                    $jsonValue = $this->decode(json_encode($jsonValue), $className);
                }
                if (false === $hasEvenMatch) {
                    throw new \Tarikweiss\Tjson\Exception\NoMatchingTypeDefinitionException('Defined types do not contain type of json value.');
                }
            }

            $this->nullCheck($jsonValueType, $nullable);

            $reflectedProperty->setAccessible(true);
            $reflectedProperty->setValue($mappableClass, $jsonValue);
        }

        return $mappableClass;
    }


    /**
     * @param \ReflectionProperty $reflectedProperty
     *
     * @return bool
     */
    private function isRequired(\ReflectionProperty $reflectedProperty)
    {
        /**
         * @var \Tarikweiss\Tjson\Attributes\Required $requiredAttributeInstance
         */
        $attributes = $reflectedProperty->getAttributes(\Tarikweiss\Tjson\Attributes\Required::class);
        $required   = $reflectedProperty->hasType();
        if ($required === false) {
            foreach ($attributes as $attribute) {
                $requiredAttributeInstance = $attribute->newInstance();
                if ($requiredAttributeInstance->isRequired()) {
                    $required = true;
                }
            }
        }

        return $required;
    }


    /**
     * @param mixed $jsonValueType
     * @param bool  $nullable
     *
     * @throws \Tarikweiss\Tjson\Exception\NoMatchingTypeDefinitionException
     */
    private function nullCheck(mixed $jsonValueType, bool $nullable): void
    {
        if (\Tarikweiss\Tjson\Util\TypeUtil::doTypesMatch($jsonValueType, 'NULL') === true && $nullable === false) {
            throw new \Tarikweiss\Tjson\Exception\NoMatchingTypeDefinitionException('Defined types do not contain type of json value.');
        }
    }


    /**
     * @param \ReflectionProperty $reflectedProperty
     *
     * @return bool
     */
    private function isNullable(\ReflectionProperty $reflectedProperty): bool
    {
        if (true === $reflectedProperty->hasType()) {
            return $reflectedProperty
                ->getType()
                ->allowsNull()
            ;
        }

        return true;
    }


    /**
     * @param \ReflectionProperty $reflectedProperty
     *
     * @return \Tarikweiss\Tjson\Decoding\AbstractedType[]
     */
    private function getTypes(\ReflectionProperty $reflectedProperty): array
    {
        /**
         * @var \Tarikweiss\Tjson\Attributes\MappedPropertyClass $mappedPropertyClassInstance
         */
        $types            = [];
        $intersectionType = false;
        if (true === $reflectedProperty->hasType()) {
            $type = $reflectedProperty->getType();

            if ($type instanceof \ReflectionNamedType === true) {
                $types[] = (new \Tarikweiss\Tjson\Decoding\AbstractedType($type->getName(), $type->isBuiltin()));
            }

            if ($type instanceof \ReflectionUnionType === true) {
                foreach ($type->getTypes() as $type) {
                    $types[] = (new \Tarikweiss\Tjson\Decoding\AbstractedType($type->getName(), $type->isBuiltin()));
                }
            }

            if ($type instanceof \ReflectionIntersectionType === true) {
                $intersectionType = true;
                foreach ($type->getTypes() as $type) {
                    $types[] = (new \Tarikweiss\Tjson\Decoding\AbstractedType($type->getName(), $type->isBuiltin()));
                }
            }
        }

        $mappedType = null;

        $mappedPropertyClassAttributes = $reflectedProperty->getAttributes(\Tarikweiss\Tjson\Attributes\MappedPropertyClass::class);
        foreach ($mappedPropertyClassAttributes as $mappedPropertyClassAttribute) {
            $mappedPropertyClassInstance = $mappedPropertyClassAttribute->newInstance();
            $class                       = $mappedPropertyClassInstance->getClass();
            if (class_exists($class) === true) {
                $mappedType = $class;
            }
        }

        if ($mappedType !== null) {
            if (count($types) === 0) {
                $types = [];
            }
            if (count($types) === 1) {
                $type = $types[0];
                if ($type->getName() !== $mappedType) {
                    throw new \Tarikweiss\Tjson\Exception\NoMatchingTypeDefinitionException('Mapped property class is not matching given type.');
                }
            }
            if (count($types) > 1) {
                if ($intersectionType === false) {
                    $foundMatching = false;
                    foreach ($types as $type) {
                        $typeName = $type->getName();
                        if ($mappedType instanceof $typeName) {
                            $foundMatching = true;
                        }
                    }
                    if ($foundMatching === false) {
                        throw new \Tarikweiss\Tjson\Exception\NoMatchingTypeDefinitionException('Could not find any matching type definition for ');
                    }
                }
                if ($intersectionType === true) {
                    $isMatchingAll = true;
                    foreach ($types as $type) {
                        $typeName = $type->getName();
                        if ($mappedType instanceof $typeName === false) {
                            $isMatchingAll = false;
                            break;
                        }
                    }

                    if ($isMatchingAll === false) {
                        throw new \Tarikweiss\Tjson\Exception\NoMatchingTypeDefinitionException('The mapped property class \'' . $mappedType . '\' does not match the intersection type.');
                    }
                }
            }
        }

        return $types;
    }
}