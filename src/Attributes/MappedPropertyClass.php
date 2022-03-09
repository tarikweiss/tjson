<?php

namespace Tarikweiss\Tjson\Attributes;

/**
 * Class MappedPropertyClass
 * Use this attribute to specify, which class should be used for mapping. This is especially needed, when not specifying
 * a type or specifying union type.<br>
 * If only a single type is specified and class name does not match, an exception will be thrown.
 * @package Tarikweiss\Tjson\Attributes
 */
#[\Attribute]
class MappedPropertyClass
{
    /**
     * @var string 
     */
    private string $class;


    /**
     * MappedPropertyClass constructor.
     *
     * @param string $class
     */
    public function __construct(string $class)
    {
        $this->class = $class;
    }


    /**
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }


    /**
     * @param string $class
     */
    public function setClass(string $class): void
    {
        $this->class = $class;
    }
}