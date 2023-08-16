<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\FullTextSearchMeilisearch\Index;

use Meilisearch\Endpoints\Indexes;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\FullTextSearchCommon\Index\NullIndexHandler;
use Tuleap\FullTextSearchMeilisearch\Server\LocalMeilisearchServer;
use Tuleap\FullTextSearchMeilisearch\Server\RemoteMeilisearchServerSettings;

final class MeilisearchHandlerFactory
{
    private const LOCAL_INDEX_NAME = 'fts_tuleap';

    public function __construct(
        private LoggerInterface $logger,
        private LocalMeilisearchServer $local_meilisearch_server,
        private MeilisearchMetadataDAO $metadata_dao,
        private RequestFactoryInterface $request_factory,
        private readonly StreamFactoryInterface $stream_factory,
        private ClientInterface $client_for_local_use,
        private ClientInterface $client_for_remote_use,
    ) {
    }

    public function buildHandler(): MeilisearchHandler|NullIndexHandler
    {
        $key_local_meilisearch_server = $this->local_meilisearch_server->getCurrentKey();

        if ($key_local_meilisearch_server !== null) {
            $this->logger->debug('Using the local Meilisearch instance');
            return new MeilisearchHandler($this->getClientIndexForLocalMeilisearchInstance($key_local_meilisearch_server), $this->metadata_dao);
        }

        $remote_meilisearch_server_url = \ForgeConfig::get(RemoteMeilisearchServerSettings::URL, '');
        if ($remote_meilisearch_server_url !== '' && \ForgeConfig::exists(RemoteMeilisearchServerSettings::API_KEY)) {
            $this->logger->debug('Using the remote Meilisearch instance ' . $remote_meilisearch_server_url);
            return new MeilisearchHandler($this->getClientIndexForRemoteMeilisearchInstance(), $this->metadata_dao);
        }

        $this->logger->debug('No local or remote Meilisearch instance available, do nothing');
        return new NullIndexHandler();
    }

    private function getClientIndexForLocalMeilisearchInstance(ConcealedString $key): Indexes
    {
        return (new \Meilisearch\Client(
            'http://127.0.0.1:7700',
            $key->getString(),
            $this->client_for_local_use,
            $this->request_factory,
            [],
            $this->stream_factory,
        ))->index(self::LOCAL_INDEX_NAME);
    }

    private function getClientIndexForRemoteMeilisearchInstance(): Indexes
    {
        return (new \Meilisearch\Client(
            \ForgeConfig::get(RemoteMeilisearchServerSettings::URL),
            \ForgeConfig::getSecretAsClearText(RemoteMeilisearchServerSettings::API_KEY)->getString(),
            $this->client_for_remote_use,
            $this->request_factory,
            [],
            $this->stream_factory,
        ))->index(\ForgeConfig::get(RemoteMeilisearchServerSettings::INDEX_NAME));
    }
}
