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

use GeckoPackages\Silex\Services\Config\Loader\JsonLoader;
use GeckoPackages\Silex\Services\Config\Loader\LoaderInterface;
use GeckoPackages\Silex\Services\Config\Loader\PHPLoader;
use GeckoPackages\Silex\Services\Config\Loader\YamlLoader;
use Silex\Application;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

/**
 * @api
 *
 * @author SpacePossum
 */
class ConfigLoader implements \ArrayAccess
{
    /**
     * @var Application
     */
    private $app;

    /**
     * Local storage of fetched configurations.
     *
     * @var array<string, array>
     */
    private $config = array();

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
     * @var string|null
     */
    private $environment;

    /**
     * @param Application $app
     * @param string|null $dir
     * @param string      $format      default: '%key%.json' @see ConfigLoader::setFormat
     * @param string|null $cache       name under which a cache service is registered,
     *                                 default: null (don't use caching)
     * @param string|null $environment default: null @see ConfigLoader::setEnvironment
     */
    public function __construct(Application $app, $dir = null, $format = '%key%.json', $cache = null, $environment = null)
    {
        $this->app = $app;
        if (null !== $dir) {
            $this->setDir($dir);
        }

        $this->setFormat($format);
        $this->environment = null === $environment ? '' : $environment;
        $this->cache = $cache; // always set last to prevent cache flushes on construction
    }

    /**
     * Get configuration by the given key.
     *
     * @param string $key
     *
     * @return array
     *
     * @throws FileNotFoundException
     */
    public function get($key)
    {
        if (array_key_exists($key, $this->config)) {
            // if true, 'config' is also always set
            return $this->config[$key]['config'];
        }

        $conf = null;
        $file = $this->getFileNameForKey($key);

        // check if in cache
        if (null !== $this->cache) {
            $cacheKey = $this->getCacheKeyForFile($file);
            $conf = $this->app[$this->cache]->get($cacheKey);
            if (false !== $conf) {
                $this->config[$key] = array(
                    'config' => $conf,
                    'cacheKey' => $cacheKey,
                );

                return $conf;
            }
        }

        // Load from file
        $conf = $this->loader->getConfig($file);
        $this->config[$key] = array('config' => $conf);

        // Store in the cache
        if (null !== $this->cache) {
            $this->config[$key]['cacheKey'] = $cacheKey;
            $this->app[$this->cache]->set($cacheKey, $conf);
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
     * @param string $cache
     */
    public function setCache($cache)
    {
        $this->cache = $cache;
    }

    /**
     * Set the directory location for the config files.
     *
     * Triggers @see ConfigLoader::flushAll.
     *
     * @param string $dir Full path
     *
     * @throws FileNotFoundException
     */
    public function setDir($dir)
    {
        if (!is_dir($dir)) {
            throw new FileNotFoundException(sprintf('Config "%s" is not a directory.', is_string($dir) ? $dir : (is_object($dir) ? get_class($dir) : gettype($dir))));
        }

        $newDir = realpath($dir).'/';
        if (null === $this->configDirectory) {
            $this->configDirectory = $newDir;

            return;
        }

        if ($newDir === $this->configDirectory) {
            return;
        }

        $this->configDirectory = $newDir;
        $this->flushAll();
    }

    /**
     * Set the version of the configuration files used in the file name format.
     *
     * Triggers @see ConfigLoader::flushAll.
     *
     * @see setFormat
     *
     * @param string|null $environment
     */
    public function setEnvironment($environment)
    {
        $this->environment = null === $environment ? '' : $environment;
        $this->flushAll();
    }

    /**
     * Set the file format to search for in the configuration directory.
     *
     * Triggers @see ConfigLoader::flushAll.
     *
     * @param string $format json(.dist)|y(a)ml(.dist)|php(.dist) with variable '%key%' and optional '%env%'.
     */
    public function setFormat($format)
    {
        if ($this->app['debug']) {
            if (false === is_string($format)) {
                throw new \InvalidArgumentException(sprintf('Format must be a string, got "%s".', is_object($format) ? get_class($format) : gettype($format)));
            }

            if (false === strpos($format, '%key%')) {
                throw new \InvalidArgumentException(sprintf('Format must contain "%%key%%", got "%s".', $format));
            }

            if (false ===  strrpos($format, '.')) {
                throw new \InvalidArgumentException(sprintf('Format missing extension, got "%s".', $format));
            }
        }

        $this->format = $format;

        if (strlen($format) > 5 && '.dist' === substr($format, -5)) {
            $format = substr($format, strrpos($format, '.', -6) + 1);
        } else {
            $format = substr($format, strrpos($format, '.') + 1);
        }

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
            default :
                throw new \InvalidArgumentException(sprintf('Unsupported file format "%s".', $format));
        }

        $this->flushAll();
    }

    /**
     * Flush all config loaded by this loader.
     *
     * Flushes the internal buffer and those in memcache (if configured).
     */
    public function flushAll()
    {
        if (null !== $this->cache) {
            foreach ($this->config as $config) {
                if (array_key_exists('cacheKey', $config)) {
                    $this->app[$this->cache]->delete($config['cacheKey']);
                }
            }
        }

        $this->config = array();
    }

    /**
     * Flush config from the loader.
     *
     * Flushes the configuration from the internal buffer and in memcache (if configured).
     *
     * @param string $key
     */
    public function flushConfig($key)
    {
        if (array_key_exists($key, $this->config)) {
            if (null !== $this->cache && array_key_exists('cacheKey', $this->config[$key])) {
                $this->app[$this->cache]->delete($this->config[$key]['cacheKey']);
            }

            unset($this->config[$key]);
        }else{
            $file = $this->getFileNameForKey($key);
            $this->app[$this->cache]->delete($this->getCacheKeyForFile($file));
        }
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
        try {
            return is_array($this->get($name));
        } catch (FileNotFoundException $e) {
        } // do not catch parsing errors and such

        return false;
    }

    /**
     * @param string $name
     *
     * @return array
     */
    public function __get($name)
    {
        return $this->get($name);
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
        throw new \BadMethodCallException('"offsetUnset" is not supported.');
    }

    /**
     * @param string $file
     *
     * @return string
     */
    private function getCacheKeyForFile($file)
    {
        return 'conf:'.abs(crc32($file));
    }

    /**
     * @param string $key
     *
     * @return string
     */
    private function getFileNameForKey($key)
    {
        return $this->getDir().strtr($this->format, array('%key%' => $key, '%env%' => $this->environment));
    }
}
