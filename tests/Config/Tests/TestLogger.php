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

use Psr\Log\LoggerInterface;

/**
 * @author SpacePossum
 *
 * @internal
 */
final class TestLogger implements LoggerInterface
{
    private $debugLog = [];

    /**
     * {@inheritdoc}
     */
    public function debug($message, array $context = [])
    {
        $this->debugLog[] = [$message, $context];
    }

    /**
     * @return array<array<string, array<string, mixed>>>
     */
    public function getDebugLog(): array
    {
        return $this->debugLog;
    }

    /**
     * {@inheritdoc}
     */
    public function emergency($message, array $context = [])
    {
        throw new \BadMethodCallException(sprintf('"%s" should not be used by during the test.', __METHOD__));
    }

    /**
     * {@inheritdoc}
     */
    public function alert($message, array $context = [])
    {
        throw new \BadMethodCallException(sprintf('"%s" should not be used by during the test.', __METHOD__));
    }

    /**
     * {@inheritdoc}
     */
    public function critical($message, array $context = [])
    {
        throw new \BadMethodCallException(sprintf('"%s" should not be used by during the test.', __METHOD__));
    }

    /**
     * {@inheritdoc}
     */
    public function error($message, array $context = [])
    {
        throw new \BadMethodCallException(sprintf('"%s" should not be used by during the test.', __METHOD__));
    }

    /**
     * {@inheritdoc}
     */
    public function warning($message, array $context = [])
    {
        throw new \BadMethodCallException(sprintf('"%s" should not be used by during the test.', __METHOD__));
    }

    /**
     * {@inheritdoc}
     */
    public function notice($message, array $context = [])
    {
        throw new \BadMethodCallException(sprintf('"%s" should not be used by during the test.', __METHOD__));
    }

    /**
     * {@inheritdoc}
     */
    public function info($message, array $context = [])
    {
        throw new \BadMethodCallException(sprintf('"%s" should not be used by during the test.', __METHOD__));
    }

    /**
     * {@inheritdoc}
     */
    public function log($level, $message, array $context = [])
    {
        throw new \BadMethodCallException(sprintf('"%s" should not be used by during the test.', __METHOD__));
    }
}
