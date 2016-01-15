<?php

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
 * @author SpacePossum
 */
interface LoaderInterface
{
    /**
     * @param string $file
     *
     * @return array
     *
     * @throws FileNotFoundException
     */
    public function getConfig($file);
}
