# tjson ![GitHub](https://img.shields.io/github/license/tarikweiss/tjson?style=for-the-badge)
Map json strings into objects and vice versa.

## Usage
This library contains an encoder and decoder for mapping objects into json strings and vice versa.
It will map all properties (including private and protected), as long they are not marked to be not converted.

### Sample object class
The properties can be configured with doctrine annotations or the new PHP 8 attributes.
A full documentation of the annotations/attributes is following.
```php
class ClassToConvert
{
    #[\Tarikweiss\Tjson\Attributes\MappedPropertyName(name: 'public_item')]
    public $publicItem;
    
    protected $protectedItem;
    
    private $privateItem;
    
    #[\Tarikweiss\Tjson\Attributes\MappedPropertyClass(class: ClassC::class)]
    private ClassA|ClassB|ClassC $typedItems;
    
    /**
     * @\Tarikweiss\Tjson\Attributes\Required(required = true)
     */
    private $anotherProperty = 'withValue';
    
    // Class getters and setters
}
```

### Basic Usage
#### JSON
This json is assumed as value for `$jsonString` for the following samples:
````json
{
  "public_item": "Value of public item",
  "protectedItem": 1337,
  "privateItem": null,
  "typedItems": {
    "property1": "foo",
    "property2": "bar"
  },
  "anotherProperty": "anotherValue"
}
````
#### Decoder
Decoding is done as follows:
```php
$jsonDecoder = new \Tarikweiss\Tjson\JsonDecoder();

$yourClassInstance = $jsonDecoder->decodeByClassName($jsonString, \Your\Class::class)
echo $yourClassInstance->getProtectedItem() // 1337
```

#### Encoder
Encoding is done as follows:
```php
$jsonEncoder = new \Tarikweiss\Tjson\JsonEncoder();

$jsonString = $jsonEncoder->encode($yourClassInstance);
```

### Attributes/Annotations
You can use the attributes/annotations to control the behaviour for encoding or decoding.
Attributes are always over the doctrine annotations in the hierarchy. That means, if you define something as annotation
and as attribute, then the attribute will win.

#### MappedPropertyClass
This annotation is used for setting a specific class on decoding. It needs to match a type, if given, otherwise an exception
is thrown.

#### MappedPropertyName
This is used to change the name of the property for encoding/decoding. For example, you can use `foo_bar` as the json
property name but use `fooBar` as your property name in your object/class. But it could also contain a completely different
name. If the name defined is occurring multiple times then an exception is thrown. For example, you have to properties,
called `foo` and `bar` in your class, and you annotate `foo` with this annotation and the value `bar`, then you have
defined the name `bar` multiple times, which leads to an exception.

#### Omit
This annotation *explicitly* disables the mapping of a property in both directions, meaning if the property exists in the json
it would be ignored on decoding and if it exists on encoding it would not be in the json.<br>
**CAUTION** This annotation *overrides* the duplication check for this annotation, as it is not considered for mapping.

#### Required
This property is used to define something as required. This is especially used for untyped properties, meaning a
typed property will always be required by default.<br>
**CAUTION** You may use this annotation to disable the required state of a property, also if it is typed, but that would mean,
that properties stay uninitialized and may cause illegal access (for example using getters).