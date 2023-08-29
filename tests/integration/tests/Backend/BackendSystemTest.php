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
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\GlobalLanguageMock;

final class BackendSystemTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;
    use MockeryPHPUnitIntegration;
    use \Tuleap\TemporaryTestDirectory;

    private $initial_sys_project_backup_path;
    private $initial_sys_custom_incdir;
    private $initial_ftp_anon_dir_prefix;
    private $initial_ftp_frs_dir_prefix;
    private $initial_tmp_dir;
    private $initial_sys_file_deletion_delay;
    private $initial_codendi_log;
    private $initial_sys_incdir;

    protected function setUp(): void
    {
        $this->initial_tmp_dir = $this->getTmpDir() . '/var/tmp';
        ForgeConfig::set('tmp_dir', $this->getTmpDir() . '/var/tmp');
        $this->initial_sys_file_deletion_delay = ForgeConfig::get('sys_file_deletion_delay');
        ForgeConfig::set('sys_file_deletion_delay', 5);
        $this->initial_codendi_log = ForgeConfig::get('codendi_log');
        ForgeConfig::set('codendi_log', ForgeConfig::get('tmp_dir'));
        $this->initial_sys_incdir = ForgeConfig::get('sys_incdir');
        ForgeConfig::set('sys_incdir', ForgeConfig::get('tmp_dir'));
        $this->initial_sys_project_backup_path = ForgeConfig::get('sys_project_backup_path');
        ForgeConfig::set('sys_project_backup_path', ForgeConfig::get('tmp_dir'));
        $this->initial_sys_custom_incdir = ForgeConfig::get('sys_custom_incdir');
        ForgeConfig::set('sys_custom_incdir', ForgeConfig::get('tmp_dir'));
        $this->initial_ftp_anon_dir_prefix = ForgeConfig::get('ftp_anon_dir_prefix');
        ForgeConfig::set('ftp_anon_dir_prefix', $this->getTmpDir() . '/var/lib/codendi/ftp/pub');
        $this->initial_ftp_frs_dir_prefix = ForgeConfig::get('ftp_frs_dir_prefix');
        ForgeConfig::set('ftp_frs_dir_prefix', $this->getTmpDir() . '/var/lib/codendi/ftp/codendi');
        ForgeConfig::set('are_unix_users_disabled', 0);

        mkdir(ForgeConfig::get('tmp_dir'), 0770, true);
        mkdir(ForgeConfig::get('ftp_frs_dir_prefix'), 0770, true);
        mkdir(ForgeConfig::get('ftp_anon_dir_prefix'), 0770, true);
    }

    protected function tearDown(): void
    {
        Backend::clearInstances();
        ForgeConfig::set('sys_project_backup_path', $this->initial_sys_project_backup_path);
        ForgeConfig::set('sys_custom_incdir', $this->initial_sys_custom_incdir);
        ForgeConfig::set('ftp_anon_dir_prefix', $this->initial_ftp_anon_dir_prefix);
        ForgeConfig::set('ftp_frs_dir_prefix', $this->initial_ftp_frs_dir_prefix);
        ForgeConfig::set('tmp_dir', $this->initial_tmp_dir);
        ForgeConfig::set('sys_file_deletion_delay', $this->initial_sys_file_deletion_delay);
        ForgeConfig::set('codendi_log', $this->initial_codendi_log);
        ForgeConfig::set('sys_incdir', $this->initial_sys_incdir);
    }

    public function testConstructor(): void
    {
        $this->assertNotNull(BackendSystem::instance());
    }

    public function testCleanupFrs(): void
    {
        $backend = \Mockery::mock(\BackendSystem::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $ff = \Mockery::mock(FRSFileFactory::class);
        $ff->shouldReceive('moveFiles')->andReturn(true);

        $wiki = \Mockery::spy(\WikiAttachment::class);
        $wiki->shouldReceive('purgeAttachments')->andReturns(true);

        $backend->shouldReceive('getFRSFileFactory')->andReturns($ff);
        $backend->shouldReceive('getWikiAttachment')->andReturns($wiki);

        $this->assertTrue($backend->cleanupFRS());
    }

    public function testRenameFRSFolders(): void
    {
        $backend = \Mockery::mock(\BackendSystem::class)->makePartial();

        $project = Mockery::mock(\Project::class);
        $project->shouldReceive('getUnixName')->andReturn('nameBeforeRename');

        $ftp_frs_dir_prefix = ForgeConfig::get('ftp_frs_dir_prefix');
        mkdir($ftp_frs_dir_prefix . '/nameBeforeRename', 0777, true);
        mkdir($ftp_frs_dir_prefix . '/DELETED/nameBeforeRename', 0777, true);

        $backend->renameFileReleasedDirectory($project, 'nameAfterRename');

        $this->assertDirectoryExists($ftp_frs_dir_prefix . '/nameAfterRename');
        $this->assertDirectoryExists($ftp_frs_dir_prefix . '/DELETED/nameAfterRename');
    }
}
