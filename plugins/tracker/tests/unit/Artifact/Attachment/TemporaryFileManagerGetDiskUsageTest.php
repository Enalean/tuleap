<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Artifact\Attachment;

use ForgeConfig;
use PFUser;
use System_Command;
use Tracker_Artifact_Attachment_TemporaryFileManager;
use Tracker_Artifact_Attachment_TemporaryFileManagerDao;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\RetrieveUserByIdStub;

final class TemporaryFileManagerGetDiskUsageTest extends TestCase
{
    use ForgeConfigSandbox;

    private Tracker_Artifact_Attachment_TemporaryFileManager $file_manager;
    private string $cache_dir;
    private PFUser $user;

    public function setUp(): void
    {
        $this->cache_dir = trim(`mktemp -d -p /var/tmp cache_dir_XXXXXX`);
        ForgeConfig::set('codendi_cache_dir', $this->cache_dir);

        $this->user = new PFUser(['user_id' => 101, 'language_id' => 'en_US']);

        $dao = $this->createMock(Tracker_Artifact_Attachment_TemporaryFileManagerDao::class);
        $dao->method('create')->willReturn(1);

        $this->file_manager = new Tracker_Artifact_Attachment_TemporaryFileManager(
            RetrieveUserByIdStub::withUser($this->user),
            $dao,
            new System_Command(),
            3,
            new DBTransactionExecutorPassthrough(),
        );
    }

    public function tearDown(): void
    {
        exec('rm -rf ' . escapeshellarg($this->cache_dir));
    }

    public function testItReturns0WhenNoFiles(): void
    {
        self::assertEquals(0, $this->file_manager->getDiskUsage($this->user));
    }

    public function testItReturnsTheSizeOfTheOnlyFile(): void
    {
        file_put_contents($this->cache_dir . '/rest_attachement_temp_101_mona_lisa.png', 'Content');

        self::assertEquals(7, $this->file_manager->getDiskUsage($this->user));
    }

    public function testItSumsUpAllTheFiles(): void
    {
        file_put_contents($this->cache_dir . '/rest_attachement_temp_101_mona_lisa.png', 'Content');
        file_put_contents($this->cache_dir . '/rest_attachement_temp_101_liza_monet.png', 'Another content');

        self::assertEquals(22, $this->file_manager->getDiskUsage($this->user));
    }

    public function testItSumsOnlyCurrentUserFiles(): void
    {
        file_put_contents($this->cache_dir . '/rest_attachement_temp_101_mona_lisa.png', 'Content');
        file_put_contents($this->cache_dir . '/rest_attachement_temp_101_liza_monet.png', 'Another content');
        file_put_contents($this->cache_dir . '/rest_attachement_temp_102_hannibal_lecteur.png', 'Whatever');

        self::assertEquals(22, $this->file_manager->getDiskUsage($this->user));
    }
}
