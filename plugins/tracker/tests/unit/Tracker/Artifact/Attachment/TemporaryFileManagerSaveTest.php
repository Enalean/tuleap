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
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;

class TemporaryFileManagerSaveTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

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
            new DBTransactionExecutorPassthrough(),
        );

        ForgeConfig::set('sys_max_size_upload', 10);
    }

    public function tearDown(): void
    {
        exec('rm -rf ' . escapeshellarg($this->cache_dir));
        ForgeConfig::restore();
    }

    public function testItCanSaveATemporaryFilesIfQuotaIsNotExceeded()
    {
        file_put_contents($this->cache_dir . '/rest_attachement_temp_101_mona_lisa.png', 'Content');

        $temporary = $this->file_manager->save($this->user, 'jette_lit.png', 'Mugshot', 'image/png');

        $this->assertEquals('jette_lit.png', $temporary->getName());
    }

    public function testItCanSaveATemporaryFilesIfQuotaIsExceededBySomeoneElse()
    {
        file_put_contents($this->cache_dir . '/rest_attachement_temp_102_mona_lisa.png', 'Content that exceed quota');

        $temporary = $this->file_manager->save($this->user, 'jette_lit.png', 'Mugshot', 'image/png');

        $this->assertEquals('jette_lit.png', $temporary->getName());
    }

    public function testItCannotSaveATemporaryFilesIfQuotaIsExceeded()
    {
        file_put_contents($this->cache_dir . '/rest_attachement_temp_101_mona_lisa.png', 'Content that exceed quota');

        $this->expectException(\Tuleap\Tracker\Artifact\Attachment\QuotaExceededException::class);

        $this->file_manager->save($this->user, 'jette_lit.png', 'Mugshot', 'image/png');
    }
}
