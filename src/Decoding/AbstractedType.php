<?php

namespace Tjson\Decoding;

/**
 * Class AbstractedType
 *
 * @package Tjson\Decoding
 */
class AbstractedType
{
    private string $name;

    private bool $builtin;


    /**
     * @param string $name
     * @param bool   $builtIn
     */
    public function __construct(string $name, bool $builtIn)
    {
        $this->name    = $name;
        $this->builtin = $builtIn;
    }


    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }


    /**
     * @param string $name
     *
     * @return AbstractedType
     */
    public function setName(string $name): AbstractedType
    {
        $this->name = $name;

        return $this;
    }


    /**
     * @return bool
     */
    public function isBuiltin(): bool
    {
        return $this->builtin;
    }


    /**
     * @param bool $builtin
     *
     * @return AbstractedType
     */
    public function setBuiltin(bool $builtin): AbstractedType
    {
        $this->builtin = $builtin;

        return $this;
    }
}