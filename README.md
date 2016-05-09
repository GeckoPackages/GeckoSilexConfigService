#### GeckoPackages

# Silex config service

A service provider for [ Silex ](http://silex.sensiolabs.org) for loading configuration files.
Supported formats:
* `json`
* `yml` (`yaml`)
* `php`

### Requirements

PHP 5.5.0

### Install

The package can be installed using [ Composer ](https://getcomposer.org/).
Add the package to your `composer.json`.

```
"require": {
    "gecko-packages/gecko-silex-config-service" : "1.1"
}
```

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
        array(
            'config.dir' => __DIR__.'/config',
            'config.format' => '%key%.%env%.json',
            'config.env' => 'dev',
            'config.cache' => 'memcached',
        )
);

// This will read: `'__DIR__.'/config/database.dev.json` and returns an array with values.
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

* `config.env`
  The value of the `environment`, this is (optional) used to replace the
  `%env%` placeholder in the file format. [`string`], default `null`.

## Runtime options

The following methods can be used to get or change properties of the service.

```php
// returns the current configuration directory
$app['config']->getDir()

// change the configuration directory to read from
$app['config']->setDir($dir)

// change the value used in the replacement of `%env%` in the `format`.
$app['config']->setEnvironment($env)

// change the format used to transform a key to a file name
$app['config']->setFormat($format)

// change the caching service to use (`null` to disable)
$app['config']->setCache($name)

// when need the service can be called directly to flush
// config values from the cache
$app['config']->flushConfig($key)

// flush all known items
$app['config']->flushAll()
```

## Twig example

When using [ Twig ](http://twig.sensiolabs.org/) in combination with a config file with key `foo` which holds the data:
```php
// when read and parsed by the config loader
array("bar" => "test")
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
$app->register(new ConfigServiceProvider('config.database'), array('config.database.dir' => $configDatabaseDir));

// second service
$app->register(new ConfigServiceProvider('config.test'), array('config.test.dir' => $configTestDir));

// usage
$app['config.database']->get('db.user.name');

```

### License

The project is released under the MIT license, see the LICENSE file.
