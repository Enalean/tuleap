<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
declare(strict_types=1);
namespace Tuleap\RealTimeMercure;

use Psr\Log\LoggerInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Tuleap\Config\ConfigKeyInt;
use Tuleap\Config\FeatureFlagConfigKey;
use Tuleap\JWT\generators\MercureJWTGenerator;

class MercureClient implements Client
{
    #[FeatureFlagConfigKey("Feature flag to enable mercure based real time in kanban ")]
    public const FEATURE_FLAG_KANBAN_KEY = 'enable_mercure_dev_kanban';

    #[FeatureFlagConfigKey("Feature flag to enable mercure based real time in TestManagement")]
    public const FEATURE_FLAG_TESTMANAGEMENT_KEY = 'enable_mercure_dev_testmanagement';
    #[ConfigKeyInt(0)]

    private const MERCURE_LOCAL_URL = "http://localhost:3000/.well-known/mercure";

    public function __construct(
        private ClientInterface $http_client,
        private RequestFactoryInterface $request_factory,
        private StreamFactoryInterface $stream_factory,
        private LoggerInterface $logger,
        private MercureJWTGenerator $jwt_generator,
    ) {
    }

    /**
     * Method to send an Https request when
     * want to broadcast a message
     *
     * @param $message (MercureMessageDataPresenter) : Message to send to Mercure server
     * @throws \JsonException
     */
    public function sendMessage(MercureMessageDataPresenter $message): void
    {
        $request_table = [
            'data' => json_encode($message->data, JSON_THROW_ON_ERROR),
            'topic' => $message->topic,
            'private' => 'on',
        ];
        $auth_string   = $this->jwt_generator->getTokenBackend();
        if ($auth_string === null) {
            $this->logger->error('Error while generating mercure authentication token generation');
            return;
        }
        $request_body = $this->stream_factory->createStream(http_build_query($request_table));
        $request      = $this->request_factory->createRequest('POST', self::MERCURE_LOCAL_URL)
            ->withHeader('Authorization', 'Bearer ' . $auth_string->getString())
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded;charset=UTF-8')
            ->withBody($request_body);
        try {
            $response = $this->http_client->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            $this->logger->error('Not able to send a message to the Mercure server', ['exception' => $e]);
            return;
        }

        $status_code = $response->getStatusCode();
        if ($status_code !== 200) {
            $this->logger->error(sprintf('Mercure server has not processed a message: %d %s', $status_code, $response->getReasonPhrase()));
        }
    }
}
