<?php

declare(strict_types=1);

/*
 * This file is part of Guzzle Factory.
 *
 * (c) Graham Campbell <graham@alt-three.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Vannghia\GuzzlePromise\Libs;

use GuzzleHttp\BodySummarizer;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Utils;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * This is the guzzle factory class.
 *
 * @author Graham Campbell <graham@alt-three.com>
 */
final class GuzzleFactory
{
    /**
     * The default connect timeout.
     *
     * @var int
     */
    const CONNECT_TIMEOUT = 10;

    /**
     * The default transport timeout.
     *
     * @var int
     */
    const TIMEOUT = 20;

    /**
     * The default backoff multiplier.
     *
     * @var int
     */
    const BACKOFF = 1000;

    /**
     * The default 4xx retry codes.
     *
     * @var int[]
     */
    const CODES = [429];

    /**
     * Create a new guzzle client.
     *
     * @param array      $options
     * @param int|null   $backoff
     * @param int[]|null $codes
     *
     * @return \GuzzleHttp\Client
     */
    public static function make(array $options = [], int $backoff = null, array $codes = null)
    {
        $config = array_merge(['connect_timeout' => self::CONNECT_TIMEOUT, 'timeout' => self::TIMEOUT], $options);
        $config['handler'] = self::handler($backoff, $codes, $options['handler'] ?? null);

        return new Client($config);
    }

    /**
     * Create a new retrying handler stack.
     *
     * @param int|null                      $backoff
     * @param int[]|null                    $codes
     * @param \GuzzleHttp\HandlerStack|null $stack
     *
     * @return \GuzzleHttp\HandlerStack
     */
    public static function handler(int $backoff = null, array $codes = null, HandlerStack $stack = null)
    {
        $stack = $stack ?? self::innerHandler();

        $stack->push(self::createRetryMiddleware($backoff ?? self::BACKOFF, $codes ?? self::CODES), 'retry');

        return $stack;
    }

    /**
     * Create a new handler stack.
     *
     * @param callable|null $handler
     *
     * @return \GuzzleHttp\HandlerStack
     */
    public static function innerHandler(callable $handler = null): HandlerStack
    {
        $stack = new HandlerStack($handler ?? Utils::chooseHandler());

        $stack->push(Middleware::httpErrors(new BodySummarizer(250)), 'http_errors');
        $stack->push(Middleware::redirect(), 'allow_redirects');
        $stack->push(Middleware::cookies(), 'cookies');
        $stack->push(Middleware::prepareBody(), 'prepare_body');

        return $stack;
    }

    /**
     * Create a new retry middleware.
     *
     * @param int   $backoff
     * @param int[] $codes
     *
     * @return callable
     */
    private static function createRetryMiddleware(int $backoff, array $codes): callable
    {
        return Middleware::retry(function ($retries, RequestInterface $request, ResponseInterface $response = null, TransferException $exception = null) use ($codes) {
            return $retries < 3 && ($exception instanceof ConnectException || ($response && ($response->getStatusCode() >= 500 || in_array($response->getStatusCode(), $codes, true))));
        }, function ($retries) use ($backoff) {
            return (int) pow(2, $retries) * ($backoff === null ? self::BACKOFF : $backoff);
        });
    }
}
