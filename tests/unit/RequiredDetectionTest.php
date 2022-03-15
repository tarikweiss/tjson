<?php

/**
 * Class RequiredDetectionTest
 */
class RequiredDetectionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @param string $json
     * @param bool   $expectException
     *
     * @return void
     * @throws \ReflectionException
     * @throws \Tarikweiss\Tjson\Exception\AmbiguousNameDefinitionException
     * @throws \Tarikweiss\Tjson\Exception\AmbiguousTypeDefinitionException
     * @throws \Tarikweiss\Tjson\Exception\ClassNotFoundException
     * @throws \Tarikweiss\Tjson\Exception\NoMatchingTypeDefinitionException
     * @throws \Tarikweiss\Tjson\Exception\RequiredPropertyNotFoundException
     * @dataProvider requiredDetectionOnDecodeDataProvider
     */
    public function testRequiredDetectionOnDecode(string $json, bool $expectException)
    {
        $testObject = new TestObject();

        $jsonDecoder = new \Tarikweiss\Tjson\JsonDecoder();

        if (true === $expectException) {
            $this->expectException(\Tarikweiss\Tjson\Exception\RequiredPropertyNotFoundException::class);
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
    /**
     * @var string
     */
    private $notRequired;

    /**
     * @var string
     * @\Tarikweiss\Tjson\Attributes\Required(required = true)
     */
    private $explicitRequired;

    /**
     * @var string
     */
    private string $implicitRequired;


    /**
     * @return string
     */
    public function getNotRequired(): string
    {
        return $this->notRequired;
    }


    /**
     * @param string $notRequired
     *
     * @return TestObject
     */
    public function setNotRequired(string $notRequired): TestObject
    {
        $this->notRequired = $notRequired;

        return $this;
    }


    /**
     * @return string
     */
    public function getExplicitRequired(): string
    {
        return $this->explicitRequired;
    }


    /**
     * @param string $explicitRequired
     *
     * @return TestObject
     */
    public function setExplicitRequired(string $explicitRequired): TestObject
    {
        $this->explicitRequired = $explicitRequired;

        return $this;
    }


    /**
     * @return string
     */
    public function getImplicitRequired(): string
    {
        return $this->implicitRequired;
    }


    /**
     * @param string $implicitRequired
     *
     * @return TestObject
     */
    public function setImplicitRequired(string $implicitRequired): TestObject
    {
        $this->implicitRequired = $implicitRequired;

        return $this;
    }
}