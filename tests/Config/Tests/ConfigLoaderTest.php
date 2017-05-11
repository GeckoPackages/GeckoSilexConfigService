<?php declare(strict_types=1);

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
use PHPUnit\Framework\TestCase;
use Silex\Application;

/**
 * @requires PHPUnit 6.0
 *
 * @author SpacePossum
 *
 * @internal
 */
final class ConfigLoaderTest extends TestCase
{
    public function testOffsetSet1()
    {
        $loader = new ConfigLoader(new Application());

        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessageRegExp('#^"offsetSet" is not supported\.$#');

        $loader->offsetSet(1, 2);
    }

    public function testOffsetSet2()
    {
        $loader = new ConfigLoader(new Application());

        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessageRegExp('#^"offsetSet" is not supported\.$#');

        $loader[1] = 2;
        echo $loader[1];
    }

    public function testOffsetUnset1()
    {
        $loader = new ConfigLoader(new Application());

        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessageRegExp('#^"offsetUnset" is not supported\.$#');

        $loader->offsetUnset(1);
    }

    public function testOffsetUnset2()
    {
        $loader = new ConfigLoader(new Application());

        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessageRegExp('#^"offsetUnset" is not supported\.$#');

        unset($loader[1]);
    }
}
