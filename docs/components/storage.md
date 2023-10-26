## KV Storage

[![Static Badge](https://img.shields.io/badge/Doxygen-API%20Documentation-purple)](https://crispcms.jrbit.de/api/d9/d4c/classcrisp_1_1api_1_1_config.html)

Crisp features a built-in Storage Controller designed to efficiently save your data in a structured Key/Value (KV) format within its PostgreSQL database.


### KV Get 

[![Static Badge](https://img.shields.io/badge/Doxygen-Reference-cyan)](https://crispcms.jrbit.de/api/d9/d4c/classcrisp_1_1api_1_1_config.html#aa529c4051ad0e8399e7ab71dd04f3e0c)

!> Retrieving a Key/Value (KV) employs a caching system with a default Time-to-Live (TTL) of 120 seconds.

Example:
<!-- tabs:start -->

#### **PHP**

```php
$myAwesomeVariable = Config::get("my_awesome_variable");
```


#### **Twig**

```twig
{{ config.my_awesome_variable }}
```

<!-- tabs:end -->



### KV Set 
[![Static Badge](https://img.shields.io/badge/Doxygen-Reference-cyan)](https://crispcms.jrbit.de/api/d9/d4c/classcrisp_1_1api_1_1_config.html#a74033a1a090875e7716e5541c111d7ce)

Setting a Key-Value (KV) pair updates the provided value and creates the key if it doesn't already exist.

The Serializer is versatile and accommodates various data types, including but not limited to:

- Objects
- Arrays
- Booleans
- Numbers
- Strings
- Serializable Classes

Example:
<!-- tabs:start -->

#### **PHP**
```php
$stringVarIsSet = Config::set("my_awesome_variable", "Hello World!");
$arrayVarIsSet = Config::set("my_awesome_variable", [1234]);
$boolVarIsSet = Config::set("my_awesome_variable", true);
$intVarIsSet = Config::set("my_awesome_variable", 1234);
```
<!-- tabs:end -->

### KV Exists 

Verify the existence of a Key-Value (KV) pair in the database.

[![Static Badge](https://img.shields.io/badge/Doxygen-Reference-cyan)](https://crispcms.jrbit.de/api/d9/d4c/classcrisp_1_1api_1_1_config.html#a7b112ef5037b56373fa735d7b11b1c1e)

Example:

<!-- tabs:start -->

#### **PHP**
```php
$doesExist = Config::exists("my_awesome_variable");
```
<!-- tabs:end -->

### KV Delete 

Remove a Key-Value (KV) pair from the database if it exists.

[![Static Badge](https://img.shields.io/badge/Doxygen-Reference-cyan)](https://crispcms.jrbit.de/api/d9/d4c/classcrisp_1_1api_1_1_config.html#a50988735f04237d816d027249c9d9d25)


Example:
<!-- tabs:start -->

#### **PHP**
```php
$isDeleted = Config::delete("my_awesome_variable");
```
<!-- tabs:end -->

## Pre-Defined KV

The [[/themes/json|theme.json]] file empowers you to pre-define Key-Value (KV) pairs in the `onInstall.createKVStorageItems` section. You have the flexibility to pass arrays or objects into the JSON to configure KV Storage during container boot-up.

```json5
{
  ...
  "onInstall": {
    ...
    "createKVStorageItems": {
      "site_name": "My Awesome Site"
    }
  },
  ...
}
```