<?php

namespace Tjson\Attributes;

/**
 * Class Omit
 *
 * @package Tjson\Attributes
 * @Annotation 
 * @\Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor()
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Omit
{
    public bool $omit;


    /**
     * Omit constructor.
     *
     * @param bool $omit
     */
    public function __construct(bool $omit)
    {
        $this->omit = $omit;
    }


    /**
     * @return bool
     */
    public function isOmit(): bool
    {
        return $this->omit;
    }
}