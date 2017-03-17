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

namespace Tuleap\Webhook;

class Emitter
{
    /**
     * @var \Http_Client
     */
    private $http_client;
    /**
     * @var StatusLogger
     */
    private $logger;

    public function __construct(\Http_Client $http_client, StatusLogger $status_logger)
    {
        $this->http_client = $http_client;
        $this->logger      = $status_logger;
    }

    public function emit(Webhook $webhook, Payload $payload)
    {
        $this->buildFormURLEncodedRequest($webhook, $payload);

        try {
            $this->http_client->doRequest();
            $this->logger->log($webhook, $this->http_client->getStatusCodeAndReasonPhrase());
        } catch (\Http_ClientException $ex) {
            $this->logger->log($webhook, $ex->getMessage());
        }
    }

    private function buildFormURLEncodedRequest(Webhook $webhook, Payload $payload)
    {
        $options = array(
            CURLOPT_URL         => $webhook->getUrl(),
            CURLOPT_POST        => true,
            CURLOPT_HEADER      => true,
            CURLOPT_FAILONERROR => false,
            CURLOPT_POSTFIELDS  => http_build_query(array('payload' => json_encode($payload->getPayload())))
        );
        $this->http_client->addOptions($options);
    }
}
