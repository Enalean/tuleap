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

namespace Tuleap\FullTextSearchMeilisearch\Server;

use org\bovigo\vfs\vfsStream;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class LocalMeilisearchServerTest extends TestCase
{
    public function testGivesPathToTheMasterKeyEnvFileWhenLocalServerIsInstalled(): void
    {
        $root = vfsStream::setup()->url() . '/';

        mkdir($root . 'usr/bin/', 0777, true);
        touch($root . 'usr/bin/tuleap-meilisearch');

        self::assertStringContainsString(
            '/var/lib/tuleap/fts_meilisearch_server/meilisearch-master-key.env',
            (new LocalMeilisearchServer($root))->getExpectedMasterKeyEnvFilePath() ?? ''
        );
    }

    public function testDoesNotGivePathToMasterKeyEnvFileWhenLocalServerIsNotInstalled(): void
    {
        $root = vfsStream::setup()->url() . '/';

        self::assertNull((new LocalMeilisearchServer($root))->getExpectedMasterKeyEnvFilePath());
    }
}
