<?php
/**
 * Copyright (c) Enalean, 2014. All rights reserved
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

use Guzzle\Http\Client;

class RequestWrapper
{

    public const MAX_RETRY = 3;
    /**
     * @var Client
     */
    private $client;

    /**
     * @var Cache
     */
    private $cache;

    public function __construct(Client $client, Cache $cache)
    {
        $this->client = $client;
        $this->cache  = $cache;
    }

    public function getResponseWithoutAuth($request)
    {
        return $request->send();
    }

    public function getResponseByName($name, $request): \Guzzle\Http\Message\Response
    {
        $token = $this->cache->getTokenForUser($name);
        if (! $token) {
            $token = $this->getTokenForUser($name, \REST_TestDataBuilder::STANDARD_PASSWORD);
            $this->cache->setTokenForUser($name, $token);
        }

        return $request
            ->setHeader('X-Auth-Token', $token['token'])
            ->setHeader('X-Auth-UserId', $token['user_id'])
            ->send();
    }

    public function getResponseByBasicAuth($username, $password, $request)
    {
        $request->setAuth($username, $password);
        return $request->send();
    }

    private function getTokenForUser($username, $password)
    {
        $payload = json_encode(
            array(
                'username' => $username,
                'password' => $password,
            )
        );

        // Retry is there because in some circumstances (PHP 5.6 first REST call) restler shits bricks
        $retry = self::MAX_RETRY;
        do {
            if ($retry !== self::MAX_RETRY) {
                $wait_for = self::MAX_RETRY - $retry;
                sleep($wait_for);
            }
            $retry--;
            // need to hardcode the v1 path here when running v2 tests in standalone
            $response = $this->client->post('/api/v1/tokens', null, $payload)->send();
        } while (substr($response->getBody(true), 0, 1) !== '{' && $retry > 0);

        return $response->json();
    }
}
