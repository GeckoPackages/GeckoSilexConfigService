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

use GeckoPackages\MemcacheMock\MemcachedLogger;
use GeckoPackages\MemcacheMock\MemcachedMock;
use GeckoPackages\Silex\Services\Config\ConfigLoader;
use GeckoPackages\Silex\Services\Config\ConfigServiceProvider;
use Silex\Application;

/**
 * @internal
 *
 * @author SpacePossum
 */
final class ConfigServiceProviderMemcachedTest extends AbstractConfigTest
{
    public function testDoNotTouchCacheOnConstruction()
    {
        $app = new Application();
        $app['debug'] = true;
        $app['memcache'] = $this->getMemcacheMock();
        $this->setupConfigService($app, '%key%.json', 'memcache');

        $logger = $app['memcache']->getLogger();
        $logger = $logger->getLogger();
        $log = $logger->getDebugLog();
        $this->assertCount(0, $log);
    }

    public function testDoNotTouchCacheOnFirstDirSet()
    {
        $app = new Application();
        $app['debug'] = true;
        $app['memcache'] = $this->getMemcacheMock();
        $app->register(
            new ConfigServiceProvider(),
            [
                'config.dir' => null,
                'config.cache' => 'memcache',
            ]
        );

        $logger = $app['memcache']->getLogger();
        $logger = $logger->getLogger();
        $log = $logger->getDebugLog();
        $this->assertCount(0, $log, 'Log should be empty on boot.');

        $app['config']->setDir(__DIR__);

        $logger = $app['memcache']->getLogger();
        $logger = $logger->getLogger();
        $log = $logger->getDebugLog();
        $this->assertCount(0, $log);
    }

    public function testUsingCache()
    {
        $configValue = ['options' => ['test' => ['driver' => 'pdo_mysql']]];

        $app = new Application();
        $app['debug'] = true;
        $this->setupConfigService($app, '%key%.json', 'memcache2');
        $app['memcache2'] = $this->getMemcacheMock();

        $app['config']->get('test');         // miss cache,  get -> 1 set -> 1 delete -> 0
        $app['config']->flushConfig('test'); // clear cache, get -> 1 set -> 1 delete -> 1
        $app['config']->get('test');         // miss cache,  get -> 2 set -> 2 delete -> 1

        /** @var MemcachedLogger $logger */
        $logger = $app['memcache2']->getLogger();

        /** @var TestLogger $logger */
        $logger = $logger->getLogger();
        $log = $logger->getDebugLog();
        $this->assertCount(5, $log);

        $this->assertSame($log[0][0], 'get');
        $key = $log[0][1]['key'];

        $this->assertSame($log[1][0], 'set');
        $this->assertSame($key, $log[1][1]['key']);

        $value = $log[1][1]['value'];
        $this->assertSame($configValue, $value);

        $this->assertSame($log[2][0], 'delete');
        $this->assertSame($key, $log[2][1]['key']);

        $this->assertSame($log[3][0], 'get');
        $this->assertSame($key, $log[3][1]['key']);

        $this->assertSame($log[4][0], 'set');
        $this->assertSame($key, $log[4][1]['key']);
        $this->assertSame($configValue, $log[4][1]['value']);

        // setting directory to same location shouldn't trigger cache flush
        $app['config']->setDir($this->getConfigDir());

        $log = $logger->getDebugLog();
        $this->assertCount(5, $log);

        $app['config']->setDir(__DIR__);

        $log = $logger->getDebugLog();
        $this->assertCount(6, $log);
    }

    // make sure that when the config loader doesn't have a value
    // for the key yet but it is in memcache it will fetch it from
    // memcache and than serve it from local memory
    public function testUsingWarmCache()
    {
        $cacheName = 'cache';
        $app = new Application();
        $app['debug'] = true;
        $this->setupConfigService($app, '%key%.json', $cacheName);
        $app[$cacheName] = $this->getMemcacheMock();
        $this->usingCacheTest($app, $cacheName);
    }

    // @see testUsingWarmCache but with a caching service set
    // after registering the service
    public function testLateSettingCache()
    {
        $cacheName = 'memcache123';
        $app = new Application();
        $app['debug'] = true;
        $this->setupConfigService($app, '%key%.json');
        $app[$cacheName] = $this->getMemcacheMock();
        $r = $app['config']->setCache($cacheName);
        $this->assertInstanceOf(ConfigLoader::class, $r);
        $this->usingCacheTest($app, $cacheName);
    }

    public function testCacheFlush()
    {
        $key = 'a';
        $configDatabaseDir = __DIR__;
        $app = new Application();
        $app['debug'] = true;
        $app['testCache'] = new TestCache();

        $loader = new ConfigLoader($app, $configDatabaseDir, '%key%.json', 'testCache');

        $this->assertSame('1:x', $loader->get($key));
        $this->assertSame('1:x', $loader->get($key));

        $loader->flushConfig($key);

        $this->assertSame('2:1', $loader->get($key));
        $this->assertSame('2:1', $loader->get($key));

        $this->assertSame(2, $app['testCache']->getTotalCallCount());
        $this->assertSame(1, $app['testCache']->getTotalFlushCount());

        $loader = new ConfigLoader($app, $configDatabaseDir, '%key%.json', 'testCache');
        $loader->flushConfig($key);

        $this->assertSame('3:2', $loader->get($key));
        $this->assertSame('3:2', $loader->get($key));

        $this->assertSame(3, $app['testCache']->getTotalCallCount());
        $this->assertSame(2, $app['testCache']->getTotalFlushCount());
    }

    private function usingCacheTest(Application $app, $cacheName)
    {
        $app['config']->get('test');

        $logger = $app[$cacheName]->getLogger();
        $logger = $logger->getLogger();

        $log = $logger->getDebugLog();
        $this->assertCount(2, $log);
        $this->assertSame('get', $log[0][0]);

        $key = $log[0][1]['key'];

        $this->assertSame('set', $log[1][0]);
        $this->assertSame($key, $log[1][1]['key']);

        // start over again

        // reset internals
        $app['config']->flushConfig('test');

        // warm up the cache
        $testValue = ['test'];
        $app[$cacheName] = $this->getMemcacheMock();
        $app[$cacheName]->set($key, $testValue);

        $app['config']->get('test');
        $app['config']->get('test');

        /** @var MemcachedLogger $logger */
        $logger = $app[$cacheName]->getLogger();
        $logger = $logger->getLogger();

        $log = $logger->getDebugLog();
        $this->assertCount(2, $log);

        $this->assertSame('set', $log[0][0]);
        $this->assertSame($key, $log[0][1]['key']);
        $this->assertSame($testValue, $log[0][1]['value']);

        $this->assertSame('get', $log[1][0]);
        $this->assertSame($key, $log[1][1]['key']);

        $app['config']->flushConfig('test');

        $log = $logger->getDebugLog();
        $this->assertCount(3, $log);

        $this->assertSame('delete', $log[2][0]);
        $this->assertSame($key, $log[2][1]['key']);

        // flush all test
        $app['config']->get('test');
        $app['config']->get('flushTest');

        $log = $logger->getDebugLog();
        $this->assertCount(7, $log); // get+set 2x

        $app['config']->flushAll();

        $log = $logger->getDebugLog();
        $this->assertCount(9, $log); // +2 delete

        $this->assertSame('delete', $log[7][0]);
        $this->assertSame($key, $log[7][1]['key']);

        $this->assertSame('delete', $log[8][0]);
        $this->assertNotSame($key, $log[8][1]['key']);
        $this->assertInternalType('string', $log[8][1]['key']);
        $this->assertNotEmpty('string', $log[8][1]['key']);
    }

    private function getMemcacheMock()
    {
        $mock = new MemcachedMock();
        $mock->setThrowExceptionsOnFailure(true);
        $mock->addServer('127.0.0.1', 11211);
        $mock->setLogger(new MemcachedLogger(new TestLogger()));

        return $mock;
    }
}

final class TestCache
{
    private $callCount = [];
    private $flushCount = [];

    public function delete($key)
    {
        if (!array_key_exists($key, $this->flushCount)) {
            $this->flushCount[$key] = 0;
        }

        ++$this->flushCount[$key];
    }

    public function get($key)
    {
        if (!array_key_exists($key, $this->callCount)) {
            $this->callCount[$key] = 0;
        }

        ++$this->callCount[$key];

        return $this->callCount[$key].':'.(array_key_exists($key, $this->flushCount) ? $this->flushCount[$key] : 'x');
    }

    public function getTotalCallCount()
    {
        return array_sum($this->callCount);
    }

    public function getTotalFlushCount()
    {
        return array_sum($this->flushCount);
    }
}
