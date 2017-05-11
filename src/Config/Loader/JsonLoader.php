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
use Symfony\Component\Filesystem\Exception\IOException;

/**
 * @internal
 *
 * @author SpacePossum
 */
final class JsonLoader implements LoaderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfig(string $file)
    {
        if (false === is_file($file)) {
            throw new FileNotFoundException(sprintf('Config file not found "%s".', $file));
        }

        if (false === $config = @file_get_contents($file)) {
            throw new IOException(sprintf('Failed to load config file "%s".', $file));
        }

        if (null === $config = @json_decode($config, true)) {
            $err = json_last_error();
            if (JSON_ERROR_NONE !== $err) {
                throw new \UnexpectedValueException(sprintf(
                    'Error parsing JSON: "%s" [%d], in "%s".',
                    json_last_error_msg(),
                    $err,
                    $file
                ));
            }
        }

        return $config;
    }
}
