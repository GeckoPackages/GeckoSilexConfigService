#### GeckoPackages

# Silex config service

A service provider for [ Silex ](http://silex.sensiolabs.org) for loading configuration files.
Supported formats:
* `json`
* `yml` (`yaml`)
* `php`

### Requirements

PHP 7.0 / Silex 2<br/>
<sub>See `Install` for more supported versions.</sub>

### Install

The package can be installed using [ Composer ](https://getcomposer.org/).
Add the package to your `composer.json`.

```
"require": {
    "gecko-packages/gecko-silex-config-service" : "^3.0"
}
```

<sub>Use `^v2.1` if you are using Silex 2.x with PHP 5.5.</sub>
<sub>Use `^v1.1` if you are using Silex 1.x.</sub>

## Usage

The provider will find and read configuration files in a directory. The files are found
using; a `directory`, a `format` and a `key`.

The `format` is a `string` which can have an optional placeholder `%env%` (`environment`),
for example: `'%key%.%env%.json'`

The `key` is the configuration key to be used, usually the file name without the extension.

Typically the directory and format are set during registering of the service and the
`key` is used for fetching the configuration.

## Example

```php
$app->register(
    new GeckoPackages\Silex\Services\Config\ConfigServiceProvider(),
    [
        'config.dir' => __DIR__.'/config',
        'config.format' => '%key%.%env%.json',
        'config.env' => 'dev',
        'config.cache' => 'memcached',
    ]
);

// This will read: `'__DIR__.'/config/database.dev.json` and returns the decoded json.
$app['config']->get('database');

// array access is also supported
$app['config']['database'];
```

## Options

The service takes the following options:

* `config.dir`
  Directory where the configuration files should be loaded from. [`string`]

* `config.format`
  Format to translate a `key` to a `file`. The default is `%key%.json`.
  `%env%` placeholder is supported. [`string`]

* `config.cache`
  Name under which a caching service is registered on the application.
  The default is `null`, which means caching is disabled. [`string`]
  The service must be [PSR-16](https://github.com/php-fig/simple-cache/blob/1.0.0/src/CacheInterface.php) compliant with respect to the `get`, `set` and `delete` methods
  (however it does not have to explicitly implement the PSR-16 interface).

* `config.env`
  The value of the `environment`, this is (optional) used to replace the
  `%env%` placeholder in the file format. [`string`], default `null`.

## Runtime options

The following methods can be used to get or change properties of the service at runtime (for example after registering).

```php
// returns the current configuration directory
$app['config']->getDir()

$app['config']
    ->setDir($dir)         // change the configuration directory to read from
    ->setEnvironment($env) // change the value used in the replacement of `%env%` in the `format`.
    ->setFormat($format)   // change the format used to transform a key to a file name
    ->setCache($name)      // change the caching service to use (`null` to disable)
;

// the service can be called directly to flush config values from the cache
$app['config']->flushConfig($key)

// flush all known items
$app['config']->flushAll()
```

## Twig example

When using [ Twig ](http://twig.sensiolabs.org/) in combination with a config file with key `foo` which holds the data:
```php
// when read and parsed by the config loader
["bar" => "test"]
```

You can simply get the value like:

```twig
{{ app.config.foo.bar.test }}
```
## Custom name registering / multiple services

You can register the service using a name other than the default name `config`.
The same method can be used to register multiple configuration services.
Pass the name at the constructor of the service and use the same name as prefix for the configuration.
For example:

```php

// first service
$app->register(new ConfigServiceProvider('config_database'), ['config.database.dir' => $configDatabaseDir]);

// second service
$app->register(new ConfigServiceProvider('config_test'), ['config.test.dir' => $configTestDir]);

// usage
$app['config_database']->get('userName');

// in Twig; {{ app.config_database.userName }}

```

### License

The project is released under the MIT license, see the LICENSE file.

### Contributions

Contributions are welcome!

### Semantic Versioning

This project follows [Semantic Versioning](http://semver.org/).

<sub>Kindly note:
We do not keep a backwards compatible promise on code annotated with `@internal`, the tests and tooling (such as document generation) of the project itself
nor the content and/or format of exception/error messages.</sub>
