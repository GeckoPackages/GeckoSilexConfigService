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

use Psr\Log\LoggerInterface;

/**
 * @author SpacePossum
 *
 * @internal
 */
final class TestLogger implements LoggerInterface
{
    private $debugLog = array();

    /**
     * {@inheritdoc}
     */
    public function debug($message, array $context = array())
    {
        $this->debugLog[] = array($message, $context);
    }

    /**
     * @return array<array<string, array<string, mixed>>>
     */
    public function getDebugLog()
    {
        return $this->debugLog;
    }

    /**
     * {@inheritdoc}
     */
    public function emergency($message, array $context = array())
    {
    }

    /**
     * {@inheritdoc}
     */
    public function alert($message, array $context = array())
    {
    }

    /**
     * {@inheritdoc}
     */
    public function critical($message, array $context = array())
    {
    }

    /**
     * {@inheritdoc}
     */
    public function error($message, array $context = array())
    {
    }

    /**
     * {@inheritdoc}
     */
    public function warning($message, array $context = array())
    {
    }

    /**
     * {@inheritdoc}
     */
    public function notice($message, array $context = array())
    {
    }

    /**
     * {@inheritdoc}
     */
    public function info($message, array $context = array())
    {
    }

    /**
     * {@inheritdoc}
     */
    public function log($level, $message, array $context = array())
    {
    }
}
