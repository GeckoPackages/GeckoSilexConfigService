<?php declare(strict_types=1);

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
 * @api
 *
 * @author SpacePossum
 */
final class ConfigServiceProvider implements ServiceProviderInterface
{
    /**
     * @var string
     */
    private $name;

    public function __construct(string $name = 'config')
    {
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function register(Container $app)
    {
        $name = $this->name;
        $app[$name] = function (Container $app) use ($name) {
            return new ConfigLoader(
                $app,
                $app[$name.'.dir'] ?? null,
                $app[$name.'.format'] ?? '%key%.json',
                $app[$name.'.cache'] ?? null,
                $app[$name.'.env'] ?? null
            );
        };
    }
}
