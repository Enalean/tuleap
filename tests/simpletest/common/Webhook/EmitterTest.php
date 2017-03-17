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

class EmitterTest extends \TuleapTestCase
{
    public function itEmitsThePayload()
    {
        $status_logger = mock('Tuleap\\Webhook\\StatusLogger');
        $http_client   = mock('Http_Client');
        $webhook       = mock('Tuleap\\Webhook\\Webhook');
        $payload       = mock('Tuleap\\Webhook\\Payload');
        $emitter       = new Emitter($http_client, $status_logger);

        $http_client->expectOnce('doRequest');
        $emitter->emit($webhook, $payload);
    }

    public function itLogsWhenThePayloadIsSuccessfullyEmitted()
    {
        $status_logger = mock('Tuleap\\Webhook\\StatusLogger');
        $http_client   = mock('Http_Client');
        $webhook       = mock('Tuleap\\Webhook\\Webhook');
        $payload       = mock('Tuleap\\Webhook\\Payload');
        $emitter       = new Emitter($http_client, $status_logger);

        $status_logger->expectOnce('log');
        $emitter->emit($webhook, $payload);
    }

    public function itLogsWhenThePayloadIsNotEmitted()
    {
        $status_logger = mock('Tuleap\\Webhook\\StatusLogger');
        $http_client   = mock('Http_Client');
        $webhook       = mock('Tuleap\\Webhook\\Webhook');
        $payload       = mock('Tuleap\\Webhook\\Payload');
        $emitter       = new Emitter($http_client, $status_logger);

        stub($http_client)->doRequest()->throws(new \Http_ClientException());
        $status_logger->expectOnce('log');
        $emitter->emit($webhook, $payload);
    }
}
