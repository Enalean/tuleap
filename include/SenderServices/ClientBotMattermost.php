<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

namespace Tuleap\BotMattermost\SenderServices;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use RuntimeException;
use Tuleap\Http\HttpClientFactory;
use Tuleap\Http\HTTPFactoryBuilder;

class ClientBotMattermost
{
    /**
     * @var ClientInterface
     */
    private $http_client;
    /**
     * @var RequestFactoryInterface
     */
    private $request_factory;
    /**
     * @var StreamFactoryInterface
     */
    private $stream_factory;

    public function __construct()
    {
        $this->http_client = HttpClientFactory::createClient();
        $this->request_factory = HTTPFactoryBuilder::requestFactory();
        $this->stream_factory = HTTPFactoryBuilder::streamFactory();
    }

    public function sendMessage(string $post_string, string $url): void
    {
        $request = $this->request_factory->createRequest('POST', $url)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->stream_factory->createStream($post_string));

        $response = $this->http_client->sendRequest($request);

        $response_status_code = $response->getStatusCode();
        if ($response_status_code !== 200) {
            throw new RuntimeException(
                sprintf(
                    'Expected a response from %s with a 200 status code, got %s %s',
                    $url,
                    $response_status_code,
                    $response->getReasonPhrase()
                )
            );
        }
    }
}
