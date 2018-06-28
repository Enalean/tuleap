<?php
/**
 * Copyright (c) Enalean, 2017-2018. All Rights Reserved.
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

namespace Tuleap\Webhook;

use Http\Client\HttpAsyncClient;
use Http\Message\RequestFactory;
use Psr\Http\Message\ResponseInterface;

class Emitter
{
    /**
     * @var HttpAsyncClient
     */
    private $http_client;
    /**
     * @var StatusLogger
     */
    private $logger;
    /**
     * @var RequestFactory
     */
    private $http_request_factory;

    public function __construct(
        RequestFactory $http_request_factory,
        HttpAsyncClient $http_client,
        StatusLogger $status_logger
    ) {
        $this->http_request_factory = $http_request_factory;
        $this->http_client          = $http_client;
        $this->logger               = $status_logger;
    }

    public function emit(Payload $payload, Webhook ...$webhooks)
    {
        $promise_responses = [];

        foreach ($webhooks as $webhook) {
            $request          = $this->buildFormURLEncodedRequest($webhook, $payload);
            $promise_response = $this->http_client->sendAsyncRequest($request);

            $promise_response->then(function (ResponseInterface $response) use ($webhook) {
                $this->logger->log($webhook, $response->getStatusCode() . ' ' . $response->getReasonPhrase());

                return $response;
            }, function (\Http\Client\Exception $http_client_exception) use ($webhook) {
                $error_message = $http_client_exception->getMessage();
                if ($http_client_exception->getCode() !== 0) {
                    $error_message = $http_client_exception->getCode() . ' ' . $error_message;
                }
                $this->logger->log($webhook, $error_message);

                throw $http_client_exception;
            });

            $promise_responses[] = $promise_response;
        }

        foreach ($promise_responses as $promise_response) {
            $promise_response->wait($unwrap = false);
        }
    }

    /**
     * @return \Psr\Http\Message\RequestInterface
     */
    private function buildFormURLEncodedRequest(Webhook $webhook, Payload $payload)
    {
        return $this->http_request_factory->createRequest(
            'POST',
            $webhook->getUrl(),
            ['Content-Type' => 'application/x-www-form-urlencoded'],
            http_build_query(['payload' => json_encode($payload->getPayload())])
        );
    }
}
