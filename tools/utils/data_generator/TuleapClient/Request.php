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

namespace TuleapClient;

class Request {

    /**
     * @var Client
     */
    private $client;
    private $password;
    private $username;

    private $token;

    public function __construct(\Guzzle\Http\Client $client, $username, $password) {
        $this->client   = $client;
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * @return \Guzzle\Http\Client
     */
    public function getClient() {
        return $this->client;
    }

    /**
     * @param \Guzzle\Http\Message\Request $request
     * @return \Guzzle\Http\Message\Response
     */
    public function send(\Guzzle\Http\Message\Request $request) {
        $token = $this->getToken();
        $request->setHeader('X-Auth-Token', $token['token'])
                ->setHeader('Content-Type', 'application/json')
                ->setHeader('X-Auth-UserId', $token['user_id']);
        return $request->send();
    }

    /**
     * @param \Guzzle\Http\Message\Request $request
     * @return Array
     */
    public function getJson(\Guzzle\Http\Message\Request $request) {
        return $this->send($request)->json();
    }

    private function getToken() {
        if (! $this->token) {
            $response = $this->client->post('tokens', '', json_encode(array(
                "username" => $this->username,
                "password" => $this->password,
            )))->send();
            $this->token = $response->json();
        }
        return $this->token;
    }
}
