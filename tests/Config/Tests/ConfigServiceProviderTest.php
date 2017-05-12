<?php declare(strict_types=1);

/*
 * This file is part of the GeckoPackages.
 *
 * (c) GeckoPackages https://github.com/GeckoPackages
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace GeckoPackages\Silex\Services\Config\Tests;

use GeckoPackages\Silex\Services\Config\ConfigLoader;
use GeckoPackages\Silex\Services\Config\ConfigServiceProvider;
use Silex\Application;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Exception\IOException;

/**
 * @requires PHPUnit 6.0
 *
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
        $this->assertInstanceOf(ConfigLoader::class, $app['config.database']);
        $this->assertSame($configDatabaseDir, $app['config.database']->getDir());

        $this->assertTrue(isset($app['config.test']));
        $this->assertInstanceOf(ConfigLoader::class, $app['config.test']);
        $this->assertSame($configTest, $app['config.test']->getDir());
    }

    public function testJSONConfig()
    {
        $app = new Application();
        $app['debug'] = true;
        $this->setupConfigService($app);

        // loop so re-fetching after flush is also tested
        for ($i = 0; $i < 2; ++$i) {
            $this->assertSame(
                ['options' => ['test' => ['driver' => 'pdo_mysql']]],
                $app['config']->get('test')
            );

            $this->assertSame(
                $app['config']->get('test'),
                $app['config']['test']
            );

            // simple flush test
            $app['config']->flushConfig('test');

            // test flush unknown key, shouldn't be a problem
            $app['config']->flushConfig('test1');
        }
    }

    public function testJSONConfigUnset()
    {
        $app = new Application();
        $app['debug'] = true;
        $this->setupConfigService($app);

        // loop so re-fetching after unset is also tested
        for ($i = 0; $i < 2; ++$i) {
            $this->assertSame(
                ['options' => ['test' => ['driver' => 'pdo_mysql']]],
                $app['config']['test']
            );

            unset($app['config']['test']); // should not be a problem
        }
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
        $r = $app['config']->setEnvironment('dev');
        $this->assertInstanceOf(ConfigLoader::class, $r);
        $r2 = $app['config']->setFormat('%key%.%env%.yml');
        $this->assertSame($r2, $r);

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
        $r = $app['config']->setDir($dir);
        $this->assertInstanceOf(ConfigLoader::class, $r);

        $this->assertSame(
            ['options' => ['test2' => 'new_dir']],
            $app['config']->get('test')
        );

        $this->assertTrue($app['config']->offsetExists('test'));
        $this->assertFalse($app['config']->offsetExists('test123'));
    }

    public function testDirNotValidException()
    {
        $app = new Application();
        $app['debug'] = true;
        $app->register(new ConfigServiceProvider(), ['config.dir' => null]); // null is a valid value upon creation

        $this->expectException(IOException::class);
        $this->expectExceptionMessageRegExp('#^"/a/b/c/" is not a directory\.$#');

        $app['config']->setDir('/a/b/c/');
    }

    /**
     * @param string $format
     *
     * @dataProvider provideFormats
     */
    public function testConfigFileNotFoundException(string $format)
    {
        $app = new Application();
        $app['debug'] = true;
        $this->setupConfigService($app);
        $app['config']->setFormat($format);

        $this->expectException(FileNotFoundException::class);
        $this->expectExceptionMessageRegExp('#^Config file not found ".*"\.$#');

        $app['config']->get('test_not_found');
    }

    /**
     * @param string $format
     *
     * @dataProvider provideFormats
     */
    public function testFileFormatException(string $format)
    {
        $app = new Application();
        $app['debug'] = true;
        $this->setupConfigService($app);
        $app['config']->setFormat($format);

        $this->expectException(\RuntimeException::class);

        $app['config']->get('invalid');
    }

    /**
     * @return array<array<string>>
     */
    public function provideFormats(): array
    {
        $cases = [
            ['%key%.json'],
            ['%key%.yml'],
            ['%key%.php'],
        ];

        return $cases;
    }

    public function testFileFormatNotSupportedException()
    {
        $app = new Application();
        $app['debug'] = true;
        $this->setupConfigService($app);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('#^Unsupported file format "xls"\.$#');

        $app['config']->setFormat('%key%.xls');
    }

    public function testFileFormatMissingKeyException()
    {
        $app = new Application();
        $app['debug'] = true;
        $this->setupConfigService($app);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('#^Format must contain "%key%", got ".xls"\.$#');

        $app['config']->setFormat('.xls');
    }

    public function testFileFormatNoExtensionException()
    {
        $app = new Application();
        $app['debug'] = true;
        $this->setupConfigService($app);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('#^Format missing extension, got "%key%json"\.$#');

        $app['config']->setFormat('%key%json');
    }

    public function testJsonInvalid()
    {
        $app = new Application();
        $app['debug'] = true;
        $this->setupConfigService($app);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessageRegExp('#^Error parsing JSON: "Syntax error" \[\d\], in ".*invalid\.json"\.$#');

        $app['config']->get('invalid');
    }

    public function testJsonNotArray()
    {
        $app = new Application();
        $app['debug'] = true;
        $this->setupConfigService($app);

        $this->assertSame(123, $app['config']->get('integer'));
    }

    public function testJsonFalse()
    {
        $app = new Application();
        $app['debug'] = true;
        $this->setupConfigService($app);

        $this->assertFalse($app['config']->get('false'));
        $this->assertTrue(isset($app['config']['false']));
    }

    public function testJsonNull()
    {
        $app = new Application();
        $app['debug'] = true;
        $this->setupConfigService($app);

        $this->assertNull($app['config']->get('null'));
        $this->assertFalse(isset($app['config']['null']));
    }

    public function testPHPNotArray()
    {
        $app = new Application();
        $app['debug'] = true;
        $this->setupConfigService($app, '%key%.php');

        $this->assertSame(456, $app['config']->get('integer'));
        $this->assertTrue(isset($app['config']['integer']));
        $this->assertTrue(isset($app['config']['integer']));
    }

    public function testJsonYaml()
    {
        $app = new Application();
        $app['debug'] = true;
        $this->setupConfigService($app, '%key%.yml');

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessageRegExp('#^Failed to parse config file "(.*)invalid.yml"\..*$#');

        $app['config']->get('invalid');
    }

    public function testYamlNotArray()
    {
        $app = new Application();
        $app['debug'] = true;
        $this->setupConfigService($app, '%key%.yml');

        $this->assertSame(789, $app['config']->get('integer'));
    }

    public function testConfigLoadingFollowsSymlinks()
    {
        $app = new Application();
        $app['debug'] = true;

        $configDatabaseDir = realpath(__DIR__.'/../../assets/configSymlink/1/2').'/';
        $app->register(new ConfigServiceProvider(), ['config.dir' => $configDatabaseDir]);
        $this->assertSame(
            ['symlink' => ['foo' => 'bar']],
            $app['config']['link1']
        );
    }
}
