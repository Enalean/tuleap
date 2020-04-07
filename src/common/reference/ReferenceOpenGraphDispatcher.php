<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
 *
 */

namespace Tuleap\Reference;

use Embed\Http\DispatcherInterface;
use Embed\Http\ImageResponse;
use Embed\Http\Response;
use Embed\Http\Url;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

class ReferenceOpenGraphDispatcher implements DispatcherInterface
{
    /**
     * @var ClientInterface
     */
    private $http_client;
    /**
     * @var RequestFactoryInterface
     */
    private $request_factory;

    public function __construct(ClientInterface $http_client, RequestFactoryInterface $request_factory)
    {
        $this->http_client     = $http_client;
        $this->request_factory = $request_factory;
    }

    public function dispatch(Url $url): Response
    {
        $request = $this->request_factory->createRequest('GET', $url->__toString());

        try {
            $response = $this->http_client->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            return new Response($url, $url, 500, null, null, []);
        }

        return new Response(
            $url,
            $url,
            $response->getStatusCode(),
            $response->getHeaderLine('Content-Type'),
            $response->getBody()->getContents(),
            []
        );
    }

    /**
     * Resolve multiple image urls at once.
     *
     * Not implemented yet.
     *
     * @param Url[] $urls
     *
     * @return ImageResponse[]
     */
    public function dispatchImages(array $urls)
    {
        return [];
    }
}
