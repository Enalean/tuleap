<?php
/**
 * Copyright (c) Enalean 2011 - Present. All rights reserved
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
use BackendCVS;
use ForgeConfig;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\TemporaryTestDirectory;

final class BackendCVSTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use TemporaryTestDirectory;

    private $initial_sys_project_backup_path;
    private $initial_codendi_log;

    protected function setUp(): void
    {
        mkdir($this->getTmpDir() . '/var/lock/cvs', 0770, true);
        mkdir($this->getTmpDir() . '/cvsroot');
        mkdir($this->getTmpDir() . '/tmp');
        copy(__DIR__ . '/_fixtures/cvsroot/loginfo.cvsnt', $this->getTmpDir() . '/cvsroot/loginfo.cvsnt');
        $GLOBALS['cvs_prefix']                 = $this->getTmpDir() . '/cvsroot';
        $GLOBALS['cvslock_prefix']             = $this->getTmpDir() . '/var/lock/cvs';
        $GLOBALS['tmp_dir']                    = $this->getTmpDir() . '/tmp';
        $GLOBALS['cvs_cmd']                    = "/usr/bin/cvs";
        $GLOBALS['cvs_root_allow_file']        = $this->getTmpDir() . '/cvs_root_allow';
        $GLOBALS['codendi_bin_prefix']         = ForgeConfig::get('codendi_bin_prefix');
        $this->initial_sys_project_backup_path = ForgeConfig::get('sys_project_backup_path');
        ForgeConfig::set('sys_project_backup_path', $this->getTmpDir() . '/tmp');
        $this->initial_codendi_log = ForgeConfig::get('codendi_log');
        ForgeConfig::set('codendi_log', $this->getTmpDir());
        mkdir($GLOBALS['cvs_prefix'] . '/' . 'toto');
    }


    protected function tearDown(): void
    {
        Backend::clearInstances();
        ForgeConfig::set('sys_project_backup_path', $this->initial_sys_project_backup_path);
        ForgeConfig::set('codendi_log', $this->initial_codendi_log);
        unset(
            $GLOBALS['cvs_prefix'],
            $GLOBALS['cvslock_prefix'],
            $GLOBALS['tmp_dir'],
            $GLOBALS['cvs_cmd'],
            $GLOBALS['cvs_root_allow_file'],
            $GLOBALS['codendi_bin_prefix']
        );
    }

    public function testConstructor(): void
    {
        $this->assertNotNull(BackendCVS::instance());
    }


    public function testArchiveProjectCVS(): void
    {
        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('getUnixName')->with(false)->andReturns('TestProj');
        $project->shouldReceive('getUnixName')->with(true)->andReturns('testproj');

        $pm = \Mockery::spy(\ProjectManager::class);
        $pm->shouldReceive('getProject')->with(142)->andReturns($project);

        $backend = \Mockery::mock(\BackendCVS::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $backend->shouldReceive('getProjectManager')->andReturns($pm);

        $projdir = $GLOBALS['cvs_prefix'] . "/TestProj";

        // Setup test data
        mkdir($projdir);
        mkdir($projdir . "/CVSROOT");

        $this->assertTrue($backend->archiveProjectCVS(142));
        $this->assertDirectoryDoesNotExist($projdir, 'Project CVS repository should be deleted');
        $this->assertFileExists(ForgeConfig::get('sys_project_backup_path') . '/TestProj-cvs.tgz', 'CVS Archive should be created');

        // Check that a wrong project id does not raise an error
        $this->assertFalse($backend->archiveProjectCVS(99999));
    }

    public function testCreateProjectCVS(): void
    {
        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('getUnixName')->with(false)->andReturns('TestProj');
        $project->shouldReceive('getUnixName')->with(true)->andReturns('testproj');
        $project->shouldReceive('isCVSTracked')->andReturns(true);
        $proj_members = array("0" =>
                              array (
                                     "user_name" => "user1",
                                     "user_id"  => "1"),
                              "1" =>
                              array (
                                     "user_name" => "user2",
                                     "user_id"  => "2"),
                              "2" =>
                              array (
                                     "user_name" => "user3",
                                     "user_id"  => "3"));

        $project->shouldReceive('getMembersUserNames')->andReturns($proj_members);

        $pm = \Mockery::spy(\ProjectManager::class);
        $pm->shouldReceive('getProject')->with(142)->andReturns($project);

        $backend = \Mockery::mock(\BackendCVS::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $backend->shouldReceive('getProjectManager')->andReturns($pm);
        $backend->shouldReceive('chown');
        $backend->shouldReceive('chgrp');
        $backend->shouldReceive('system')->with('chown -R :TestProj ' . $GLOBALS['cvs_prefix'] . '/TestProj')->once();

        $this->assertTrue($backend->createProjectCVS(142));
        $this->assertDirectoryExists($GLOBALS['cvs_prefix'] . '/TestProj', 'CVS dir should be created');
        $this->assertDirectoryExists($GLOBALS['cvs_prefix'] . '/TestProj/CVSROOT', 'CVSROOT dir should be created');
        $this->assertFileExists($GLOBALS['cvs_prefix'] . '/TestProj/CVSROOT/loginfo', 'loginfo file should be created');

        $commitinfo_file = file($GLOBALS['cvs_prefix'] . '/TestProj/CVSROOT/commitinfo');
        $this->assertContains($backend->block_marker_start, $commitinfo_file, 'commitinfo file should contain block');

        $commitinfov_file = file($GLOBALS['cvs_prefix'] . '/TestProj/CVSROOT/commitinfo,v');
        $this->assertContains($backend->block_marker_start, $commitinfov_file, 'commitinfo file should be under version control and contain block');

        $this->assertDirectoryExists($GLOBALS['cvslock_prefix'] . '/TestProj', 'CVS lock dir should be created');

        $writers_file = file($GLOBALS['cvs_prefix'] . '/TestProj/CVSROOT/writers');
        $this->assertContains("user1\n", $writers_file, 'writers file should contain user1');
        $this->assertContains("user2\n", $writers_file, 'writers file should contain user2');
        $this->assertContains("user3\n", $writers_file, 'writers file should contain user3');
    }

    public function testCVSRootListUpdate(): void
    {
        $backend = \Mockery::mock(\BackendCVS::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service_dao = \Mockery::spy(\ServiceDao::class);
        $service_dao->shouldReceive('searchActiveUnixGroupByUsedService')->andReturns(array(array('unix_group_name' => 'TestProj'),array('unix_group_name' => 'gpig')));
        $backend->shouldReceive('_getServiceDao')->andReturns($service_dao);

        $backend->setCVSRootListNeedUpdate();
        $this->assertTrue($backend->getCVSRootListNeedUpdate(), "Need to update the repo list");

        $this->assertTrue($backend->CVSRootListUpdate());

        // Now test CVSRootListUpdate
        $this->assertTrue(is_file($GLOBALS['cvs_root_allow_file']), "cvs_root_allow file should be created");
        $cvs_config_array1 = file($GLOBALS['cvs_root_allow_file']);

        $this->assertContains("/cvsroot/gpig\n", $cvs_config_array1, "Project gpig should be listed in root file");
        $this->assertContains("/cvsroot/TestProj\n", $cvs_config_array1, "Project TestProj should be listed in root file");

        $service_dao->shouldReceive('searchActiveUnixGroupByUsedService')->andReturns(array(array('unix_group_name' => 'TestProj'),array('unix_group_name' => 'gpig')));
        $backend->setCVSRootListNeedUpdate();
        $this->assertTrue($backend->getCVSRootListNeedUpdate(), "Need to update the repo list");
        $this->assertTrue($backend->CVSRootListUpdate());
        $this->assertTrue(is_file($GLOBALS['cvs_root_allow_file'] . ".new"), "cvs_root_allow.new file should be created");
        $this->assertFalse(is_file($GLOBALS['cvs_root_allow_file'] . ".old"), "cvs_root_allow.old file should not be created (same files)");
        $cvs_config_array2 = file($GLOBALS['cvs_root_allow_file'] . ".new");
        $this->assertContains("/cvsroot/gpig\n", $cvs_config_array2, "Project gpig should be listed in root.new file");
        $this->assertContains("/cvsroot/TestProj\n", $cvs_config_array2, "Project TestProj should be listed in root.new file");

        // A project was added
        $service_dao2 = \Mockery::spy(\ServiceDao::class);
        $service_dao2->shouldReceive('searchActiveUnixGroupByUsedService')->andReturns(array(array('unix_group_name' => 'TestProj'),array('unix_group_name' => 'gpig'),array('unix_group_name' => 'newProj')));
        $backend2 = \Mockery::mock(\BackendCVS::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $backend2->shouldReceive('_getServiceDao')->andReturns($service_dao2);
        $backend2->setCVSRootListNeedUpdate();
        $this->assertTrue($backend2->getCVSRootListNeedUpdate(), "Need to update the repo list");
        $this->assertTrue($backend2->CVSRootListUpdate());
        $this->assertFalse(is_file($GLOBALS['cvs_root_allow_file'] . ".new"), "cvs_root_allow.new file should not be created (moved because different files)");
        $this->assertTrue(is_file($GLOBALS['cvs_root_allow_file'] . ".old"), "cvs_root_allow.old file should be created (different files)");
        // Again
        $backend2->setCVSRootListNeedUpdate();
        $this->assertTrue($backend2->getCVSRootListNeedUpdate(), "Need to update the repo list");
        $this->assertTrue($backend2->CVSRootListUpdate());
        $this->assertTrue(is_file($GLOBALS['cvs_root_allow_file'] . ".new"), "cvs_root_allow.new file should be created (same files)");
        $this->assertTrue(is_file($GLOBALS['cvs_root_allow_file'] . ".old"), "cvs_root_allow.old file should be there");
    }

    public function testSetCVSPrivacyPrivate(): void
    {
        $backend = \Mockery::mock(\BackendCVS::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $backend->shouldReceive('chmod')->with($GLOBALS['cvs_prefix'] . '/' . 'toto', 02770)->once()->andReturns(true);

        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('getUnixName')->andReturns('toto');

        $this->assertTrue($backend->setCVSPrivacy($project, true));
    }

    public function testsetCVSPrivacyPublic(): void
    {
        $backend = \Mockery::mock(\BackendCVS::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $backend->shouldReceive('chmod')->with($GLOBALS['cvs_prefix'] . '/' . 'toto', 02775)->once()->andReturns(true);

        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('getUnixName')->andReturns('toto');

        $this->assertTrue($backend->setCVSPrivacy($project, false));
    }

    public function testSetCVSPrivacyNoRepository(): void
    {
        $path_that_doesnt_exist = '/' . bin2hex(random_bytes(32));

        $backend = \Mockery::mock(\BackendCVS::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $backend->shouldReceive('chmod')->never();

        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('getUnixName')->andReturns($path_that_doesnt_exist);

        $this->assertFalse($backend->setCVSPrivacy($project, true));
        $this->assertFalse($backend->setCVSPrivacy($project, false));
    }

    public function testRenameCVSRepository(): void
    {
        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('getUnixName')->with(false)->andReturns('TestProj');
        $project->shouldReceive('getUnixName')->with(true)->andReturns('testproj');
        $project->shouldReceive('isCVSTracked')->andReturns(false);

        $project->shouldReceive('getMembersUserNames')->andReturns(array());

        $pm = \Mockery::spy(\ProjectManager::class);
        $pm->shouldReceive('getProject')->with(142)->andReturns($project);

        $backend = \Mockery::mock(\BackendCVS::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $backend->shouldReceive('getProjectManager')->andReturns($pm);
        $backend->shouldReceive('system')->with('chown -R :TestProj ' . $GLOBALS['cvs_prefix'] . '/TestProj')->once();

        $backend->createProjectCVS(142);

        $this->assertTrue($backend->renameCVSRepository($project, "foobar"));

        // Test repo location
        $repoPath = $GLOBALS['cvs_prefix'] . "/foobar";
        $this->assertDirectoryExists($repoPath, "CVS dir should be renamed");

        // Test Lock dir
        $this->assertDirectoryExists($GLOBALS['cvslock_prefix'] . "/foobar", 'CVS lock dir should be renamed');
        $file = file_get_contents($repoPath . "/CVSROOT/config");
        $this->assertSame(preg_match('#^LockDir=' . $GLOBALS['cvslock_prefix'] . "/foobar$#m", $file), 1, "CVS lock dir should be renamed");
        $this->assertStringNotContainsString('TestProj', $file, 'There should no longer be any occurence of old project name in CVSROOT/config');

        // Test loginfo file
        $file = file_get_contents($repoPath . "/CVSROOT/commitinfo");
        $this->assertStringNotContainsString('TestProj', $file, 'There should no longer be any occurrence of old project name in CVSROOT/commitinfo');
    }

    public function testRenameCVSRepositoryTracked(): void
    {
        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('getUnixName')->with(false)->andReturns('TestProj');
        $project->shouldReceive('getUnixName')->with(true)->andReturns('testproj');
        $project->shouldReceive('isCVSTracked')->andReturns(true);

        $project->shouldReceive('getMembersUserNames')->andReturns(array());

        $pm = \Mockery::spy(\ProjectManager::class);
        $pm->shouldReceive('getProject')->with(142)->andReturns($project);

        $backend = \Mockery::mock(\BackendCVS::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $backend->shouldReceive('getProjectManager')->andReturns($pm);
        $backend->shouldReceive('chown');
        $backend->shouldReceive('chgrp');
        $backend->shouldReceive('system')->with('chown -R :TestProj ' . $GLOBALS['cvs_prefix'] . '/TestProj')->once();

        $backend->createProjectCVS(142);

        $this->assertTrue($backend->renameCVSRepository($project, "foobar"));

        // Test repo location
        $repoPath = $GLOBALS['cvs_prefix'] . "/foobar";
        $this->assertDirectoryExists($repoPath, "CVS dir should be renamed");

        // Test Lock dir
        $this->assertDirectoryExists($GLOBALS['cvslock_prefix'] . "/foobar", "CVS lock dir should be renamed");
        $file = file_get_contents($repoPath . "/CVSROOT/config");
        $this->assertSame(preg_match('#^LockDir=' . $GLOBALS['cvslock_prefix'] . "/foobar$#m", $file), 1, "CVS lock dir should be renamed");
        $this->assertStringNotContainsString('TestProj', $file, 'There should no longer be any occurence of old project name in CVSROOT/config');

        // Test loginfo file
        $file = file_get_contents($repoPath . "/CVSROOT/loginfo");
        $this->assertSame(preg_match('#^ALL \(' . ForgeConfig::get('codendi_bin_prefix') . "/log_accum -T foobar -C foobar -s %{sVv}\)>/dev/null 2>&1$#m", $file), 1, "CVS loginfo log_accum should use new project name");
        $this->assertStringNotContainsString('TestProj', $file, 'There should no longer be any occurrence of old project name in CVSROOT/loginfo');

        // Test loginfo file
        $file = file_get_contents($repoPath . "/CVSROOT/commitinfo");
        $this->assertSame(preg_match('#^ALL ' . ForgeConfig::get('codendi_bin_prefix') . "/commit_prep -T foobar -r$#m", $file), 1, "CVS commitinfo should use new project name");
        $this->assertStringNotContainsString('TestProj', $file, 'There should no longer be any occurrence of old project name in CVSROOT/commitinfo');
    }

    public function testRenameCVSRepositoryWithCVSNT(): void
    {
        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('getUnixName')->with(false)->andReturns('TestProj');
        $project->shouldReceive('getUnixName')->with(true)->andReturns('testproj');

        // Simulate loginfo generated for CVSNT
        $cvsdir = $GLOBALS['cvs_prefix'] . '/foobar';
        mkdir($cvsdir);
        mkdir($cvsdir . '/CVSROOT');
        $file = file_get_contents(dirname(__FILE__) . '/_fixtures/cvsroot/loginfo.cvsnt');
        $file = str_replace('%unix_group_name%', 'TestProj', $file);
        $file = str_replace('%cvs_dir%', $GLOBALS['cvs_prefix'] . '/TestProj', $file);
        $file = str_replace('%codendi_bin_prefix%', ForgeConfig::get('codendi_bin_prefix'), $file);
        file_put_contents($cvsdir . '/CVSROOT/loginfo', $file);

        $backend = \Mockery::mock(\BackendCVS::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $backend->shouldReceive('useCVSNT')->andReturns(true);
        $loginfo_path = $GLOBALS['cvs_prefix'] . '/foobar/CVSROOT/loginfo';
        $backend->shouldReceive('system')->with('co -q -l ' . $loginfo_path, 0)->once();
        $backend->shouldReceive('system')->with(
            sprintf('/usr/bin/rcs -q -l %s; ci -q -m"Codendi modification" %s; co -q %s', $loginfo_path, $loginfo_path, $loginfo_path),
            0
        )->once();

        $backend->renameLogInfoFile($project, 'foobar');

        // Test loginfo file
        $file = file_get_contents($cvsdir . "/CVSROOT/loginfo");
        $this->assertSame(preg_match('#^DEFAULT chgrp -f -R\s*foobar ' . $cvsdir . '$#m', $file), 1, "CVS loginfo should use new project name");
        $this->assertSame(preg_match('#^ALL ' . ForgeConfig::get('codendi_bin_prefix') . '/log_accum -T foobar -C foobar -s %{sVv}$#m', $file), 1, "CVS loginfo should use new project name");
        $this->assertStringNotContainsString('TestProj', $file, 'There should no longer be any occurrence of old project name in CVSROOT/loginfo');
    }

    public function testIsNameAvailable(): void
    {
        $cvsdir = $GLOBALS['cvs_prefix'] . '/foobar';
        mkdir($cvsdir);

        $backend = \Mockery::mock(\BackendCVS::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->assertFalse($backend->isNameAvailable("foobar"));
    }

    public function testUpdateCVSWritersForGivenMember(): void
    {
        $backend = \Mockery::mock(\BackendCVS::class)->makePartial()->shouldAllowMockingProtectedMethods();

        // The user
        $user = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('getId')->andReturns(array(142));

        $project1 = \Mockery::spy(\Project::class);
        $project1->shouldReceive('getId')->andReturns(102);
        $project1->shouldReceive('usesCVS')->andReturns(true);

        $project2 = \Mockery::spy(\Project::class);
        $project2->shouldReceive('getId')->andReturns(101);
        $project2->shouldReceive('usesCVS')->andReturns(true);

        $projects =  array(102, 101);
        $user->shouldReceive('getProjects')->andReturns($projects);

        $pm = \Mockery::spy(\ProjectManager::class);
        $backend->shouldReceive('getProjectManager')->andReturns($pm);

        $pm->shouldReceive('getProject')->with(102)->andReturns($project1);
        $pm->shouldReceive('getProject')->with(101)->andReturns($project2);

        $backend->shouldReceive('repositoryExists')->with($project1)->once()->andReturn(true);
        $backend->shouldReceive('repositoryExists')->with($project2)->once()->andReturn(true);

        $backend->shouldReceive('updateCVSwriters')->with(102)->once()->andReturn(true);
        $backend->shouldReceive('updateCVSwriters')->with(101)->once()->andReturn(true);

        $this->assertTrue($backend->updateCVSWritersForGivenMember($user));
    }

    public function testUpdateCVSWatchModeNotifyMissing(): void
    {
        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('getUnixName')->with(false)->andReturns('TestProj');
        $pm = \Mockery::spy(\ProjectManager::class);
        $pm->shouldReceive('getProject')->with(1)->andReturns($project);
        $backend = \Mockery::mock(\BackendCVS::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $backend->shouldReceive('getProjectManager')->andReturns($pm);
        $backend->shouldReceive('getCVSWatchMode')->andReturns(false);

        $backend->shouldReceive('log')->with('No such file: ' . $GLOBALS['cvs_prefix'] . '/TestProj/CVSROOT/notify', 'error')->once();

        $this->assertFalse($backend->updateCVSWatchMode(1));
    }

    public function testUpdateCVSWatchModeNotifyExist(): void
    {
        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('getUnixName')->with(false)->andReturns('TestProj');
        $pm = \Mockery::spy(\ProjectManager::class);
        $pm->shouldReceive('getProject')->with(1)->andReturns($project);
        $project->shouldReceive('getMembersUserNames')->andReturns(array());
        $backend = \Mockery::mock(\BackendCVS::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $backend->shouldReceive('getProjectManager')->andReturns($pm);
        $backend->shouldReceive('getCVSWatchMode')->andReturns(false);

        // Simulate notify generated using command
        $cvsdir = $GLOBALS['cvs_prefix'] . '/TestProj';
        mkdir($cvsdir);
        system($GLOBALS['cvs_cmd'] . " -d $cvsdir init");
        $this->assertTrue($backend->updateCVSWatchMode(1));
    }

    public function testCheckCVSModeFilesMissing(): void
    {
        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('getUnixName')->with(false)->andReturns('TestProj');
        $project->shouldReceive('isPublic')->andReturns(true);
        $project->shouldReceive('isCVSPrivate')->andReturns(false);

        // Simulate loginfo generated for CVSNT
        $cvsdir = $GLOBALS['cvs_prefix'] . '/TestProj';
        mkdir($cvsdir);
        mkdir($cvsdir . '/CVSROOT');
        $file = file_get_contents(__DIR__ . '/_fixtures/cvsroot/loginfo.cvsnt');
        $file = str_replace('%unix_group_name%', 'TestProj', $file);
        $file = str_replace('%cvs_dir%', $GLOBALS['cvs_prefix'] . '/TestProj', $file);
        $file = str_replace('%codendi_bin_prefix%', ForgeConfig::get('codendi_bin_prefix'), $file);
        file_put_contents($cvsdir . '/CVSROOT/loginfo', $file);

        $stat = stat($cvsdir . '/CVSROOT/loginfo');
        $project->shouldReceive('getUnixGID')->andReturns($stat['gid']);

        $this->assertFileExists($cvsdir . '/CVSROOT/loginfo');
        $this->assertFileDoesNotExist($cvsdir . '/CVSROOT/commitinfo');
        $this->assertFileDoesNotExist($cvsdir . '/CVSROOT/config');

        $backend = \Mockery::mock(\BackendCVS::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $backend->shouldReceive('getHTTPUserUID')->andReturns($stat['uid']);

        $backend->shouldReceive('log')->times(2);
        $backend->shouldReceive('log')->with('File not found in cvsroot: ' . $cvsdir . '/CVSROOT/commitinfo', \Psr\Log\LogLevel::WARNING)->ordered();
        $backend->shouldReceive('log')->with('File not found in cvsroot: ' . $cvsdir . '/CVSROOT/config', \Psr\Log\LogLevel::WARNING)->ordered();

        $this->assertTrue($backend->checkCVSMode($project));
    }

    public function testCheckCVSModeNeedOwnerUpdate(): void
    {
        $cvsdir = $GLOBALS['cvs_prefix'] . '/TestProj';
        mkdir($cvsdir . '/CVSROOT', 0700, true);
        chmod($cvsdir . '/CVSROOT', 04700);

        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('getUnixName')->with(false)->andReturns('TestProj');
        $project->shouldReceive('isPublic')->andReturns(true);
        $project->shouldReceive('isCVSPrivate')->andReturns(false);
        $project->shouldReceive('getMembersUserNames')->andReturns(array());

        $backend = $this->GivenACVSRepositoryWithWrongOwnership($project, $cvsdir);
        $backend->shouldReceive('log')->with('Restoring ownership on CVS dir: ' . $cvsdir, 'info')->once();
        $backend->shouldReceive('system')->with('chown -R :TestProj ' . $GLOBALS['cvs_prefix'] . '/TestProj')->once();

        $this->assertTrue($backend->checkCVSMode($project));
    }

    /**
     * @return BackendCVS
     */
    private function givenACVSRepositoryWithWrongOwnership($project, $cvsdir)
    {
        $pm = \Mockery::spy(\ProjectManager::class);
        $pm->shouldReceive('getProject')->with(1)->andReturns($project);

        $backend = \Mockery::mock(\BackendCVS::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $backend->shouldReceive('getProjectManager')->andReturns($pm);

        touch($cvsdir . '/CVSROOT/loginfo');
        touch($cvsdir . '/CVSROOT/commitinfo');
        touch($cvsdir . '/CVSROOT/config');

        //fake the fact that the repo has wrong ownership
        $stat = stat($cvsdir . '/CVSROOT/loginfo');
        $project->shouldReceive('getUnixGID')->andReturns($stat['gid'] + 1);
        $backend->shouldReceive('getHTTPUserUID')->andReturns($stat['uid']);

        return $backend;
    }
}
