<?php
declare(strict_types=1);

namespace Tjson;

/**
 * Class JsonDecoder
 *
 * @package Tjson
 */
class JsonDecoder
{
    /**
     * @template T of object
     * @param class-string<T> $className
     *
     * @return object<T>
     * @throws \ReflectionException
     * @throws \Tjson\Exception\AmbiguousNameDefinitionException
     * @throws \Tjson\Exception\AmbiguousTypeDefinitionException
     * @throws \Tjson\Exception\ClassNotFoundException
     * @throws \Tjson\Exception\NoMatchingTypeDefinitionException
     * @throws \Tjson\Exception\RequiredPropertyNotFoundException
     */
    public function decodeByClassName(string $json, string $className): object
    {
        if (class_exists($className) === false) {
            throw new \Tjson\Exception\ClassNotFoundException('Class ' . $className . ' not found.');
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
     * @throws \Tjson\Exception\AmbiguousNameDefinitionException
     * @throws \Tjson\Exception\AmbiguousTypeDefinitionException
     * @throws \Tjson\Exception\ClassNotFoundException
     * @throws \Tjson\Exception\NoMatchingTypeDefinitionException
     * @throws \Tjson\Exception\RequiredPropertyNotFoundException
     */
    public function decodeByObject(string $json, object $object): object
    {
        $decodedJson = json_decode($json, false);

        $reflectionObject    = new \ReflectionObject($object);
        $reflectedProperties = $reflectionObject->getProperties();

        $processedJsonPropertyNames = [];

        $mapping = [];

        foreach ($reflectedProperties as $reflectedProperty) {
            $jsonPropertyName = \Tjson\Util\PropertyUtil::getJsonPropertyNameByClassProperty($reflectedProperty);

            if (\Tjson\Util\PropertyUtil::isOmitted($reflectedProperty)) {
                continue;
            }

            if (array_key_exists($jsonPropertyName, $processedJsonPropertyNames) === true) {
                throw new \Tjson\Exception\AmbiguousNameDefinitionException('There is a duplicate of the property name definition for \'' . $jsonPropertyName . '\'');
            }
            $processedJsonPropertyNames[] = $jsonPropertyName;
            $required                     = \Tjson\Util\PropertyUtil::isRequired($reflectedProperty);

            $nullable = \Tjson\Util\ReflectionUtil::isNullable($reflectedProperty);
            $types    = $this->getTypes($reflectedProperty);

            if (property_exists($decodedJson, $jsonPropertyName) === false) {
                if ($required === true) {
                    throw new \Tjson\Exception\RequiredPropertyNotFoundException(sprintf('Required class property \'%s\' not found in json.', $reflectedProperty->getName()));
                }
                continue;
            }

            $jsonValue     = $decodedJson->$jsonPropertyName;
            $jsonValueType = gettype($jsonValue);

            if (count($types) === 1) {
                $type = $types[0];

                $jsonValueTypeDoesMatch = \Tjson\Util\PropertyUtil::doTypesMatch($jsonValueType, $type->getName());
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
                            throw new \Tjson\Exception\NoMatchingTypeDefinitionException('No matching type found for json property \'' . $jsonPropertyName . '\'');
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
                        $typeThatIsNotBuiltIn = new \Tjson\Decoding\AbstractedType($type->getName(), $type->isBuiltin());
                    }
                    if ($customTypesCount > 1) {
                        throw new \Tjson\Exception\AmbiguousTypeDefinitionException('Cannot infer type for class property' . $reflectedProperty->getName());
                    }
                    $jsonValueTypeDoesMatch = \Tjson\Util\PropertyUtil::doTypesMatch($jsonValueType, $type->getName());
                    $hasEvenMatch           = $jsonValueTypeDoesMatch | $hasEvenMatch;
                }
                if ($typeThatIsNotBuiltIn !== null && is_object($jsonValue) === true) {
                    $jsonValue = $this->decodeByClassName(json_encode($jsonValue), $typeThatIsNotBuiltIn->getName());
                }
                if (false === $hasEvenMatch) {
                    throw new \Tjson\Exception\NoMatchingTypeDefinitionException('Defined types do not contain type of json value.');
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
     *
     * @throws \Tjson\Exception\NoMatchingTypeDefinitionException
     */
    private function nullCheck($jsonValueType, bool $nullable): void
    {
        if (\Tjson\Util\PropertyUtil::doTypesMatch($jsonValueType, 'NULL') === true && $nullable === false) {
            throw new \Tjson\Exception\NoMatchingTypeDefinitionException('Defined types do not contain type of json value.');
        }
    }


    /**
     * @return array<\Tjson\Decoding\AbstractedType>
     * @throws \Tjson\Exception\NoMatchingTypeDefinitionException
     */
    private function getTypes(\ReflectionProperty $reflectedProperty): array
    {
        /**
         * @var \Tjson\Attributes\MappedPropertyClass $mappedPropertyClassInstance
         */
        $types            = [];
        $intersectionType = false;
        if ($reflectedProperty->hasType() === true) {
            $type = $reflectedProperty->getType();

            if ($type instanceof \ReflectionNamedType === true) {
                $types[] = (new \Tjson\Decoding\AbstractedType($type->getName(), $type->isBuiltin()));
            }

            if ($type instanceof \ReflectionUnionType === true) {
                foreach ($type->getTypes() as $type) {
                    $types[] = (new \Tjson\Decoding\AbstractedType($type->getName(), $type->isBuiltin()));
                }
            }

            if ($type instanceof \ReflectionIntersectionType === true) {
                $intersectionType = true;
                foreach ($type->getTypes() as $type) {
                    $types[] = (new \Tjson\Decoding\AbstractedType($type->getName(), $type->isBuiltin()));
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
                    throw new \Tjson\Exception\NoMatchingTypeDefinitionException('Mapped property class is not matching given type.');
                }
            }
            if (count($types) > 1) {
                if ($intersectionType === false) {
                    $foundMatching = false;
                    foreach ($types as $type) {
                        $typeName = $type->getName();
                        if ($mappedType instanceof $typeName === true) {
                            $foundMatching = true;
                        }
                    }
                    if ($foundMatching === false) {
                        throw new \Tjson\Exception\NoMatchingTypeDefinitionException('Could not find any matching type definition for ');
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
                        throw new \Tjson\Exception\NoMatchingTypeDefinitionException('The mapped property class \'' . $mappedType . '\' does not match the intersection type.');
                    }
                }
            }
        }

        return $types;
    }


    private function getMappedType(\ReflectionProperty $reflectedProperty): ?string
    {
        $mappedType = null;

        $reader     = new \Doctrine\Common\Annotations\AnnotationReader();
        $annotation = $reader->getPropertyAnnotation($reflectedProperty, \Tjson\Attributes\MappedPropertyClass::class);
        if ($annotation instanceof \Tjson\Attributes\MappedPropertyClass === true) {
            $mappedType = $annotation->getClass();
        }

        if (\Tjson\Util\VersionUtil::isPhp8OrNewer() === true) {
            $mappedPropertyClassAttributes = $reflectedProperty->getAttributes(\Tjson\Attributes\MappedPropertyClass::class);
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