<?php

namespace Tarikweiss\Tjson\Attributes;

/**
 * Class Required
 *
 * @package Tarikweiss\Tjson\Attributes
 */
class Required
{
    /**
     * @var bool 
     */
    private bool $required;


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