<?php

/*
 * This file is part of the GeckoPackages.
 *
 * (c) GeckoPackages https://github.com/GeckoPackages
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace GeckoPackages\Silex\Services\Config\Tests;

use GeckoPackages\Silex\Services\Config\ConfigServiceProvider;
use Silex\Application;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Exception\IOException;

/**
 * @internal
 *
 * @author SpacePossum
 */
final class ConfigServiceProviderTest extends AbstractConfigTest
{
    public function testServiceRegisterNaming()
    {
        $app = new Application();
        $app['debug'] = true;

        $configDatabaseDir = realpath(__DIR__.'/../../assets').'/';
        $app->register(new ConfigServiceProvider('config.database'), ['config.database.dir' => $configDatabaseDir]);

        $configTest = realpath(__DIR__.'/../../Config').'/';
        $app->register(new ConfigServiceProvider('config.test'), ['config.test.dir' => $configTest]);

        $this->assertFalse(isset($app['config']));

        $this->assertTrue(isset($app['config.database']));
        $this->assertInstanceOf('GeckoPackages\Silex\Services\Config\ConfigLoader', $app['config.database']);
        $this->assertSame($configDatabaseDir, $app['config.database']->getDir());

        $this->assertTrue(isset($app['config.test']));
        $this->assertInstanceOf('GeckoPackages\Silex\Services\Config\ConfigLoader', $app['config.test']);
        $this->assertSame($configTest, $app['config.test']->getDir());
    }

    public function testPHPConfig()
    {
        $configValue = ['test' => 1, 'lvl2' => ['20' => 'two zero', 21 => 'two one']];
        $app = new Application();
        $app['debug'] = true;
        $this->setupConfigService($app, '%key%.%env%.php.dist', null, 'unitTest');
        for ($i = 0; $i < 2; ++$i) {
            $config = $app['config']->get('__conf');
            $this->assertInternalType('array', $config);
            $this->assertNotEmpty($config);
            $this->assertSame($configValue, $config);
        }

        // Test using dynamic Object Properties, ie. the `magic` `__get` and `__set` methods
        $this->assertTrue($app['config']->__isset('__conf'));
        // test that no exception is thrown but false is returned
        $this->assertFalse($app['config']->__isset('__conf_invalid__'));
        $this->assertSame($configValue, $app['config']->__get('__conf'));
    }

    public function testYamlConfig()
    {
        $app = new Application();
        $app['debug'] = true;
        $this->setupConfigService($app, '%key%.json');
        $app['config']->setEnvironment('dev');
        $app['config']->setFormat('%key%.%env%.yml');

        $this->assertSame(
            [
                'foo' => 'bar',
                'leveled' => [
                    'one' => 1,
                    'two' => 'second',
                    '3' => 1,
                ],
            ],
            $app['config']->get('test')
        );

        // test multiple files to see if the parser re use is OK
        $this->assertSame(
            [
                'bar' => 'foo',
                'home' => 'here',
            ],
            $app['config']->get('test2')
        );

        // test array access
        $this->assertArrayHasKey('bar', $app['config']['test2']);
        $this->assertSame('foo', $app['config']['test2']['bar']);
    }

    public function testJSONConfig()
    {
        $app = new Application();
        $app['debug'] = true;
        $this->setupConfigService($app);

        $this->assertSame(
            ['options' => ['test' => ['driver' => 'pdo_mysql']]],
            $app['config']->get('test')
        );

        // simple flush test
        $app['config']->flushConfig('test');

        // test flush unknown key, shouldn't be a problem
        $app['config']->flushConfig('test1');
    }

    public function testDirSwapping()
    {
        $app = new Application();
        $app['debug'] = true;
        $this->setupConfigService($app);

        $this->assertSame(
            ['options' => ['test' => ['driver' => 'pdo_mysql']]],
            $app['config']->get('test')
        );

        $dir = $app['config']->getDir().'../config2';
        $app['config']->setDir($dir);

        $this->assertSame(
            ['options' => ['test2' => 'new_dir']],
            $app['config']->get('test')
        );

        $this->assertTrue($app['config']->offsetExists('test'));
        $this->assertFalse($app['config']->offsetExists('test123'));
    }

    /**
     * @requires PHPUnit 5.2
     */
    public function testDirNotValidException()
    {
        $this->expectException(IOException::class);
        $this->expectExceptionMessageRegExp('#^Config "/a/b/c/" is not a directory.$#');

        $app = new Application();
        $app['debug'] = true;
        $app->register(new ConfigServiceProvider(), ['config.dir' => null]); // null is a valid value upon creation
        $app['config']->setDir('/a/b/c/');
    }

    /**
     * @requires PHPUnit 5.2
     *
     * @param string $format
     *
     * @dataProvider provideFormats
     */
    public function testConfigFileNotFoundException($format)
    {
        $this->expectException(FileNotFoundException::class);
        $this->expectExceptionMessageRegExp('#^Config file not found ".*".$#');

        $app = new Application();
        $app['debug'] = true;
        $this->setupConfigService($app);
        $app['config']->setFormat($format);
        $app['config']->get('test_not_found');
    }

    /**
     * @requires PHPUnit 5.2
     *
     * @param string $format
     *
     * @dataProvider provideFormats
     */
    public function testFileFormatException($format)
    {
        $this->expectException(\RuntimeException::class);

        $app = new Application();
        $app['debug'] = true;
        $this->setupConfigService($app);
        $app['config']->setFormat($format);
        $app['config']->get('invalid');
    }

    public function provideFormats()
    {
        $cases = [
            ['%key%.json'],
            ['%key%.yml'],
            ['%key%.php'],
        ];

        return $cases;
    }

    /**
     * @requires PHPUnit 5.2
     */
    public function testFileFormatNotSupportedException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('#^Unsupported file format "xls".$#');

        $app = new Application();
        $app['debug'] = true;
        $this->setupConfigService($app);
        $app['config']->setFormat('%key%.xls');
    }

    /**
     * @requires PHPUnit 5.2
     */
    public function testFileFormatMissingKeyException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('#^Format must contain "%key%", got ".xls".$#');

        $app = new Application();
        $app['debug'] = true;
        $this->setupConfigService($app);
        $app['config']->setFormat('.xls');
    }

    /**
     * @requires PHPUnit 5.2
     */
    public function testFileFormatNotStringException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('#^Format must be a string, got "NULL".$#');

        $app = new Application();
        $app['debug'] = true;
        $this->setupConfigService($app);
        $app['config']->setFormat(null);
    }

    /**
     * @requires PHPUnit 5.2
     */
    public function testFileFormatNoExtensionException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('#^Format missing extension, got "%key%json".$#');

        $app = new Application();
        $app['debug'] = true;
        $this->setupConfigService($app);
        $app['config']->setFormat('%key%json');
    }

    /**
     * @requires PHPUnit 5.2
     */
    public function testJsonNotArray()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessageRegExp('#^Expected array as configuration, got: "integer", in ".*integer.json".$#');

        $app = new Application();
        $app['debug'] = true;
        $this->setupConfigService($app);
        $app['config']->get('integer');
    }
}
