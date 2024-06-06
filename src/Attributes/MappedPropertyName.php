<?php

namespace Tjson\Attributes;

/**
 * Class MappedPropertyName
 *
 * @package Tjson\Attributes
 * @Annotation
 * @\Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor()
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class MappedPropertyName
{
    private string $name;


    public function __construct(string $name)
    {
        $this->name = $name;
    }


    public function getName(): string
    {
        return $this->name;
    }
}