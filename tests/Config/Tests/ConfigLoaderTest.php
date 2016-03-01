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

use GeckoPackages\Silex\Services\Config\ConfigLoader;
use Silex\Application;

class ConfigLoaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \BadMethodCallException
     * @@expectedExceptionMessage "offsetSet" is not supported.
     */
    public function testOffsetSet()
    {
        $loader = new ConfigLoader(new Application());
        $loader->offsetSet(1, 2);
    }

    /**
     * @expectedException \BadMethodCallException
     * @@expectedExceptionMessage "offsetUnset" is not supported.
     */
    public function testOffsetUnset()
    {
        $loader = new ConfigLoader(new Application());
        $loader->offsetUnset(1);
    }
}
