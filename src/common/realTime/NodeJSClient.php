<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\RealTime;

use ForgeConfig;
use Psr\Log\LoggerInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class NodeJSClient implements Client
{
    /**
     * @var ClientInterface
     */
    private $http_client;
    /**
     * @var RequestFactoryInterface
     */
    private $request_factory;
    /**
     * @var StreamFactoryInterface
     */
    private $stream_factory;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var string|null
     */
    private $url;

    public function __construct(
        ClientInterface $http_client,
        RequestFactoryInterface $request_factory,
        StreamFactoryInterface $stream_factory,
        LoggerInterface $logger
    ) {
        $this->http_client     = $http_client;
        $this->request_factory = $request_factory;
        $this->stream_factory  = $stream_factory;
        $nodejs_server_address = $this->getNodeJsServerAddress();
        $this->logger          = $logger;
        if ($nodejs_server_address !== '') {
            $this->url = 'https://' . $nodejs_server_address;
        }
    }

    /**
     * Method to send an Https request when
     * want to broadcast a message
     *
     * @param $message (MessageDataPresenter) : Message to send to Node.js server
     */
    public function sendMessage(MessageDataPresenter $message) : void
    {
        if ($this->url === null) {
            return;
        }

        $request = $this->request_factory->createRequest('POST', $this->url . '/message')
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->stream_factory->createStream(json_encode($message)));

        try {
            $response = $this->http_client->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            $this->logger->error(
                sprintf('Not able to send a message to the realtime NodeJS server (%s): %s', $this->url, $e->getMessage())
            );
            return;
        }

        $status_code = $response->getStatusCode();
        if ($status_code !== 200) {
            $this->logger->error(
                sprintf(
                    'Realtime NodeJS server (%s) has not processed a message: %d %s',
                    $this->url,
                    $status_code,
                    $response->getReasonPhrase()
                )
            );
        }
    }

    private function getNodeJsServerAddress() : string
    {
        return ForgeConfig::get('nodejs_server_int') !== '' ?
            ForgeConfig::get('nodejs_server_int') : ForgeConfig::get('nodejs_server');
    }
}
