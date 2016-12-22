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

/**
 * @requires PHPUnit 5.2
 *
 * @internal
 *
 * @author SpacePossum
 */
final class ConfigLoaderTest extends \PHPUnit_Framework_TestCase
{
    public function testOffsetSet()
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessageRegExp('#^"offsetSet" is not supported.$#');

        $loader = new ConfigLoader(new Application());
        $loader->offsetSet(1, 2);
    }

    public function testOffsetUnset()
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessageRegExp('#^"offsetUnset" is not supported.$#');

        $loader = new ConfigLoader(new Application());
        $loader->offsetUnset(1);
    }
}
