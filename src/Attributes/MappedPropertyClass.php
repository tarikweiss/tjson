<?php

namespace Tjson\Attributes;

/**
 * Class MappedPropertyClass
 * Use this attribute to specify, which class should be used for mapping. This is especially needed, when not specifying
 * a type or specifying union type.<br>
 * If only a single type is specified and class name does not match, an exception will be thrown.
 * @package Tjson\Attributes
 * @Annotation
 * @\Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class MappedPropertyClass
{
    private string $class;


    public function __construct(string $class)
    {
        $this->class = $class;
    }


    public function getClass(): string
    {
        return $this->class;
    }


    public function setClass(string $class): void
    {
        $this->class = $class;
    }
}