## KV Storage

[![Static Badge](https://img.shields.io/badge/Doxygen-API%20Documentation-purple)](https://api.crispcms.jrbit.de/d9/d4c/classcrisp_1_1api_1_1_config.html)

Crisp has a built in Storage Controller to save your data to its PostgresSQL Database in a Key/Value like structure (KV).


### KV Get 

[![Static Badge](https://img.shields.io/badge/Doxygen-Reference-cyan)](https://api.crispcms.jrbit.de/d9/d4c/classcrisp_1_1api_1_1_config.html#aa529c4051ad0e8399e7ab71dd04f3e0c)

!> Getting a KV makes use of a caching system with a default TTL of 120 seconds.

Example:
```php
/* @returns mixed */
$MyVar = Config::get("my_awesome_variable");
```

### KV Set 
[![Static Badge](https://img.shields.io/badge/Doxygen-Reference-cyan)](https://api.crispcms.jrbit.de/d9/d4c/classcrisp_1_1api_1_1_config.html#a74033a1a090875e7716e5541c111d7ce)

Setting a KV updates the given value and creates the key if it does not exist.

The Serializer supports multiple types:

- objects
- arrays
- booleans
- numbers
- strings

Example:
```php
/* @returns boolean */
$stringVarSet = Config::set("my_awesome_variable", "Hello World!");
$arrayVarSet = Config::set("my_awesome_variable", [1234]);
$boolVarSet = Config::set("my_awesome_variable", true);
$intVarSet = Config::set("my_awesome_variable", 1234);
```

### KV Exists 

Check if a KV exists in the Database.

[![Static Badge](https://img.shields.io/badge/Doxygen-Reference-cyan)](https://api.crispcms.jrbit.de/d9/d4c/classcrisp_1_1api_1_1_config.html#a7b112ef5037b56373fa735d7b11b1c1e)

Example:
```php
/* @returns boolean */
$exists = Config::exists("my_awesome_variable");
```

### KV Delete 

Delete a KV if it exists in the Database.

[![Static Badge](https://img.shields.io/badge/Doxygen-Reference-cyan)](https://api.crispcms.jrbit.de/d9/d4c/classcrisp_1_1api_1_1_config.html#a50988735f04237d816d027249c9d9d25)


Example:
```php
/* @returns boolean */
$deleted = Config::delete("my_awesome_variable");
```
