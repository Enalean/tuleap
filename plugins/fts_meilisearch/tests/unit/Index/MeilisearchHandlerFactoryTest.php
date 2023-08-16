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

use org\bovigo\vfs\vfsStream;
use Psr\Log\NullLogger;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\ForgeConfigSandbox;
use Tuleap\FullTextSearchCommon\Index\NullIndexHandler;
use Tuleap\FullTextSearchMeilisearch\Server\LocalMeilisearchServer;
use Tuleap\Http\HttpClientFactory;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class MeilisearchHandlerFactoryTest extends TestCase
{
    use ForgeConfigSandbox;

    public function testBuildsMeilisearchHandlerWhenLocalInstanceIsAvailable(): void
    {
        $root = vfsStream::setup()->url() . '/';

        mkdir($root . '/usr/bin/', 0777, true);
        touch($root . '/usr/bin/tuleap-meilisearch');
        mkdir($root . '/var/lib/tuleap/fts_meilisearch_server/', 0777, true);
        file_put_contents($root . '/var/lib/tuleap/fts_meilisearch_server/meilisearch-master-key.env', 'MEILI_MASTER_KEY=foo');

        $factory = $this->buildFactory($root);

        $handler = $factory->buildHandler();

        self::assertInstanceOf(MeilisearchHandler::class, $handler);
    }

    public function testBuildsRemoteMeilisearchHandlerWhenLocalInstanceIsNotAvailableAndRemoteInformationAreSet(): void
    {
        $root = vfsStream::setup('root', null, ['conf' => []])->url();
        \ForgeConfig::set('fts_meilisearch_server_url', 'https://example.com');
        \ForgeConfig::set('sys_custom_dir', $root);
        \ForgeConfig::set('fts_meilisearch_api_key', \ForgeConfig::encryptValue(new ConcealedString('something')));
        \ForgeConfig::set('fts_meilisearch_index_name', 'index_name');
        $factory = $this->buildFactory($root . '/');

        $handler = $factory->buildHandler();

        self::assertInstanceOf(MeilisearchHandler::class, $handler);
    }

    public function testBuildsNullHandlerWhenNoInstanceIsAvailable(): void
    {
        $factory = $this->buildFactory(vfsStream::setup()->url() . '/');

        $handler = $factory->buildHandler();

        self::assertInstanceOf(NullIndexHandler::class, $handler);
    }

    private function buildFactory(string $root_dir): MeilisearchHandlerFactory
    {
        return new MeilisearchHandlerFactory(
            new NullLogger(),
            new LocalMeilisearchServer($root_dir),
            $this->createStub(MeilisearchMetadataDAO::class),
            HTTPFactoryBuilder::requestFactory(),
            HTTPFactoryBuilder::streamFactory(),
            HttpClientFactory::createClientForInternalTuleapUse(),
            HttpClientFactory::createClient(),
        );
    }
}
