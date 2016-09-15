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
abstract class AbstractConfigTest extends \PHPUnit_Framework_TestCase
{
    protected function getConfigDir()
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
