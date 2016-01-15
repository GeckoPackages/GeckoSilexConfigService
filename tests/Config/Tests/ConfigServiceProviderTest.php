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

/**
 * @internal
 *
 * @author SpacePossum
 */
final class ConfigServiceProviderTest extends AbstractConfigTest
{
    public function testPHPConfig()
    {
        $configValue = array('test' => 1, 'lvl2' => array('20' => 'two zero', 21 => 'two one'));
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
            array(
                'foo' => 'bar',
                'leveled' => array(
                    'one' => 1,
                    'two' => 'second',
                    '3' => 1,
                ),
            ),
            $app['config']->get('test')
        );

        // test multiple files to see if the parser re use is OK
        $this->assertSame(
            array(
                'bar' => 'foo',
                'home' => 'here',
            ),
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
            array(
                'options' => array('test' => array('driver' => 'pdo_mysql')),
            ),
            $config = $app['config']->get('test')
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
            array(
                'options' => array('test' => array('driver' => 'pdo_mysql')),
            ),
            $config = $app['config']->get('test')
        );

        $dir = $app['config']->getDir().'../config2';
        $app['config']->setDir($dir);

        $this->assertSame(
            array('options' => array('test2' => 'new_dir')),
            $app['config']->get('test')
        );
    }

    /**
     * @expectedException \Symfony\Component\Filesystem\Exception\IOException
     * @@expectedExceptionMessage Config "/a/b/c/" is not a directory.
     */
    public function testDirNotValidException()
    {
        $app = new Application();
        $app['debug'] = true;
        $app->register(new ConfigServiceProvider(), array('config.dir' => __DIR__));
        $app['config']->setDir('/a/b/c/');
    }

    /**
     * @param string $format
     *
     * @dataProvider provideFormats
     *
     * @expectedException \Symfony\Component\Filesystem\Exception\FileNotFoundException
     * @expectedExceptionMessageRegExp /Config file not found ".*"./
     */
    public function testConfigFileNotFoundException($format)
    {
        $app = new Application();
        $app['debug'] = true;
        $this->setupConfigService($app);
        $app['config']->setFormat($format);
        $app['config']->get('test_not_found');
    }

    /**
     * @param string $format
     *
     * @dataProvider provideFormats
     *
     * @expectedException \RuntimeException
     */
    public function testFileFormatException($format)
    {
        $app = new Application();
        $app['debug'] = true;
        $this->setupConfigService($app);
        $app['config']->setFormat($format);
        $app['config']->get('invalid');
    }

    public function provideFormats()
    {
        $cases = array(
            array('%key%.json'),
            array('%key%.yml'),
            array('%key%.php'),
        );

        return $cases;
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Unsupported file format "xls".
     */
    public function testFileFormatNotSupportedException()
    {
        $app = new Application();
        $app['debug'] = true;
        $this->setupConfigService($app);
        $app['config']->setFormat('%key%.xls');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Format must contain "%key%", got ".xls".
     */
    public function testFileFormatMissingKeyException()
    {
        $app = new Application();
        $app['debug'] = true;
        $this->setupConfigService($app);
        $app['config']->setFormat('.xls');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Format must be a string, got "NULL".
     */
    public function testFileFormatNotStringException()
    {
        $app = new Application();
        $app['debug'] = true;
        $this->setupConfigService($app);
        $app['config']->setFormat(null);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Format missing extension, got "%key%json".
     */
    public function testFileFormatNoExtensionException()
    {
        $app = new Application();
        $app['debug'] = true;
        $this->setupConfigService($app);
        $app['config']->setFormat('%key%json');
    }
}
