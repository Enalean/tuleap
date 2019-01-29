<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

use Http\Message\RequestFactory;
use Zend\Feed\Reader\Http\HeaderAwareClientInterface;
use Zend\Feed\Reader\Http\Psr7ResponseDecorator;
use Zend\Feed\Reader\Http\ResponseInterface;

final class FeedHTTPClient implements HeaderAwareClientInterface
{
    /**
     * @var \Http\Client\HttpClient
     */
    private $http_client;
    /**
     * @var RequestFactory
     */
    private $http_request_factory;

    public function __construct(\Http\Client\HttpClient $http_client, RequestFactory $http_request_factory)
    {
        $this->http_client          = $http_client;
        $this->http_request_factory = $http_request_factory;
    }

    public function get($uri, array $headers = []) : ResponseInterface
    {
        $request = $this->http_request_factory->createRequest('GET', $uri, $headers);

        return new Psr7ResponseDecorator($this->http_client->sendRequest($request));
    }
}
