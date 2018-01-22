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

use GeckoPackages\Silex\Services\Config\Loader\JsonLoader;
use GeckoPackages\Silex\Services\Config\Loader\LoaderInterface;
use GeckoPackages\Silex\Services\Config\Loader\PHPLoader;
use GeckoPackages\Silex\Services\Config\Loader\YamlLoader;
use Pimple\Container;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Exception\IOException;

/**
 * @api
 *
 * @author SpacePossum
 */
final class ConfigLoader implements \ArrayAccess
{
    /**
     * @var Container
     */
    private $app;

    /**
     * Local storage of fetched configurations.
     *
     * @var array<string, mixed>
     */
    private $config = [];

    /**
     * @var string|null
     */
    private $configDirectory;

    /**
     * @var LoaderInterface
     */
    private $loader;

    /**
     * @var string
     */
    private $format;

    /**
     * @var string|null
     */
    private $cache;

    /**
     * @var string
     */
    private $environment;

    /**
     * @param Container   $app
     * @param string|null $dir
     * @param string      $format      default: '%key%.json' @see ConfigLoader::setFormat
     * @param string|null $cache       name under which a cache service is registered,
     *                                 default: null (don't use caching)
     * @param string|null $environment default: null @see ConfigLoader::setEnvironment
     */
    public function __construct(
        Container $app,
        string $dir = null,
        string $format = '%key%.json',
        string $cache = null,
        string $environment = null
    ) {
        $this->app = $app;
        if (null !== $dir) {
            $this->setDir($dir);
        }

        $this->setFormat($format);
        $this->environment = null === $environment ? '' : $environment;

        // Always set last, do not use `setCache` to prevent cache flushes on construction of the loader.
        $this->cache = $cache;
    }

    // Magic function support, for Twig etc.,
    // @see http://twig.sensiolabs.org/doc/recipes.html#using-dynamic-object-properties

    /**
     * @param string $name
     *
     * @return bool
     */
    public function __isset($name)
    {
        if (isset($this->config[$name])) {
            return isset($this->config[$name]['config']);
        }

        try {
            $this->get($name);
        } catch (FileNotFoundException $e) {
            return false;
        } // do not catch parsing errors and such

        return isset($this->config[$name]['config']);
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     * Get configuration by the given key.
     *
     * @param string $key
     *
     * @throws FileNotFoundException
     * @throws IOException
     * @throws \UnexpectedValueException
     *
     * @return mixed
     */
    public function get(string $key)
    {
        if (isset($this->config[$key])) {
            // if true, 'config' is also always set
            return $this->config[$key]['config'];
        }

        $file = $this->getFileNameForKey($key);

        // check if in cache
        if (null !== $this->cache) {
            $cacheKey = $this->getCacheKeyForFile($file);
            $conf = $this->app[$this->cache]->get($cacheKey);
            if (is_array($conf) && array_key_exists('config', $conf)) {
                $this->config[$key] = [
                    'config' => $conf['config'],
                    'cacheKey' => $cacheKey,
                ];

                return $conf['config'];
            }
        }

        // Load from file using loader
        $conf = $this->loader->getConfig($file);
        $this->config[$key] = ['config' => $conf];

        // Store in the cache
        if (null !== $this->cache) {
            $this->app[$this->cache]->set($cacheKey, $this->config[$key]);
            $this->config[$key]['cacheKey'] = $cacheKey;
        }

        return $conf;
    }

    /**
     * Returns the directory where this class looks for configuration files.
     *
     * @return string|null
     */
    public function getDir()
    {
        return $this->configDirectory;
    }

    /**
     * Set the name under which the cache to use is registered in the Application.
     *
     * Set <null> to disable using cache.
     *
     * Triggers @see ConfigLoader::flushAll (on the previous cache service if configured).
     *
     * @param string|null $cache
     *
     * @return $this
     */
    public function setCache(string $cache = null): self
    {
        $this->flushAll(); // flush cached entities (including internally)
        $this->cache = $cache;

        return $this;
    }

    /**
     * Set the directory location for the config files.
     *
     * Triggers @see ConfigLoader::flushAll (if not the same directory is passed).
     *
     * @param string $dir Full path
     *
     * @throws FileNotFoundException
     *
     * @return $this
     */
    public function setDir(string $dir): self
    {
        if (!is_dir($dir)) {
            throw new FileNotFoundException(sprintf('"%s" is not a directory.', $dir));
        }

        $newDir = realpath($dir).'/';
        if (null === $this->configDirectory) {
            $this->configDirectory = $newDir;

            return $this;
        }

        if ($newDir === $this->configDirectory) {
            return $this;
        }

        $this->configDirectory = $newDir;
        $this->flushAll();

        return $this;
    }

    /**
     * Set the version of the configuration files used in the file name format.
     *
     * Triggers @see ConfigLoader::flushAll.
     *
     * @see setFormat
     *
     * @param string|null $environment
     *
     * @return $this
     */
    public function setEnvironment(string $environment = null): self
    {
        $this->environment = null === $environment ? '' : $environment;
        $this->flushAll();

        return $this;
    }

    /**
     * Set the file format to search for in the configuration directory.
     *
     * Triggers @see ConfigLoader::flushAll.
     *
     * @param string $format json(.dist)|y(a)ml(.dist)|php(.dist) with variable '%key%' and optional '%env%'
     *
     * @return $this
     */
    public function setFormat(string $format): self
    {
        if ($this->app['debug']) {
            if (false === strpos($format, '%key%')) {
                throw new \InvalidArgumentException(sprintf('Format must contain "%%key%%", got "%s".', $format));
            }

            if (false === strrpos($format, '.')) {
                throw new \InvalidArgumentException(sprintf('Format missing extension, got "%s".', $format));
            }
        }

        $this->format = $format;

        // determine file extension (with or without trailing `.dist`)
        $format = '.dist' === substr($format, -5)
            ? substr($format, strrpos($format, '.', -6) + 1)
            : substr($format, strrpos($format, '.') + 1)
        ;

        switch ($format) {
            case 'json':
            case 'json.dist':
                $this->loader = new JsonLoader();

                break;
            case 'yml':
            case 'yml.dist':
            case 'yaml':
            case 'yaml.dist':
                if (false === class_exists('Symfony\\Component\\Yaml\\Yaml')) {
                    throw new \RuntimeException('Missing Symfony Yaml component.');
                }

                $this->loader = new YamlLoader();

                break;
            case 'php':
            case 'php.dist':
                $this->loader = new PHPLoader();

                break;
            default:
                throw new \InvalidArgumentException(sprintf('Unsupported file format "%s".', $format));
        }

        $this->flushAll();

        return $this;
    }

    /**
     * Flush all config loaded by this loader.
     *
     * Flushes the internal cache and those in cache service (if configured).
     */
    public function flushAll()
    {
        if (null !== $this->cache) {
            foreach ($this->config as $config) {
                if (isset($config['cacheKey'])) {
                    $this->app[$this->cache]->delete($config['cacheKey']);
                }
            }
        }

        $this->config = [];
    }

    /**
     * Flush config from the loader.
     *
     * Flushes the configuration from the internal buffer and the cache (if configured).
     *
     * @param string $key
     */
    public function flushConfig(string $key)
    {
        $this->offsetUnset($key);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return $this->__isset($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        throw new \BadMethodCallException('"offsetSet" is not supported.');
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        if (null !== $this->cache) {
            $this->app[$this->cache]->delete(
                $this->config[$offset]['cacheKey'] ?? $this->getCacheKeyForFile($this->getFileNameForKey($offset))
            );
        }

        unset($this->config[$offset]);
    }

    private function getCacheKeyForFile(string $file): string
    {
        return 'conf:'.abs(crc32($file));
    }

    private function getFileNameForKey(string $key): string
    {
        return $this->getDir().strtr($this->format, ['%key%' => $key, '%env%' => $this->environment]);
    }
}
