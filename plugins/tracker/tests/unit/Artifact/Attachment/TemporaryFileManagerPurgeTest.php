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
use PHPUnit\Framework\MockObject\MockObject;
use System_Command;
use TestHelper;
use Tracker_Artifact_Attachment_TemporaryFileManager;
use Tracker_Artifact_Attachment_TemporaryFileManagerDao;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\RetrieveUserByIdStub;

final class TemporaryFileManagerPurgeTest extends TestCase
{
    use ForgeConfigSandbox;

    private string $file_to_delete;
    private Tracker_Artifact_Attachment_TemporaryFileManager $file_manager;
    private string $cache_dir;
    private Tracker_Artifact_Attachment_TemporaryFileManagerDao&MockObject $dao;

    public function setUp(): void
    {
        $this->cache_dir = trim(`mktemp -d -p /var/tmp cache_dir_XXXXXX`);
        ForgeConfig::set('codendi_cache_dir', $this->cache_dir);

        $this->dao = $this->createMock(Tracker_Artifact_Attachment_TemporaryFileManagerDao::class);
        $this->dao->method('create')->willReturn(1);

        $this->file_manager = new Tracker_Artifact_Attachment_TemporaryFileManager(
            RetrieveUserByIdStub::withUser(new PFUser(['user_id' => 101, 'language_id' => 'en_US'])),
            $this->dao,
            new System_Command(),
            3,
            new DBTransactionExecutorPassthrough(),
        );

        $this->file_to_delete = $this->cache_dir . '/rest_attachement_temp_101_XyKoe';

        touch($this->file_to_delete);
    }

    public function tearDown(): void
    {
        exec('rm -rf ' . escapeshellarg($this->cache_dir));
    }

    public function testItPurgesOldFiles(): void
    {
        $ten_days_ago = $_SERVER['REQUEST_TIME'] - 10 * 3600 * 24;
        $this->dao->expects(self::once())->method('delete')->with(1);
        $this->dao->expects(self::once())->method('searchTemporaryFilesOlderThan')->with($_SERVER['REQUEST_TIME'] - 3 * 3600 * 24)->willReturn(
            TestHelper::argListToDar([
                [
                    'id'            => 1,
                    'filename'      => 'mona_lisa.png',
                    'tempname'      => 'XyKoe',
                    'description'   => 'le smile',
                    'last_modified' => $ten_days_ago,
                    'offset'        => 1,
                    'submitted_by'  => 101,
                    'filesize'      => 12345,
                    'filetype'      => 'image/png',
                ],
            ])
        );

        $this->file_manager->purgeOldTemporaryFiles();

        self::assertFileDoesNotExist($this->file_to_delete);
    }
}
