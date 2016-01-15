<?php

/*
 * This file is part of the GeckoPackages.
 *
 * (c) GeckoPackages https://github.com/GeckoPackages
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace GeckoPackages\Silex\Services\Config;

use Silex\Application;
use Silex\ServiceProviderInterface;

/**
 * Service for loading configuration.
 *
 * @author SpacePossum
 */
class ConfigServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
        //
    }

    /**
     * {@inheritdoc}
     */
    public function register(Application $app)
    {
        $app['config'] = $app->share(
            function () use ($app) {
                return new ConfigLoader(
                    $app,
                    isset($app['config.dir']) ? $app['config.dir'] : null,
                    isset($app['config.format']) ? $app['config.format'] : '%key%.json',
                    isset($app['config.cache']) ? $app['config.cache'] : null,
                    isset($app['config.env']) ? $app['config.env'] : null
                );
            }
        );
    }
}
