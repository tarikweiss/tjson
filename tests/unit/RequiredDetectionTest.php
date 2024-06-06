<?php

/**
 * Class RequiredDetectionTest
 */
class RequiredDetectionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return void
     * @throws \ReflectionException
     * @throws \Tjson\Exception\AmbiguousNameDefinitionException
     * @throws \Tjson\Exception\AmbiguousTypeDefinitionException
     * @throws \Tjson\Exception\ClassNotFoundException
     * @throws \Tjson\Exception\NoMatchingTypeDefinitionException
     * @throws \Tjson\Exception\RequiredPropertyNotFoundException
     * @dataProvider requiredDetectionOnDecodeDataProvider
     */
    public function testRequiredDetectionOnDecode(string $json, bool $expectException)
    {
        $testObject = new TestObject();

        $jsonDecoder = new \Tjson\JsonDecoder();

        if (true === $expectException) {
            $this->expectException(\Tjson\Exception\RequiredPropertyNotFoundException::class);
        }

        $jsonDecoder->decodeByObject($json, $testObject);

        if (false === $expectException) {
            $this->assertTrue(property_exists($testObject, 'notRequired'));
            $this->assertTrue(property_exists($testObject, 'explicitRequired'));
            $this->assertTrue(property_exists($testObject, 'implicitRequired'));
        }
    }


    /**
     * @return \string[][]
     */
    public function requiredDetectionOnDecodeDataProvider(): array
    {
        return [
            [
                '{}',
                true,
            ],
            [
                '{"implicitRequired": "foo"}',
                true,
            ],
            [
                '{"explicitRequired": false}',
                true,
            ],
        ];
    }
}

class TestObject
{
    private $notRequired;

    /**
     * @\Tjson\Attributes\Required(required = true)
     */
    private        $explicitRequired;

    private string $implicitRequired;


    public function getNotRequired(): string
    {
        return $this->notRequired;
    }


    public function setNotRequired(string $notRequired): TestObject
    {
        $this->notRequired = $notRequired;

        return $this;
    }


    public function getExplicitRequired(): string
    {
        return $this->explicitRequired;
    }


    public function setExplicitRequired(string $explicitRequired): TestObject
    {
        $this->explicitRequired = $explicitRequired;

        return $this;
    }


    public function getImplicitRequired(): string
    {
        return $this->implicitRequired;
    }


    public function setImplicitRequired(string $implicitRequired): TestObject
    {
        $this->implicitRequired = $implicitRequired;

        return $this;
    }
}