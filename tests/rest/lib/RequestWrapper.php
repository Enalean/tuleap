<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Test\Rest;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class RequestWrapper
{
    private Client $client;

    /**
     * @var Cache
     */
    private $cache;

    public function __construct(Client $client, Cache $cache)
    {
        $this->client = $client;
        $this->cache  = $cache;
    }

    public function getResponseWithoutAuth(RequestInterface $request): ResponseInterface
    {
        return $this->client->sendRequest($request);
    }

    public function getResponseByName($name, RequestInterface $request): ResponseInterface
    {
        $token = $this->cache->getTokenForUser($name);
        if (! $token) {
            $token = $this->getTokenForUser($name, \REST_TestDataBuilder::STANDARD_PASSWORD);
            $this->cache->setTokenForUser($name, $token);
        }

        return $this->client->sendRequest(
            $request
                ->withHeader('X-Auth-Token', $token['token'])
                ->withHeader('X-Auth-UserId', $token['user_id'])
        );
    }

    public function getResponseByBasicAuth($username, $password, RequestInterface $request): ResponseInterface
    {
        return $this->client->send($request, [RequestOptions::AUTH => [$username, $password], RequestOptions::HTTP_ERRORS => false]);
    }

    private function getTokenForUser($username, $password)
    {
        $payload = json_encode(
            [
                'username' => $username,
                'password' => $password,
            ]
        );

        $response = $this->client->request('POST', 'tokens', ['body' => $payload]);

        return json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
    }
}
