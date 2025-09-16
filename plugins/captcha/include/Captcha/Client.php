<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

readonly class Client
{
    private const string SITE_VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    public function __construct(
        private string $secret_key,
        private ClientInterface $http_client,
        private RequestFactoryInterface $request_factory,
        private StreamFactoryInterface $stream_factory,
    ) {
    }

    public function verify(string $challenge, string $user_ip): bool
    {
        $request = $this->request_factory->createRequest('POST', self::SITE_VERIFY_URL)
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withBody(
                $this->stream_factory->createStream(
                    http_build_query([
                        'secret'   => $this->secret_key,
                        'response' => $challenge,
                        'remoteip' => $user_ip,
                    ])
                )
            );

        try {
            $http_response = $this->http_client->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            return false;
        }

        if ($http_response->getStatusCode() !== 200) {
            return false;
        }

        $response = json_decode($http_response->getBody()->getContents(), true);

        return isset($response['success']) && $response['success'] === true;
    }
}
