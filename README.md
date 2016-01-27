PHP Advini
==========

Advanced INI file reader for PHP.

Problem:

You cannot define and read deeper array structures with internal "parse_ini_file" implementation:

```ini
[group1]
property1 = value1
property2 = value2
property3 = value3

[group2]
property1 = value1
property2 = value2
property3 = value3
```

"Advini" extends the internal "parse_ini_file" with several ingredients.

It supports:

- defining complex keys and sections

	```ini
	category/property = value
	```

- importing any INI file

	```ini
	property = @import[ default.ini ]
	```

- including constants (from INI file)

	```ini
	property = << key >> 
	```

- calling methods before setting

	```ini
	property:sha1 = geheim1234 
	```


Defining complex keys and sections
----------------------------------

Usage:
```ini
[{key1}/{key2}(...)]
key3/key4(...) = value
```

PHP:
```php
use JBR\Advini\Advini;

$ini = new Advini();

$configuration = $ini->getFromFile("local.ini");
var_dump($configuration);
```

INI:
```ini
[category/subcategory]
key = value
```

Resulted output:
```text
array(
    "category" => array(
        "subcategory" => array(
            "key" => "value"
        )
    )
)
```


Importing any INI file
----------------------

Usage:
```ini
{key} = @import[ [file] ]
```


PHP:
```php
use JBR\Advini\Advini;
 
$ini = new Advini();
$configuration = $ini->getFromFile("local.ini");
var_dump($configuration);
```

INI "local.ini":
```ini
category = @import[ import.ini ]
```

INI "import.ini":
```ini
[subcategory]
key = value
```

Resulted output:
```text
array(
    "category" => array(
        "subcategory" => array(
            "key" => "value"
        )
    )
)
```


Including constants
-------------------

Usage:
```ini
{key} = << [constant] >>
```

PHP:
```php
use JBR\Advini\Advini;
use JBR\Advini\Instructor\ConstantInstructor;

$ini = new Advini();
$const = $ini->getInstructor(ConstantInstructor::class);
$const->setConstantsFromFile("constants.ini");
$configuration = $ini->getFromFile("local.ini");
var_dump($configuration);
```

INI "constants.ini":
```ini
[category/subcategory]
key = value
```

INI "local.ini":
```ini
[category/subcategory]
key = << key >>
```

Resulted output:
```text
array(
    "category" => array(
        "subcategory" => array(
            "key" => "value"
        )
    )
)
```

Calling methods before setting
------------------------------

Usage:
```ini
{key}:{method}(...) = {value}
```


PHP:
```php
use JBR\Advini\Advini;
use JBR\Advini\Methods\Base;

$ini = new Advini(new Base());

try {
    $configuration = $ini->getFromFile("local.ini");
    var_dump($configuration);
} catch (AdviniException $e) {
    echo $e->getMessage();
}
```

INI "local.ini":
```ini
[category/subcategory]
key1:integer = "foobar"
key2:string = 123
key3:md5 = "secret"
```

Resulted output:
```text
array(
    "category" => array(
        "subcategory" => array(
            "key1" => 0,
            "key2" => "123",
            "key3" => "5ebe2294ecd0e0f08eab7690d2a6ee69"
        )
    )
)
```

Or calling by sections:

Usage:
```ini
[{section}:{method}]
{key} = {value}
```

INI "local.ini":
```ini
[category/subcategory:serialize]
key1:integer = "foobar"
key2:string = 123
key3:md5 = "secret"
```

Resulted output:
```text
array(
    "category" => array(
        "subcategory" => "a:3:{s:4:"key1";i:0;s:4:"key2";s:3:"123";s:4:"key3";s:32:"5ebe2294ecd0e0f08eab7690d2a6ee69";}"
    )
)
```
