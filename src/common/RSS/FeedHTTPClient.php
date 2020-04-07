<?php
/**
 * Copyright (c) Enalean, 2018-2019. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\RSS;

use Laminas\Feed\Exception\RuntimeException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Laminas\Feed\Reader\Http\HeaderAwareClientInterface;
use Laminas\Feed\Reader\Http\Psr7ResponseDecorator;
use Laminas\Feed\Reader\Http\ResponseInterface;

final class FeedHTTPClient implements HeaderAwareClientInterface
{
    /**
     * @var ClientInterface
     */
    private $http_client;
    /**
     * @var RequestFactoryInterface
     */
    private $http_request_factory;

    public function __construct(ClientInterface $http_client, RequestFactoryInterface $http_request_factory)
    {
        $this->http_client          = $http_client;
        $this->http_request_factory = $http_request_factory;
    }

    public function get($uri, array $headers = []): ResponseInterface
    {
        $request = $this->http_request_factory->createRequest('GET', $uri);
        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        try {
            return new Psr7ResponseDecorator($this->http_client->sendRequest($request));
        } catch (ClientExceptionInterface $e) {
            throw new RuntimeException('Cannot retrieve feed: ' . $e->getMessage(), 0, $e);
        }
    }
}
