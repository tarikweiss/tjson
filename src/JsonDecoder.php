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
    public function decodeByClassName(string $json, string $className)
    {
        if (class_exists($className) === false) {
            throw new \Tarikweiss\Tjson\Exception\ClassNotFoundException('Class ' . $className . ' not found.');
        }

        $reflectionClass = new \ReflectionClass($className);
        $object          = $reflectionClass->newInstanceWithoutConstructor();

        return $this->decodeByObject($json, $object);
    }


    /**
     * @param string $json
     * @param object $object
     *
     * @return object
     * @throws \ReflectionException
     * @throws \Tarikweiss\Tjson\Exception\AmbiguousNameDefinitionException
     * @throws \Tarikweiss\Tjson\Exception\AmbiguousTypeDefinitionException
     * @throws \Tarikweiss\Tjson\Exception\ClassNotFoundException
     * @throws \Tarikweiss\Tjson\Exception\NoMatchingTypeDefinitionException
     * @throws \Tarikweiss\Tjson\Exception\RequiredPropertyNotFoundException
     */
    public function decodeByObject(string $json, object $object)
    {
        /**
         * @var \Tarikweiss\Tjson\Attributes\MappedPropertyName $mappedPropertyNameInstance
         */
        $decodedJson = json_decode($json, false);

        $reflectionObject    = new \ReflectionObject($object);
        $reflectedProperties = $reflectionObject->getProperties();

        $processedJsonPropertyNames = [];

        $mapping = [];

        foreach ($reflectedProperties as $reflectedProperty) {
            $jsonPropertyName = \Tarikweiss\Tjson\Util\PropertyUtil::getJsonPropertyNameByClassProperty($reflectedProperty);

            if (\Tarikweiss\Tjson\Util\PropertyUtil::isOmitted($reflectedProperty)) {
                continue;
            }

            if (array_key_exists($jsonPropertyName, $processedJsonPropertyNames) === true) {
                throw new \Tarikweiss\Tjson\Exception\AmbiguousNameDefinitionException('There is a duplicate of the property name definition for \'' . $jsonPropertyName . '\'');
            }
            $processedJsonPropertyNames[] = $jsonPropertyName;
            $required                     = \Tarikweiss\Tjson\Util\PropertyUtil::isRequired($reflectedProperty);

            $nullable = \Tarikweiss\Tjson\Util\ReflectionUtil::isNullable($reflectedProperty);
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
                $type = $types[0];

                $jsonValueTypeDoesMatch = \Tarikweiss\Tjson\Util\PropertyUtil::doTypesMatch($jsonValueType, $type->getName());
                if ($jsonValueTypeDoesMatch === false) {
                    switch ($jsonValueType) {
                        case 'object':
                        {
                            // Check if type is builtin and if not, then map that.
                            if ($type->isBuiltin() === false) {
                                $className = $type->getName();
                                // Maybe find a better solution for that...
                                $jsonValue = $this->decodeByClassName(json_encode($jsonValue), $className);
                            }
                            break;
                        }
                        case 'NULL':
                        {
                            break;
                        }
                        default:
                        {
                            throw new \Tarikweiss\Tjson\Exception\NoMatchingTypeDefinitionException('No matching type found for json property \'' . $jsonPropertyName . '\'');
                        }
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
                        $typeThatIsNotBuiltIn = new \Tarikweiss\Tjson\Decoding\AbstractedType($type->getName(), $type->isBuiltin());
                    }
                    if ($customTypesCount > 1) {
                        throw new \Tarikweiss\Tjson\Exception\AmbiguousTypeDefinitionException('Cannot infer type for class property' . $reflectedProperty->getName());
                    }
                    $jsonValueTypeDoesMatch = \Tarikweiss\Tjson\Util\PropertyUtil::doTypesMatch($jsonValueType, $type->getName());
                    $hasEvenMatch           = $jsonValueTypeDoesMatch | $hasEvenMatch;
                }
                if ($typeThatIsNotBuiltIn !== null && is_object($jsonValue) === true) {
                    $jsonValue = $this->decodeByClassName(json_encode($jsonValue), $typeThatIsNotBuiltIn->getName());
                }
                if (false === $hasEvenMatch) {
                    throw new \Tarikweiss\Tjson\Exception\NoMatchingTypeDefinitionException('Defined types do not contain type of json value.');
                }
            }

            $this->nullCheck($jsonValueType, $nullable);

            $mapping[$reflectedProperty->getName()] = [
                'reflectedProperty' => $reflectedProperty,
                'jsonValue'         => $jsonValue,
            ];
        }

        foreach ($mapping as $item) {
            $reflectedProperty = $item['reflectedProperty'];
            $jsonValue         = $item['jsonValue'];

            $reflectedProperty->setAccessible(true);
            $reflectedProperty->setValue($object, $jsonValue);
        }

        return $object;
    }


    /**
     * @param mixed $jsonValueType
     * @param bool  $nullable
     *
     * @throws \Tarikweiss\Tjson\Exception\NoMatchingTypeDefinitionException
     */
    private function nullCheck($jsonValueType, bool $nullable): void
    {
        if (\Tarikweiss\Tjson\Util\PropertyUtil::doTypesMatch($jsonValueType, 'NULL') === true && $nullable === false) {
            throw new \Tarikweiss\Tjson\Exception\NoMatchingTypeDefinitionException('Defined types do not contain type of json value.');
        }
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

        $mappedType = $this->getMappedType($reflectedProperty);

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


    /**
     * @param \ReflectionProperty $reflectedProperty
     *
     * @return string|null
     */
    private function getMappedType(\ReflectionProperty $reflectedProperty): ?string
    {
        $mappedType = null;

        $reader     = new \Doctrine\Common\Annotations\AnnotationReader();
        $annotation = $reader->getPropertyAnnotation($reflectedProperty, \Tarikweiss\Tjson\Attributes\MappedPropertyClass::class);
        if ($annotation instanceof \Tarikweiss\Tjson\Attributes\MappedPropertyClass === true) {
            $mappedType = $annotation->getClass();
        }

        if (\Tarikweiss\Tjson\Util\VersionUtil::isPhp8OrNewer() === true) {
            $mappedPropertyClassAttributes = $reflectedProperty->getAttributes(\Tarikweiss\Tjson\Attributes\MappedPropertyClass::class);
            foreach ($mappedPropertyClassAttributes as $mappedPropertyClassAttribute) {
                $mappedPropertyClassInstance = $mappedPropertyClassAttribute->newInstance();
                $class                       = $mappedPropertyClassInstance->getClass();
                if (class_exists($class) === true) {
                    $mappedType = $class;
                }
            }
        }

        return $mappedType;
    }
}