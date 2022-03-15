<?php

/**
 * Class JsonDecoderTest
 */
class JsonDecoderTest extends \PHPUnit\Framework\TestCase
{
    public function testDecode()
    {
        $object               = (new stdClass());
        $object->intProperty  = 10;
        $object->boolProperty = false;

        $jsonString = '{"int":10,"bool":true,"string":"Some fantastic string!","float":17.5,"array":[1,2,3,4,5,6],"object":{"intProperty":10,"boolProperty":false}}';
        $decoder    = (new \Tarikweiss\Tjson\JsonDecoder());
        $foo        = $decoder->decodeByClassName($jsonString, Foo::class);
        $this->assertInstanceOf(Foo::class, $foo);
        $this->assertSame(10, $foo->getInt());
        $this->assertSame(true, $foo->isBool());
        $this->assertSame("Some fantastic string!", $foo->getString());
        $this->assertSame(17.5, $foo->getFloat());
        $this->assertSame([1, 2, 3, 4, 5, 6], $foo->getArray());
        $this->assertEquals($object, $foo->getObject());
    }


    /**
     * @dataProvider decodeFailsOnNoMatchingTypeDefinitionDataProvider
     * @return void
     * @throws \ReflectionException
     * @throws \Tarikweiss\Tjson\Exception\AmbiguousNameDefinitionException
     * @throws \Tarikweiss\Tjson\Exception\AmbiguousTypeDefinitionException
     * @throws \Tarikweiss\Tjson\Exception\ClassNotFoundException
     * @throws \Tarikweiss\Tjson\Exception\NoMatchingTypeDefinitionException
     * @throws \Tarikweiss\Tjson\Exception\RequiredPropertyNotFoundException
     */
    public function testDecodeFailsOnNoMatchingTypeDefinition($jsonString, $field)
    {
        $decoder = (new \Tarikweiss\Tjson\JsonDecoder());
        $this->expectException(\Tarikweiss\Tjson\Exception\NoMatchingTypeDefinitionException::class);
        $this->expectExceptionMessage('No matching type found for json property \'' . $field . '\'');
        $decoder->decodeByClassName($jsonString, Foo::class);
    }


    /**
     * @return \string[][]
     */
    public function decodeFailsOnNoMatchingTypeDefinitionDataProvider(): array
    {
        return [
            [
                '{"int":"10","bool":true,"string":"Some fantastic string!","float":17.5,"array":[1,2,3,4,5,6],"object":{"intProperty":10,"boolProperty":false}}',
                'int',
            ],
            [
                '{"int":10,"bool":"true","string":"Some fantastic string!","float":17.5,"array":[1,2,3,4,5,6],"object":{"intProperty":10,"boolProperty":false}}',
                'bool',
            ],
            [
                '{"int":10,"bool":true,"string":10,"float":17.5,"array":[1,2,3,4,5,6],"object":{"intProperty":10,"boolProperty":false}}',
                'string',
            ],
            [
                '{"int":10,"bool":true,"string":"Some fantastic string!","float":17,"array":[1,2,3,4,5,6],"object":{"intProperty":10,"boolProperty":false}}',
                'float',
            ],
            [
                '{"int":10,"bool":true,"string":"Some fantastic string!","float":17.5,"array":"just an easy string haha!","object":{"intProperty":10,"boolProperty":false}}',
                'array',
            ],
            [
                '{"int":10,"bool":true,"string":"Some fantastic string!","float":17.5,"array":[1,2,3,4,5,6],"object":["instead", "an", "array"]}',
                'object',
            ],
        ];
    }
}

class Foo
{
    private int    $int;

    private bool   $bool;

    private string $string;

    private float  $float;

    private array  $array;

    private object $object;
    

    /**
     * @return int
     */
    public function getInt(): int
    {
        return $this->int;
    }


    /**
     * @param int $int
     *
     * @return Foo
     */
    public function setInt(int $int): Foo
    {
        $this->int = $int;

        return $this;
    }


    /**
     * @return bool
     */
    public function isBool(): bool
    {
        return $this->bool;
    }


    /**
     * @param bool $bool
     *
     * @return Foo
     */
    public function setBool(bool $bool): Foo
    {
        $this->bool = $bool;

        return $this;
    }


    /**
     * @return string
     */
    public function getString(): string
    {
        return $this->string;
    }


    /**
     * @param string $string
     *
     * @return Foo
     */
    public function setString(string $string): Foo
    {
        $this->string = $string;

        return $this;
    }


    /**
     * @return float
     */
    public function getFloat(): float
    {
        return $this->float;
    }


    /**
     * @param float $float
     *
     * @return Foo
     */
    public function setFloat(float $float): Foo
    {
        $this->float = $float;

        return $this;
    }


    /**
     * @return array
     */
    public function getArray(): array
    {
        return $this->array;
    }


    /**
     * @param array $array
     *
     * @return Foo
     */
    public function setArray(array $array): Foo
    {
        $this->array = $array;

        return $this;
    }


    /**
     * @return object
     */
    public function getObject(): object
    {
        return $this->object;
    }


    /**
     * @param object $object
     *
     * @return Foo
     */
    public function setObject(object $object): Foo
    {
        $this->object = $object;

        return $this;
    }
}