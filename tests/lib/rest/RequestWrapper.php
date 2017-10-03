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

class RequestWrapper {

    /**
     * @var Client
     */
    private $client;

    /**
     * @var Cache
     */
    private $cache;

    public function __construct(Client $client, Cache $cache) {
        $this->client = $client;
        $this->cache  = $cache;
    }

    public function getResponseWithoutAuth($request) {
        return $request->send();
    }

    public function getResponseByName($name, $request) {
        $token = $this->cache->getTokenForUser($name);
        if (! $token) {
            $token = $this->getTokenForUser($name, 'welcome0');
            $this->cache->setTokenForUser($name, $token);
        }

        return $request
            ->setHeader('X-Auth-Token', $token['token'])
            ->setHeader('X-Auth-UserId', $token['user_id'])
            ->send();
    }

    public function getResponseByBasicAuth($username, $password, $request) {
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
        $token = $this->client->post('tokens', null, $payload)->send()->json();
        return $token;
    }
}
