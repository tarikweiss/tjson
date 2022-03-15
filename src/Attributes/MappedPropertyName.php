<?php

namespace Tarikweiss\Tjson\Attributes;

/**
 * Class MappedPropertyName
 *
 * @package Tarikweiss\Tjson\Attributes
 * @Annotation
 * @\Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor()
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class MappedPropertyName
{
    /**
     * @var string 
     */
    private string $name;


    /**
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }


    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
}