<?php
/**
 * Copyright (c) Enalean, 2016-2018. All Rights Reserved.
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

class ClientBotMattermost
{
    private $http_client;

    public function __construct()
    {
        $this->http_client = new \Http_Client;
    }

    public function sendMessage($post_string, $url)
    {
        $options = [
            CURLOPT_POST       => true,
            CURLOPT_URL        => $url,
            CURLOPT_HTTPHEADER => ['Content-type: application/json'],
            CURLOPT_POSTFIELDS => $post_string
        ];
        $this->http_client->addOptions($options);
        $this->http_client->doRequest();

    }
}