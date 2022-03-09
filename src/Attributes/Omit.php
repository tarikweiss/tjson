<?php

namespace Tarikweiss\Tjson\Attributes;

/**
 * Class Omit
 *
 * @package Tarikweiss\Tjson\Attributes
 */
#[\Attribute]
class Omit
{
    private bool $omit;


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