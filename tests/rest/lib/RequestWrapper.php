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

declare(strict_types=1);

namespace Tuleap\REST;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Psl\Json;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final readonly class RequestWrapper
{
    public function __construct(
        private Client $client,
        private Cache $cache,
    ) {
    }

    public function getResponseWithoutAuth(RequestInterface $request): ResponseInterface
    {
        return $this->client->sendRequest($request);
    }

    public function getResponseByName(string $name, RequestInterface $request): ResponseInterface
    {
        $token = $this->cache->getTokenForUser($name);
        if (! $token) {
            $token = $this->getTokenForUser($name, RESTTestDataBuilder::STANDARD_PASSWORD);
            $this->cache->setTokenForUser($name, $token);
        }

        return $this->client->sendRequest(
            $request
                ->withHeader('X-Auth-Token', $token['token'])
                ->withHeader('X-Auth-UserId', (string) $token['user_id'])
        );
    }

    public function getResponseByBasicAuth(string $username, string $password, RequestInterface $request): ResponseInterface
    {
        return $this->client->send($request, [RequestOptions::AUTH => [$username, $password], RequestOptions::HTTP_ERRORS => false]);
    }

    /**
     * @throws GuzzleException
     * @return array{user_id: int, token: string}
     */
    private function getTokenForUser(string $username, string $password): array
    {
        $payload = Json\encode(
            [
                'username' => $username,
                'password' => $password,
            ]
        );

        $response = $this->client->request('POST', 'tokens', ['body' => $payload]);

        return Json\decode($response->getBody()->getContents());
    }
}
