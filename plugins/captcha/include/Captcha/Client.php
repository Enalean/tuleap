<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Captcha;

use Http_Client;
use Http_ClientException;

class Client
{
    const SITE_VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    /**
     * @var string
     */
    private $secret_key;
    /**
     * @var Http_Client
     */
    private $http_client;

    public function __construct($secret_key, Http_Client $http_client)
    {
        $this->secret_key  = $secret_key;
        $this->http_client = $http_client;
    }

    /**
     * @return bool
     */
    public function verify($challenge, $user_ip)
    {
        $this->http_client->addOptions(array(
            CURLOPT_URL        => self::SITE_VERIFY_URL,
            CURLOPT_HTTPHEADER => array('Content-Type: application/x-www-form-urlencoded'),
            CURLOPT_POST       => true,
            CURLOPT_POSTFIELDS => http_build_query(array(
                'secret'   => $this->secret_key,
                'response' => $challenge,
                'remoteip' => $user_ip
            ))
        ));

        try {
            $this->http_client->doRequest();
        } catch (Http_ClientException $ex) {
            return false;
        }

        $json_response = $this->http_client->getLastResponse();
        $response      = json_decode($json_response, true);

        if (! $response) {
            return false;
        }

        return isset($response['success']) && $response['success'] === true;
    }
}
