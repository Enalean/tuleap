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
use PFUser;

final class BackendSystemTest extends \PHPUnit\Framework\TestCase
{
    use MockeryPHPUnitIntegration;
    use \Tuleap\TemporaryTestDirectory;

    private $initial_sys_project_backup_path;
    private $initial_sys_custom_incdir;
    private $initial_homedir_prefix;
    private $initial_grpdir_prefix;
    private $initial_ftp_anon_dir_prefix;
    private $initial_ftp_frs_dir_prefix;

    protected function setUp(): void
    {
        $GLOBALS['codendi_shell_skel']        = __DIR__ . '/_fixtures/etc/skel_codendi';
        $GLOBALS['tmp_dir']                   = $this->getTmpDir() . '/var/tmp';
        $GLOBALS['ftp_frs_dir_prefix']        = $this->getTmpDir() . '/var/lib/codendi/ftp/codendi';
        $GLOBALS['sys_file_deletion_delay']   = 5;
        $GLOBALS['codendi_log']               = $GLOBALS['tmp_dir'];
        ForgeConfig::set('sys_incdir', $GLOBALS['tmp_dir']);

        $this->initial_sys_project_backup_path = ForgeConfig::get('sys_project_backup_path');
        ForgeConfig::set('sys_project_backup_path', $GLOBALS['tmp_dir']);
        $this->initial_sys_custom_incdir = ForgeConfig::get('sys_custom_incdir');
        ForgeConfig::set('sys_custom_incdir', $GLOBALS['tmp_dir']);
        $this->initial_homedir_prefix = ForgeConfig::get('homedir_prefix');
        ForgeConfig::set('homedir_prefix', $this->getTmpDir() . '/home/users');
        $this->initial_grpdir_prefix = ForgeConfig::get('grpdir_prefix');
        ForgeConfig::set('grpdir_prefix', $this->getTmpDir() . '/home/groups');
        $this->initial_ftp_anon_dir_prefix = ForgeConfig::get('ftp_anon_dir_prefix');
        ForgeConfig::set('ftp_anon_dir_prefix', $this->getTmpDir() . '/var/lib/codendi/ftp/pub');
        $this->initial_ftp_frs_dir_prefix = ForgeConfig::get('ftp_frs_dir_prefix');
        ForgeConfig::set('ftp_frs_dir_prefix', $GLOBALS['ftp_frs_dir_prefix']);

        mkdir(ForgeConfig::get('homedir_prefix'), 0770, true);
        mkdir(ForgeConfig::get('grpdir_prefix'), 0770, true);
        mkdir($GLOBALS['tmp_dir'], 0770, true);
        mkdir($GLOBALS['ftp_frs_dir_prefix'], 0770, true);
        mkdir(ForgeConfig::get('ftp_anon_dir_prefix'), 0770, true);
    }


    protected function tearDown(): void
    {
        Backend::clearInstances();
        unset($GLOBALS['codendi_shell_skel'], $GLOBALS['tmp_dir'], $GLOBALS['ftp_frs_dir_prefix'], $GLOBALS['sys_file_deletion_delay'], $GLOBALS['codendi_log']);
        ForgeConfig::set('sys_project_backup_path', $this->initial_sys_project_backup_path);
        ForgeConfig::set('sys_custom_incdir', $this->initial_sys_custom_incdir);
        ForgeConfig::set('homedir_prefix', $this->initial_homedir_prefix);
        ForgeConfig::set('grpdir_prefix', $this->initial_grpdir_prefix);
        ForgeConfig::set('ftp_anon_dir_prefix', $this->initial_ftp_anon_dir_prefix);
        ForgeConfig::set('ftp_frs_dir_prefix', $this->initial_ftp_frs_dir_prefix);
    }

    public function testConstructor(): void
    {
        $this->assertNotNull(BackendSystem::instance());
    }


    public function testCreateUserHome(): void
    {
        // We use codendiadm uid/gid to avoid chown warnings (because test is not run as root)
        $user = new PFUser([
            'language_id' => 'en',
            'user_name' => 'codendiadm',
        ]);

        $backend = \Mockery::mock(\BackendSystem::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $this->assertTrue($backend->createUserHome($user));
        $this->assertDirectoryExists(ForgeConfig::get('homedir_prefix') . "/codendiadm", "Home dir should be created");

        $this->assertTrue(is_file(ForgeConfig::get('homedir_prefix') . "/codendiadm/.profile"), "User files from /etc/codendi_skel should be created");
    }

    public function testCreateProjectHome(): void
    {
        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('getUnixNameMixedCase')->andReturns('TestPrj');
        $project->shouldReceive('getUnixName')->with(true)->andReturns('testprj');

        $pm = \Mockery::spy(\ProjectManager::class);
        $pm->shouldReceive('getProject')->with(142)->andReturns($project);

        $backend = \Mockery::mock(\BackendSystem::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $backend->shouldReceive('getProjectManager')->andReturns($pm);
        $backend->shouldReceive('chown')->with(Mockery::any(), 'dummy');
        $backend->shouldReceive('chgrp')->with(Mockery::any(), 'TestPrj');

        $projdir = ForgeConfig::get('grpdir_prefix') . "/TestPrj";
        $ftpdir = ForgeConfig::get('ftp_anon_dir_prefix') . "/TestPrj";
        $frsdir = $GLOBALS['ftp_frs_dir_prefix'] . "/TestPrj";

        $this->assertTrue($backend->createProjectHome(142));
        $this->assertDirectoryExists($projdir, "Project Home should be created");
        $this->assertDirectoryExists($ftpdir, "Ftp dir should be created");
        $this->assertDirectoryExists($frsdir, "Frs dir should be created");

        // Check that a wrong project id does not raise an error
        $this->assertEquals($backend->createProjectHome(99999), false);
    }

    public function testArchiveUserHome(): void
    {
        // We use codendiadm uid/gid to avoid chown warnings (because test is not run as root)
        $user = new PFUser([
            'language_id' => 'en',
            'user_name' => 'codendiadm',
        ]);

        $backend = \Mockery::mock(\BackendSystem::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $backend->createUserHome($user);
        $this->assertDirectoryExists(ForgeConfig::get('homedir_prefix') . "/codendiadm", "Home dir should be created");

        // Run test
        $um = \Mockery::spy(\UserManager::class);
        $um->shouldReceive('getUserById')->with(104)->andReturns($user);

        $backend->shouldReceive('getUserManager')->andReturns($um);

        $this->assertTrue($backend->archiveUserHome(104));
        $this->assertDirectoryDoesNotExist(ForgeConfig::get('homedir_prefix') . '/codendiadm', 'Home dir should be deleted');
        $this->assertTrue(is_file(ForgeConfig::get('sys_project_backup_path') . "/codendiadm.tgz"), "Archive should be created");
    }

    public function testArchiveProjectHome(): void
    {
        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('getUnixName')->with(false)->andReturns('TestProj');
        $project->shouldReceive('getUnixName')->with(true)->andReturns('testproj');

        $pm = \Mockery::spy(\ProjectManager::class);
        $pm->shouldReceive('getProject')->with(142)->andReturns($project);

        $backend = \Mockery::mock(\BackendSystem::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $backend->shouldReceive('getProjectManager')->andReturns($pm);

        $projdir = ForgeConfig::get('grpdir_prefix') . "/TestProj";
        $lcprojlnk = ForgeConfig::get('grpdir_prefix') . "/testproj";

        // Setup test data
        mkdir($projdir);
        touch($projdir . "/testfile.txt");
        symlink($projdir, $lcprojlnk);

        $this->assertTrue($backend->archiveProjectHome(142));
        $this->assertDirectoryDoesNotExist($projdir, "Project dir should be deleted");
        $this->assertFalse(is_link($lcprojlnk), "Project link should be deleted");
        $this->assertTrue(is_file(ForgeConfig::get('sys_project_backup_path') . "/TestProj.tgz"), "Archive should be created");

        // Check that a wrong project id does not raise an error
        $this->assertFalse($backend->archiveProjectHome(99999));
    }

    public function testRenameProjectHomeDirectory(): void
    {
        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('getUnixName')->with(false)->andReturns('TestProject');
        $project->shouldReceive('getUnixNameMixedCase')->andReturns('TestProject');
        $project->shouldReceive('getUnixName')->with(true)->andReturns('testproject');

        $pm = \Mockery::spy(\ProjectManager::class);
        $pm->shouldReceive('getProject')->with(142)->andReturns($project);

        $backend = \Mockery::mock(\BackendSystem::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $backend->shouldReceive('getProjectManager')->andReturns($pm);
        $backend->shouldReceive('chown')->with(Mockery::any(), 'dummy');
        $backend->shouldReceive('chgrp')->with(Mockery::any(), 'TestProject');

        $backend->createProjectHome(142);

        $this->assertTrue($backend->renameProjectHomeDirectory($project, "FooBar"));

        $this->assertFileDoesNotExist(ForgeConfig::get('grpdir_prefix') . "/TestProject", 'Old project home should no longer exists');
        $this->assertDirectoryExists(ForgeConfig::get('grpdir_prefix') . "/FooBar", "Project home should be renamed");

        $this->assertFileDoesNotExist(ForgeConfig::get('grpdir_prefix') . "/testproject", 'Old project home lowercase version should no longer exists');
        $this->assertTrue(is_link(ForgeConfig::get('grpdir_prefix') . "/foobar"), "Project home lowercase version should be renamed");
        $this->assertEquals(readlink(ForgeConfig::get('grpdir_prefix') . "/foobar"), ForgeConfig::get('grpdir_prefix') . "/FooBar", "Project home lowercase version should be link to the uppercase version");
    }

    /**
     * Special case when the project rename is just about changing case
     * TestProject -> testproject
     */
    public function testRenameProjectHomeDirectoryToLowerCase(): void
    {
        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('getUnixName')->with(false)->andReturns('TestProject');
        $project->shouldReceive('getUnixNameMixedCase')->andReturns('TestProject');
        $project->shouldReceive('getUnixName')->with(true)->andReturns('testproject');

        $pm = \Mockery::spy(\ProjectManager::class);
        $pm->shouldReceive('getProject')->with(142)->andReturns($project);

        $backend = \Mockery::mock(\BackendSystem::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $backend->shouldReceive('getProjectManager')->andReturns($pm);
        $backend->shouldReceive('chown')->with(Mockery::any(), 'dummy');
        $backend->shouldReceive('chgrp')->with(Mockery::any(), 'TestProject');

        $backend->createProjectHome(142);

        $this->assertTrue($backend->renameProjectHomeDirectory($project, "testproject"));

        $this->assertFileDoesNotExist(ForgeConfig::get('grpdir_prefix') . "/TestProject", 'Old project home should no longer exists');
        $this->assertDirectoryExists(ForgeConfig::get('grpdir_prefix') . "/testproject", 'Project home should be renamed');
    }

   /**
     * Special case when the project rename is just about changing case
     * testproject -> TestProject
     */
    public function testRenameProjectHomeDirectoryToUpperCase(): void
    {
        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('getUnixName')->with(false)->andReturns('testproject');
        $project->shouldReceive('getUnixNameMixedCase')->andReturns('testproject');
        $project->shouldReceive('getUnixName')->with(true)->andReturns('testproject');

        $pm = \Mockery::spy(\ProjectManager::class);
        $pm->shouldReceive('getProject')->with(142)->andReturns($project);

        $backend = \Mockery::mock(\BackendSystem::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $backend->shouldReceive('getProjectManager')->andReturns($pm);
        $backend->shouldReceive('chown')->with(Mockery::any(), 'dummy');
        $backend->shouldReceive('chgrp')->with(Mockery::any(), 'testproject');

        $backend->createProjectHome(142);

        $this->assertTrue($backend->renameProjectHomeDirectory($project, "TestProject"));

        // Not test possible with is_dir because is_dir resolve the link.
        // Testing lower case as a link is enough (see below).
        //$this->assertFalse(is_dir(ForgeConfig::get('grpdir_prefix')."/testproject"), "Old project home should no longer exists as directory (it's a link now)");
        $this->assertDirectoryExists(ForgeConfig::get('grpdir_prefix') . "/TestProject", 'Project home should be renamed');
        $this->assertEquals(readlink(ForgeConfig::get('grpdir_prefix') . '/testproject'), ForgeConfig::get('grpdir_prefix') . '/TestProject', "The lower case of project should be a link");
    }

    /**
     * testproject -> projecttest
     */
    public function testRenameProjectHomeDirectoryLowerCase(): void
    {
        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('getUnixName')->with(false)->andReturns('testproject');
        $project->shouldReceive('getUnixNameMixedCase')->andReturns('testproject');
        $project->shouldReceive('getUnixName')->with(true)->andReturns('testproject');

        $pm = \Mockery::spy(\ProjectManager::class);
        $pm->shouldReceive('getProject')->with(142)->andReturns($project);

        $backend = \Mockery::mock(\BackendSystem::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $backend->shouldReceive('getProjectManager')->andReturns($pm);
        $backend->shouldReceive('chown')->with(Mockery::any(), 'dummy');
        $backend->shouldReceive('chgrp')->with(Mockery::any(), 'testproject');


        $backend->createProjectHome(142);

        $this->assertTrue($backend->renameProjectHomeDirectory($project, "projecttest"));

        $this->assertFileDoesNotExist(ForgeConfig::get('grpdir_prefix') . "/testproject", 'Old project home should no longer exists');
        $this->assertDirectoryExists(ForgeConfig::get('grpdir_prefix') . "/projecttest", 'Project home should be renamed');
    }

    public function testIsProjectNameAvailableWithExistingFileInProjectHome(): void
    {
        touch(ForgeConfig::get('grpdir_prefix') . "/testproject");
        $backend = \Mockery::mock(\BackendSystem::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->assertFalse($backend->isProjectNameAvailable('testproject'), 'A file with the same name exists in home/groups/');
    }

    public function testIsProjectNameAvailableWithExistingFileInProjectHomeWithMixedCase(): void
    {
        touch(ForgeConfig::get('grpdir_prefix') . "/testproject");
        $backend = \Mockery::mock(\BackendSystem::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->assertFalse($backend->isProjectNameAvailable('TestProject'), 'A file with the same name in lowercase exists in home/groups/');
    }

    public function testIsProjectNameAvailableWithExistingFileInFRS(): void
    {
        touch($GLOBALS['ftp_frs_dir_prefix'] . "/testproject");
        $backend = \Mockery::mock(\BackendSystem::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->assertFalse($backend->isProjectNameAvailable('testproject'), 'A file with the same name exists in var/lib/codendi/ftp/codendi');
    }

    public function testIsProjectNameAvailableWithExistingFileInAnnoFtp(): void
    {
        touch(ForgeConfig::get('ftp_anon_dir_prefix') . "/testproject");
        $backend = \Mockery::mock(\BackendSystem::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->assertFalse($backend->isProjectNameAvailable('testproject'), 'A file with the same name exists in var/lib/codendi/ftp/pub');
    }

    public function testRenameUserHomeDirectory(): void
    {
        // We use codendiadm uid/gid to avoid chown warnings (because test is not run as root)
        $user = new PFUser([
            'language_id' => 'en',
            'user_name' => 'codendiadm',
        ]);

        $backend = \Mockery::mock(\BackendSystem::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $backend->createUserHome($user);
        $this->assertTrue($backend->renameUserHomeDirectory($user, 'toto'));
        $this->assertDirectoryExists(ForgeConfig::get('homedir_prefix') . "/toto", "Home dir should be created");

        $this->assertDirectoryDoesNotExist(ForgeConfig::get('homedir_prefix') . "/codendiadm", 'Home dir should no more exists');
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
}
