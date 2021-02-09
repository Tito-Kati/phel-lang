+++
title = "PHP Interop"
weight = 13
+++

## Calling PHP functions

PHP comes with huge set of functions that can be called from Phel by just adding a `php/` prefix to the function name.

```
(php/strlen "test") # Calls PHP's strlen function and evaluates to 4
(php/date "l") # Evaluates to something like "Monday"
```

## PHP class instantiation

```phel
(php/new expr args*)
```

Evaluates `expr` and creates a new PHP class using the arguments. The instance of the class is returned.

```phel
(ns my\module
  (:use \DateTime))

(php/new DateTime) # Returns a new instance of the DateTime class
(php/new DateTime "now") # Returns a new instance of the DateTime class

(php/new "\\DateTimeImmutable") # instantiate a new PHP class from string
```

## PHP method and property call

```phel
(php/-> (methodname expr*))
(php/-> property)
```

Calls a method or property on a PHP object. Both `methodname` and `property` must be symbols and cannot be an evaluated value.

```phel
(ns my\module
  (:use \DateInterval))

(def di (php/new \DateInterval "PT30S"))

(php/-> di (format "%s seconds")) # Evaluates to "30 seconds"
(php/-> di s) # Evaluates to 30
```

## PHP static method and property call

```phel
(php/:: (methodname expr*))
(php/:: property)
```

Same as above, but for static calls on PHP classes.

```phel
(ns my\module
  (:use \DateTimeImmutable))

(php/:: DateTimeImmutable ATOM) # Evaluates to "Y-m-d\TH:i:sP"

# Evaluates to a new instance of DateTimeImmutable
(php/:: DateTimeImmutable (createFromFormat "Y-m-d" "2020-03-22"))

```

## Get PHP-Array value

```phel
(php/aget arr index)
```

Equivalent to PHP's `arr[index] ?? null`.

```phel
(php/aget ["a" "b" "c"] 0) # Evaluates to "a"
(php/aget (php/array "a" "b" "c") 1) # Evaluates to "b"
(php/aget (php/array "a" "b" "c") 5) # Evaluates to nil
```

## Set PHP-Array value

```phel
(php/aset arr index value)
```

Equivalent to PHP's `arr[index] = value`.

## Append PHP-Array value

```phel
(php/apush arr value)
```

Equivalent to PHP's `arr[] = value`.

## Unset PHP-Array value

```phel
(php/aunset arr index)
```

Equivalent to PHP's `unset(arr[index])`.

## `__DIR__` and `__FILE__`

In Phel you can also use PHP Magic Methods `__DIR__` and `__FILE__`. These resolve to the dirname or filename of the Phel file.

```phel
(println __DIR__) # Prints the directory name of the file
(println __FILE__) # Prints the filename of the file
```

## Calling Phel functions from PHP

There are two possible ways to call wrap around your phel functions in PHP classes:

### Manually
You can define your own classes using the `Phel\Runtime\RuntimeFactory\PhelCallerTrait`.
Then you will be able to call any phel function by using the `callPhel` method:
```php
function callPhel(string $namespace, string $definitionName, ...$arguments): mixed
```

### Using the `export` command

You can use the `phel export` command to generate those wrapper classes for you.

To let the command known which functions you want to export you should add
the meta keyword "export" to the function like: `@{:export true}`
```clojure
(defn adder
  @{:export true}
  [a b]
  (+ a b))
```

In addition, it is important to add this "extra phel configuration" options to your composer:
```json
"export": {
    "directories": [
        "src/phel"
    ],
    "namespace-prefix": "PhelGenerated",
    "target-directory": "src/PhelGenerated"
}
```

You can read more about them in the [configuration section](../configuration/#export).
