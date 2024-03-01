<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
 * Copyright (c) The Codendi Team, Xerox, 2009. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Backend;

use Backend;
use BackendSystem;
use ForgeConfig;
use FRSFileFactory;
use Tuleap\GlobalLanguageMock;
use Tuleap\TemporaryTestDirectory;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;
use WikiAttachment;

final class BackendSystemTest extends TestIntegrationTestCase
{
    use GlobalLanguageMock;
    use TemporaryTestDirectory;

    protected function setUp(): void
    {
        ForgeConfig::set('tmp_dir', $this->getTmpDir() . '/var/tmp');
        ForgeConfig::set('sys_file_deletion_delay', 5);
        ForgeConfig::set('codendi_log', ForgeConfig::get('tmp_dir'));
        ForgeConfig::set('sys_incdir', ForgeConfig::get('tmp_dir'));
        ForgeConfig::set('sys_project_backup_path', ForgeConfig::get('tmp_dir'));
        ForgeConfig::set('sys_custom_incdir', ForgeConfig::get('tmp_dir'));
        ForgeConfig::set('ftp_anon_dir_prefix', $this->getTmpDir() . '/var/lib/codendi/ftp/pub');
        ForgeConfig::set('ftp_frs_dir_prefix', $this->getTmpDir() . '/var/lib/codendi/ftp/codendi');
        ForgeConfig::set('are_unix_users_disabled', 0);

        mkdir(ForgeConfig::get('tmp_dir'), 0770, true);
        mkdir(ForgeConfig::get('ftp_frs_dir_prefix'), 0770, true);
        mkdir(ForgeConfig::get('ftp_anon_dir_prefix'), 0770, true);
    }

    protected function tearDown(): void
    {
        Backend::clearInstances();
    }

    public function testConstructor(): void
    {
        self::assertNotNull(BackendSystem::instance());
    }

    public function testCleanupFrs(): void
    {
        $backend = $this->createPartialMock(BackendSystem::class, [
            'getFRSFileFactory',
            'getWikiAttachment',
        ]);

        $ff = $this->createMock(FRSFileFactory::class);
        $ff->method('moveFiles')->willReturn(true);

        $wiki = $this->createMock(WikiAttachment::class);
        $wiki->method('purgeAttachments')->willReturn(true);

        $backend->method('getFRSFileFactory')->willReturn($ff);
        $backend->method('getWikiAttachment')->willReturn($wiki);

        self::assertTrue($backend->cleanupFRS());
    }

    public function testRenameFRSFolders(): void
    {
        $backend = $this->createPartialMock(BackendSystem::class, []);

        $project = ProjectTestBuilder::aProject()->withUnixName('nameBeforeRename')->build();

        $ftp_frs_dir_prefix = ForgeConfig::get('ftp_frs_dir_prefix');
        mkdir($ftp_frs_dir_prefix . '/nameBeforeRename', 0777, true);
        mkdir($ftp_frs_dir_prefix . '/DELETED/nameBeforeRename', 0777, true);

        $backend->renameFileReleasedDirectory($project, 'nameAfterRename');

        self::assertDirectoryExists($ftp_frs_dir_prefix . '/nameAfterRename');
        self::assertDirectoryExists($ftp_frs_dir_prefix . '/DELETED/nameAfterRename');
    }
}
