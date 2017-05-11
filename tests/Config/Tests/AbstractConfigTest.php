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

use GeckoPackages\Silex\Services\Config\ConfigServiceProvider;
use PHPUnit\Framework\TestCase;
use Silex\Application;

/**
 * @requires PHPUnit 6.0
 *
 * @internal
 *
 * @author SpacePossum
 */
abstract class AbstractConfigTest extends TestCase
{
    protected function getConfigDir(): string
    {
        return __DIR__.'/../../assets/config';
    }

    protected function setupConfigService(Application $app, $format = '%key%.json', $cache = null, $env = null)
    {
        $app->register(
            new ConfigServiceProvider(),
            [
                'config.dir' => $this->getConfigDir(),
                'config.format' => $format,
                'config.cache' => $cache,
                'config.env' => $env,
            ]
        );
    }
}
