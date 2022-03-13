<?php

namespace Tarikweiss\Tjson\Attributes;

/**
 * Class Required
 *
 * @package Tarikweiss\Tjson\Attributes
 * @Annotation 
 * @\Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor()
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Required
{
    /**
     * @var bool 
     */
    public bool $required;


    /**
     * Required constructor.
     *
     * @param bool $required
     */
    public function __construct(bool $required)
    {
        $this->required = $required;
    }


    /**
     * @return bool
     */
    public function isRequired(): bool
    {
        return $this->required;
    }
}