<?php declare(strict_types=1);

/*
 * This file is part of the GeckoPackages.
 *
 * (c) GeckoPackages https://github.com/GeckoPackages
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace GeckoPackages\Silex\Services\Config\Loader;

use Symfony\Component\Filesystem\Exception\FileNotFoundException;

/**
 * @internal
 *
 * @author SpacePossum
 */
final class PHPLoader implements LoaderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfig(string $file)
    {
        if (false === is_file($file)) {
            throw new FileNotFoundException(sprintf('Config file not found "%s".', $file));
        }

        return require $file;
    }
}
