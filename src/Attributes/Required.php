<?php

namespace Tjson\Attributes;

/**
 * Class Required
 *
 * @package Tjson\Attributes
 * @Annotation
 * @\Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor()
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Required
{
    public bool $required;


    public function __construct(bool $required)
    {
        $this->required = $required;
    }


    public function isRequired(): bool
    {
        return $this->required;
    }
}