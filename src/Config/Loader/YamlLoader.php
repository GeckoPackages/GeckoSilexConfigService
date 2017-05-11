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
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;

/**
 * @internal
 *
 * @author SpacePossum
 */
final class YamlLoader implements LoaderInterface
{
    /**
     * @var Parser
     */
    private $parser;

    public function __construct()
    {
        $this->parser = new Parser();
    }

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

        try {
            $config = $this->parser->parse($config);
        } catch (ParseException $e) {
            throw new \UnexpectedValueException(sprintf('Failed to parse config file "%s". %s', $file, $e->getMessage()), $e->getCode(), $e);
        }

        return $config;
    }
}
