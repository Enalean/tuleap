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
use Tracker_Artifact_Attachment_TemporaryFile;
use Tracker_Artifact_Attachment_TemporaryFileManager;
use Tuleap\DB\DBTransactionExecutor;

class TemporaryFileManagerAppendChunkTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private $empty_file;
    private $wrong_path_file;
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

        ForgeConfig::set('sys_max_size_upload', 10);
        $this->empty_file = new Tracker_Artifact_Attachment_TemporaryFile(
            1,
            'jette_lit.png',
            'random_tmpname',
            'Mugshot',
            0,
            101,
            0,
            'image/png'
        );
        touch($this->cache_dir . '/rest_attachement_temp_101_' . $this->empty_file->getTemporaryName());

        $this->wrong_path_file = new Tracker_Artifact_Attachment_TemporaryFile(
            1,
            'jette_lit.png',
            'wrong_path',
            'Mugshot',
            0,
            101,
            0,
            'image/png'
        );
    }

    public function tearDown(): void
    {
        exec('rm -rf ' . escapeshellarg($this->cache_dir));
        ForgeConfig::restore();
    }

    public function testItThrowsExceptionIfOffsetIsNotValid()
    {
        $this->expectException('Tracker_Artifact_Attachment_InvalidOffsetException');

        $this->file_manager->appendChunk(base64_encode('le content'), $this->empty_file, 2);
    }

    public function testItThrowsExceptionIfFileDoesNotExist()
    {
        $this->expectException('Tracker_Artifact_Attachment_InvalidPathException');

        $this->file_manager->appendChunk(base64_encode('le content'), $this->wrong_path_file, 1);
    }

    public function testItWritesChunkOnTheDisk()
    {
        $filepath = $this->cache_dir . '/rest_attachement_temp_101_' . $this->empty_file->getTemporaryName();

        $this->dao->shouldReceive('updateFileInfo');

        $this->file_manager->appendChunk(base64_encode('le content'), $this->empty_file, 1);

        $this->assertEquals('le content', file_get_contents($filepath));
    }

    public function testItThrowsExceptionIfChunkIsTooBig()
    {
        $filepath = $this->cache_dir . '/rest_attachement_temp_101_' . $this->empty_file->getTemporaryName();
        $this->expectException('Tuleap\Tracker\Artifact\Attachment\QuotaExceededException');

        $this->file_manager->appendChunk(base64_encode('le too big content'), $this->empty_file, 1);

        $this->assertEquals('', file_get_contents($filepath));
    }
}
