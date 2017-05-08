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

use Pimple\Container;
use Pimple\ServiceProviderInterface;

/**
 * Service for loading configuration.
 *
 * @final
 *
 * @author SpacePossum
 */
class ConfigServiceProvider implements ServiceProviderInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * ConfigServiceProvider constructor.
     *
     * @param string $name
     */
    public function __construct($name = 'config')
    {
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function register(Container $app)
    {
        $name = $this->name;
        $app[$name] = function ($app) use ($name) {
            return new ConfigLoader(
                $app,
                isset($app[$name.'.dir']) ? $app[$name.'.dir'] : null,
                isset($app[$name.'.format']) ? $app[$name.'.format'] : '%key%.json',
                isset($app[$name.'.cache']) ? $app[$name.'.cache'] : null,
                isset($app[$name.'.env']) ? $app[$name.'.env'] : null
            );
        };
    }
}
