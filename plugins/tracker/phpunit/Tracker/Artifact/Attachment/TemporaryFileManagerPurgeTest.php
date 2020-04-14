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
use Mockery;
use PHPUnit\Framework\TestCase;
use System_Command;
use Tracker_Artifact_Attachment_TemporaryFileManager;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\FakeDataAccessResult;

class TemporaryFileManagerPurgeTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private $file_to_delete;
    protected $file_manager;
    protected $cache_dir;

    /** @var \Tracker_Artifact_Attachment_TemporaryFileManagerDao */
    protected $dao;

    /** @var \PFUser */
    protected $user;

    public function setUp(): void
    {
        ForgeConfig::store();

        $this->cache_dir = trim(`mktemp -d -p /var/tmp cache_dir_XXXXXX`);
        ForgeConfig::set('codendi_cache_dir', $this->cache_dir);

        $this->user   = new \PFUser(['user_id' => 101, 'language_id' => 'en_US']);
        $user_manager = Mockery::mock(\UserManager::class);
        $user_manager->shouldReceive('getUserById')->with(101)->andReturn($this->user);

        $this->dao = Mockery::mock(\Tracker_Artifact_Attachment_TemporaryFileManagerDao::class, ['create' =>  1]);

        $system = new System_Command();

        $retention_delay = 3;

        $this->file_manager = new Tracker_Artifact_Attachment_TemporaryFileManager(
            $user_manager,
            $this->dao,
            $system,
            $retention_delay,
            Mockery::mock(DBTransactionExecutor::class),
        );

        $this->file_to_delete = $this->cache_dir . '/rest_attachement_temp_101_XyKoe';

        touch($this->file_to_delete);
    }

    public function tearDown(): void
    {
        exec('rm -rf ' . escapeshellarg($this->cache_dir));
        ForgeConfig::restore();
    }

    public function testItPurgesOldFiles()
    {
        $ten_days_ago = $_SERVER['REQUEST_TIME'] - 10 * 3600 * 24;
        $this->dao->shouldReceive('delete')->with(1)->once();
        $this->dao->shouldReceive('searchTemporaryFilesOlderThan')->with($_SERVER['REQUEST_TIME'] - 3 * 3600 * 24)->once()->andReturn(
            new FakeDataAccessResult(
                [
                    [
                        'id'            => 1,
                        'filename'      => 'mona_lisa.png',
                        'tempname'      => 'XyKoe',
                        'description'   => 'le smile',
                        'last_modified' => $ten_days_ago,
                        'offset'        => 1,
                        'submitted_by'  => 101,
                        'filesize'      => 12345,
                        'filetype'      => 'image/png'
                    ]
                ]
            )
        );

        $this->file_manager->purgeOldTemporaryFiles();

        $this->assertFileDoesNotExist($this->file_to_delete);
    }
}
