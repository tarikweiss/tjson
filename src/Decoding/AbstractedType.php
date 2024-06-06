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


    public function __construct(string $name, bool $builtIn)
    {
        $this->name    = $name;
        $this->builtin = $builtIn;
    }


    public function getName(): string
    {
        return $this->name;
    }


    public function setName(string $name): AbstractedType
    {
        $this->name = $name;

        return $this;
    }


    public function isBuiltin(): bool
    {
        return $this->builtin;
    }


    public function setBuiltin(bool $builtin): AbstractedType
    {
        $this->builtin = $builtin;

        return $this;
    }
}