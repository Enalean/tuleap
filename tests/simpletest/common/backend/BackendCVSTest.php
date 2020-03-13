<?php
/*
 * Copyright (c) The Codendi Team, Xerox, 2009. All Rights Reserved.
 * Copyright (c) Enalean 2011 - 2018. All rights reserved
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


Mock::generate('UserManager');
Mock::generate('PFUser');
Mock::generate('ProjectManager');
Mock::generate('Project');
Mock::generate('ServiceDao');


Mock::generatePartial('BackendCVS', 'BackendCVSTestVersion', array('getUserManager',
                                                                       'getProjectManager',
                                                                       'chown',
                                                                       'chgrp',
                                                                       'chmod',
                                                                       '_getServiceDao',
                                                                       'getCVSWatchMode',
                                                                       'getHTTPUserUID',
                                                                       'log',
                                                           ));

Mock::generatePartial('BackendCVS', 'BackendCVS4RenameCVSNT', array('useCVSNT', '_RcsCheckout', '_RcsCommit','updateCVSwriters',
'repositoryExists', 'getProjectManager'));

class BackendCVSTest extends TuleapTestCase
{

    public function __construct($name = 'BackendCVS test')
    {
        parent::__construct($name);
    }

    public function setUp()
    {
        parent::setUp();
        mkdir($this->getTmpDir() . '/var/lock/cvs', 0770, true);
        mkdir($this->getTmpDir() . '/cvsroot');
        mkdir($this->getTmpDir() . '/tmp');
        copy(__DIR__ . '/_fixtures/cvsroot/loginfo.cvsnt', $this->getTmpDir() . '/cvsroot/loginfo.cvsnt');
        $GLOBALS['cvs_prefix']                = $this->getTmpDir() . '/cvsroot';
        $GLOBALS['cvslock_prefix']            = $this->getTmpDir() . '/var/lock/cvs';
        $GLOBALS['tmp_dir']                   = $this->getTmpDir() . '/tmp';
        $GLOBALS['cvs_cmd']                   = "/usr/bin/cvs";
        $GLOBALS['cvs_root_allow_file']       = $this->getTmpDir() . '/cvs_root_allow';
        ForgeConfig::store();
        ForgeConfig::set('sys_project_backup_path', $this->getTmpDir() . '/tmp');
        mkdir($GLOBALS['cvs_prefix'] . '/' . 'toto');
    }


    public function tearDown()
    {
        Backend::clearInstances();
        unset($GLOBALS['cvs_prefix']);
        unset($GLOBALS['cvslock_prefix']);
        unset($GLOBALS['tmp_dir']);
        unset($GLOBALS['cvs_cmd']);
        unset($GLOBALS['cvs_root_allow_file']);
        ForgeConfig::restore();
        parent::tearDown();
    }

    public function testConstructor()
    {
        $backend = BackendCVS::instance();
    }


    public function testArchiveProjectCVS()
    {
        $project = new MockProject($this);
        $project->setReturnValue('getUnixName', 'TestProj', array(false));
        $project->setReturnValue('getUnixName', 'testproj', array(true));

        $pm = new MockProjectManager();
        $pm->setReturnReference('getProject', $project, array(142));

        $backend = new BackendCVSTestVersion($this);
        $backend->setReturnValue('getProjectManager', $pm);

        $projdir = $GLOBALS['cvs_prefix'] . "/TestProj";

        // Setup test data
        mkdir($projdir);
        mkdir($projdir . "/CVSROOT");

        //$this->assertTrue(is_dir($projdir),"Project dir should be created");

        $this->assertEqual($backend->archiveProjectCVS(142), true);
        $this->assertFalse(is_dir($projdir), "Project CVS repository should be deleted");
        $this->assertTrue(is_file(ForgeConfig::get('sys_project_backup_path') . "/TestProj-cvs.tgz"), "CVS Archive should be created");

        // Check that a wrong project id does not raise an error
        $this->assertEqual($backend->archiveProjectCVS(99999), false);

        // Cleanup
        unlink(ForgeConfig::get('sys_project_backup_path') . "/TestProj-cvs.tgz");
    }

    public function testCreateProjectCVS()
    {
        $project = new MockProject($this);
        $project->setReturnValue('getUnixName', 'TestProj', array(false));
        $project->setReturnValue('getUnixName', 'testproj', array(true));
        $project->setReturnValue('isCVSTracked', true);
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

        $project->setReturnValue('getMembersUserNames', $proj_members);

        $pm = new MockProjectManager();
        $pm->setReturnReference('getProject', $project, array(142));

        $backend = new BackendCVSTestVersion($this);
        $backend->setReturnReference('getProjectManager', $pm);

        $this->assertEqual($backend->createProjectCVS(142), true);
        $this->assertTrue(is_dir($GLOBALS['cvs_prefix'] . "/TestProj"), "CVS dir should be created");
        $this->assertTrue(is_dir($GLOBALS['cvs_prefix'] . "/TestProj/CVSROOT"), "CVSROOT dir should be created");
        $this->assertTrue(is_file($GLOBALS['cvs_prefix'] . "/TestProj/CVSROOT/loginfo"), "loginfo file should be created");

        $commitinfo_file = file($GLOBALS['cvs_prefix'] . "/TestProj/CVSROOT/commitinfo");
        $this->assertTrue(in_array($backend->block_marker_start, $commitinfo_file), "commitinfo file should contain block");

        $commitinfov_file = file($GLOBALS['cvs_prefix'] . "/TestProj/CVSROOT/commitinfo,v");
        $this->assertTrue(in_array($backend->block_marker_start, $commitinfov_file), "commitinfo file should be under version control and contain block");

        $this->assertTrue(is_dir($GLOBALS['cvslock_prefix'] . "/TestProj"), "CVS lock dir should be created");

        $writers_file = file($GLOBALS['cvs_prefix'] . "/TestProj/CVSROOT/writers");
        $this->assertTrue(in_array("user1\n", $writers_file), "writers file should contain user1");
        $this->assertTrue(in_array("user2\n", $writers_file), "writers file should contain user2");
        $this->assertTrue(in_array("user3\n", $writers_file), "writers file should contain user3");

        // Cleanup
        $backend->recurseDeleteInDir($GLOBALS['cvs_prefix'] . "/TestProj");
        rmdir($GLOBALS['cvs_prefix'] . "/TestProj");
        rmdir($GLOBALS['cvslock_prefix'] . "/TestProj");
    }

    public function testCVSRootListUpdate()
    {
        $backend = new BackendCVSTestVersion($this);
        $service_dao = new MockServiceDao($this);
        $service_dao->setReturnValue('searchActiveUnixGroupByUsedService', array(array('unix_group_name' => 'TestProj'),array('unix_group_name' => 'gpig')));
        $backend->setReturnReference('_getServiceDao', $service_dao);

        $backend->setCVSRootListNeedUpdate();
        $this->assertTrue($backend->getCVSRootListNeedUpdate(), "Need to update the repo list");

        $this->assertEqual($backend->CVSRootListUpdate(), true);

        // Now test CVSRootListUpdate
        $this->assertTrue(is_file($GLOBALS['cvs_root_allow_file']), "cvs_root_allow file should be created");
        $cvs_config_array1 = file($GLOBALS['cvs_root_allow_file']);

        $this->assertTrue(in_array("/cvsroot/gpig\n", $cvs_config_array1), "Project gpig should be listed in root file");
        $this->assertTrue(in_array("/cvsroot/TestProj\n", $cvs_config_array1), "Project TestProj should be listed in root file");

        $service_dao->setReturnValue('searchActiveUnixGroupByUsedService', array(array('unix_group_name' => 'TestProj'),array('unix_group_name' => 'gpig')));
        $backend->setCVSRootListNeedUpdate();
        $this->assertTrue($backend->getCVSRootListNeedUpdate(), "Need to update the repo list");
        $this->assertEqual($backend->CVSRootListUpdate(), true);
        $this->assertTrue(is_file($GLOBALS['cvs_root_allow_file'] . ".new"), "cvs_root_allow.new file should be created");
        $this->assertFalse(is_file($GLOBALS['cvs_root_allow_file'] . ".old"), "cvs_root_allow.old file should not be created (same files)");
        $cvs_config_array2 = file($GLOBALS['cvs_root_allow_file'] . ".new");
        $this->assertTrue(in_array("/cvsroot/gpig\n", $cvs_config_array2), "Project gpig should be listed in root.new file");
        $this->assertTrue(in_array("/cvsroot/TestProj\n", $cvs_config_array2), "Project TestProj should be listed in root.new file");

        // A project was added
        $service_dao2 = new MockServiceDao($this);
        $service_dao2->setReturnValue('searchActiveUnixGroupByUsedService', array(array('unix_group_name' => 'TestProj'),array('unix_group_name' => 'gpig'),array('unix_group_name' => 'newProj')));
        $backend2 = new BackendCVSTestVersion($this);
        $backend2->setReturnReference('_getServiceDao', $service_dao2);
        $backend2->setCVSRootListNeedUpdate();
        $this->assertTrue($backend2->getCVSRootListNeedUpdate(), "Need to update the repo list");
        $this->assertEqual($backend2->CVSRootListUpdate(), true);
        $this->assertFalse(is_file($GLOBALS['cvs_root_allow_file'] . ".new"), "cvs_root_allow.new file should not be created (moved because different files)");
        $this->assertTrue(is_file($GLOBALS['cvs_root_allow_file'] . ".old"), "cvs_root_allow.old file should be created (different files)");
        // Again
        $backend2->setCVSRootListNeedUpdate();
        $this->assertTrue($backend2->getCVSRootListNeedUpdate(), "Need to update the repo list");
        $this->assertEqual($backend2->CVSRootListUpdate(), true);
        $this->assertTrue(is_file($GLOBALS['cvs_root_allow_file'] . ".new"), "cvs_root_allow.new file should be created (same files)");
        $this->assertTrue(is_file($GLOBALS['cvs_root_allow_file'] . ".old"), "cvs_root_allow.old file should be there");

        // Cleanup
        unlink($GLOBALS['cvs_root_allow_file']);
        unlink($GLOBALS['cvs_root_allow_file'] . ".old");
        unlink($GLOBALS['cvs_root_allow_file'] . ".new");
    }

    public function testSetCVSPrivacy_private()
    {
        $backend = new BackendCVSTestVersion($this);
        $backend->setReturnValue('chmod', true);
        $backend->expectOnce('chmod', array($GLOBALS['cvs_prefix'] . '/' . 'toto', 02770));

        $project = new MockProject($this);
        $project->setReturnValue('getUnixName', 'toto');

        $this->assertTrue($backend->setCVSPrivacy($project, true));
    }

    public function testsetCVSPrivacy_public()
    {
        $backend = new BackendCVSTestVersion($this);
        $backend->setReturnValue('chmod', true);
        $backend->expectOnce('chmod', array($GLOBALS['cvs_prefix'] . '/' . 'toto', 02775));

        $project = new MockProject($this);
        $project->setReturnValue('getUnixName', 'toto');

        $this->assertTrue($backend->setCVSPrivacy($project, false));
    }

    public function testSetCVSPrivacy_no_repository()
    {
        $path_that_doesnt_exist = md5(uniqid(rand(), true));

        $backend = new BackendCVSTestVersion($this);
        $backend->expectNever('chmod');

        $project = new MockProject($this);
        $project->setReturnValue('getUnixName', $path_that_doesnt_exist);

        $this->assertFalse($backend->setCVSPrivacy($project, true));
        $this->assertFalse($backend->setCVSPrivacy($project, false));
    }

    public function testRenameCVSRepository()
    {
        $project = new MockProject($this);
        $project->setReturnValue('getUnixName', 'TestProj', array(false));
        $project->setReturnValue('getUnixName', 'testproj', array(true));
        $project->setReturnValue('isCVSTracked', false);

        $project->setReturnValue('getMembersUserNames', array());

        $pm = new MockProjectManager();
        $pm->setReturnReference('getProject', $project, array(142));

        $backend = new BackendCVSTestVersion($this);
        $backend->setReturnValue('getProjectManager', $pm);

        $backend->createProjectCVS(142);

        $this->assertEqual($backend->renameCVSRepository($project, "foobar"), true);

        // Test repo location
        $repoPath = $GLOBALS['cvs_prefix'] . "/foobar";
        $this->assertTrue(is_dir($repoPath), "CVS dir should be renamed");

        // Test Lock dir
        $this->assertTrue(is_dir($GLOBALS['cvslock_prefix'] . "/foobar"), "CVS lock dir should be renamed");
        $file = file_get_contents($repoPath . "/CVSROOT/config");
        $this->assertTrue(preg_match('#^LockDir=' . $GLOBALS['cvslock_prefix'] . "/foobar$#m", $file), "CVS lock dir should be renamed");
        $this->assertFalse(preg_match("/TestProj/", $file), "There should no longer be any occurence of old project name in CVSROOT/config");

        // Test loginfo file
        $file = file_get_contents($repoPath . "/CVSROOT/commitinfo");
        $this->assertFalse(preg_match("/TestProj/", $file), "There should no longer be any occurence of old project name in CVSROOT/commitinfo");

        // Cleanup
        $backend->recurseDeleteInDir($GLOBALS['cvs_prefix'] . "/foobar");
        rmdir($GLOBALS['cvs_prefix'] . "/foobar");
        rmdir($GLOBALS['cvslock_prefix'] . "/foobar");
    }

    public function testRenameCVSRepositoryTracked()
    {
        $project = new MockProject($this);
        $project->setReturnValue('getUnixName', 'TestProj', array(false));
        $project->setReturnValue('getUnixName', 'testproj', array(true));
        $project->setReturnValue('isCVSTracked', true);

        $project->setReturnValue('getMembersUserNames', array());

        $pm = new MockProjectManager();
        $pm->setReturnReference('getProject', $project, array(142));

        $backend = new BackendCVSTestVersion($this);
        $backend->setReturnValue('getProjectManager', $pm);

        $backend->createProjectCVS(142);

        $this->assertEqual($backend->renameCVSRepository($project, "foobar"), true);

        // Test repo location
        $repoPath = $GLOBALS['cvs_prefix'] . "/foobar";
        $this->assertTrue(is_dir($repoPath), "CVS dir should be renamed");

        // Test Lock dir
        $this->assertTrue(is_dir($GLOBALS['cvslock_prefix'] . "/foobar"), "CVS lock dir should be renamed");
        $file = file_get_contents($repoPath . "/CVSROOT/config");
        $this->assertTrue(preg_match('#^LockDir=' . $GLOBALS['cvslock_prefix'] . "/foobar$#m", $file), "CVS lock dir should be renamed");
        $this->assertFalse(preg_match("/TestProj/", $file), "There should no longer be any occurence of old project name in CVSROOT/config");

        // Test loginfo file
        $file = file_get_contents($repoPath . "/CVSROOT/loginfo");
        $this->assertTrue(preg_match('#^ALL \(' . $GLOBALS['codendi_bin_prefix'] . "/log_accum -T foobar -C foobar -s %{sVv}\)>/dev/null 2>&1$#m", $file), "CVS loginfo log_accum should use new project name");
        $this->assertFalse(preg_match("/TestProj/", $file), "There should no longer be any occurence of old project name in CVSROOT/loginfo");

        // Test loginfo file
        $file = file_get_contents($repoPath . "/CVSROOT/commitinfo");
        $this->assertTrue(preg_match('#^ALL ' . $GLOBALS['codendi_bin_prefix'] . "/commit_prep -T foobar -r$#m", $file), "CVS commitinfo should use new project name");
        $this->assertFalse(preg_match("/TestProj/", $file), "There should no longer be any occurence of old project name in CVSROOT/commitinfo");

        // Cleanup
        $backend->recurseDeleteInDir($GLOBALS['cvs_prefix'] . "/foobar");
        rmdir($GLOBALS['cvs_prefix'] . "/foobar");
        rmdir($GLOBALS['cvslock_prefix'] . "/foobar");
    }

    public function testRenameCVSRepositoryWithCVSNT()
    {
        $project = new MockProject($this);
        $project->setReturnValue('getUnixName', 'TestProj', array(false));
        $project->setReturnValue('getUnixName', 'testproj', array(true));

        // Simulate loginfo generated for CVSNT
        $cvsdir = $GLOBALS['cvs_prefix'] . '/foobar';
        mkdir($cvsdir);
        mkdir($cvsdir . '/CVSROOT');
        $file = file_get_contents(dirname(__FILE__) . '/_fixtures/cvsroot/loginfo.cvsnt');
        $file = str_replace('%unix_group_name%', 'TestProj', $file);
        $file = str_replace('%cvs_dir%', $GLOBALS['cvs_prefix'] . '/TestProj', $file);
        $file = str_replace('%codendi_bin_prefix%', $GLOBALS['codendi_bin_prefix'], $file);
        file_put_contents($cvsdir . '/CVSROOT/loginfo', $file);

        $backend = new BackendCVS4RenameCVSNT($this);
        $backend->setReturnValue('useCVSNT', true);
        $backend->renameLogInfoFile($project, 'foobar');

        // Test loginfo file
        $file = file_get_contents($cvsdir . "/CVSROOT/loginfo");
        $this->assertTrue(preg_match('#^DEFAULT chgrp -f -R\s*foobar ' . $cvsdir . '$#m', $file), "CVS loginfo should use new project name");
        $this->assertTrue(preg_match('#^ALL ' . $GLOBALS['codendi_bin_prefix'] . '/log_accum -T foobar -C foobar -s %{sVv}$#m', $file), "CVS loginfo should use new project name");
        $this->assertFalse(preg_match("/TestProj/", $file), "There should no longer be any occurence of old project name in CVSROOT/loginfo");

        $backend->recurseDeleteInDir($cvsdir);
        rmdir($cvsdir);
    }

    public function testIsNameAvailable()
    {
        $cvsdir = $GLOBALS['cvs_prefix'] . '/foobar';
        mkdir($cvsdir);

        $backend = new BackendCVSTestVersion($this);
        $this->assertEqual($backend->isNameAvailable("foobar"), false);

        $backend->recurseDeleteInDir($cvsdir);

        rmdir($cvsdir);
    }

    public function testUpdateCVSWritersForGivenMember()
    {
        $backend = new BackendCVS4RenameCVSNT($this);

        // The user
        $user = mock('PFUser');
        $user->setReturnValue('getId', array(142));

        $project1 = new MockProject($this);
        $project1->setReturnValue('getId', 102);
        $project1->setReturnValue('usesCVS', true);

        $project2 = new MockProject($this);
        $project2->setReturnValue('getId', 101);
        $project2->setReturnValue('usesCVS', true);

        $projects =  array(102, 101);
        $user->setReturnValue('getProjects', $projects);

        $backend->setReturnValue('repositoryExists', true);
        $backend->setReturnValue('updateCVSwriters', true);

        $pm = new MockProjectManager();
        $backend->setReturnValue('getProjectManager', $pm);

        $pm->setReturnReference('getProject', $project1, array(102));
        $pm->setReturnReference('getProject', $project2, array(101));

        $this->assertEqual($backend->updateCVSWritersForGivenMember($user), true);

        $backend->expectCallCount('repositoryExists', 2);
        $backend->expectAt(0, 'repositoryExists', array($project1));
        $backend->expectAt(1, 'repositoryExists', array($project2));

        $backend->expectCallCount('updateCVSwriters', 2);
        $backend->expectAt(0, 'updateCVSwriters', array(102));
        $backend->expectAt(1, 'updateCVSwriters', array(101));
    }

    public function testUpdateCVSWatchModeNotifyMissing()
    {
        $project = new MockProject($this);
        $project->setReturnValue('getUnixName', 'TestProj', array(false));
        $pm = new MockProjectManager();
        $pm->setReturnValue('getProject', $project, array(1));
        $backend = new BackendCVSTestVersion($this);
        $backend->setReturnValue('getProjectManager', $pm);
        $backend->setReturnValue('getCVSWatchMode', false);

        $backend->expectOnce('log', array('No such file: ' . $GLOBALS['cvs_prefix'] . '/TestProj/CVSROOT/notify', 'error'));

        $this->assertFalse($backend->updateCVSWatchMode(1));
    }

    public function testUpdateCVSWatchModeNotifyExist()
    {
        $project = new MockProject($this);
        $project->setReturnValue('getUnixName', 'TestProj', array(false));
        $pm = new MockProjectManager();
        $pm->setReturnValue('getProject', $project, array(1));
        $project->setReturnValue('getMembersUserNames', array());
        $backend = new BackendCVSTestVersion($this);
        $backend->setReturnValue('getProjectManager', $pm);
        $backend->setReturnValue('getCVSWatchMode', false);

        // Simulate notify generated using command
        $cvsdir = $GLOBALS['cvs_prefix'] . '/TestProj';
        mkdir($cvsdir);
        system($GLOBALS['cvs_cmd'] . " -d $cvsdir init");
        $this->assertTrue($backend->updateCVSWatchMode(1));

        // Cleanup
        $backend->recurseDeleteInDir($GLOBALS['cvs_prefix'] . "/TestProj");
        rmdir($GLOBALS['cvs_prefix'] . "/TestProj");
    }

    public function testCheckCVSModeFilesMissing()
    {
        $project = new MockProject($this);
        $project->setReturnValue('getUnixName', 'TestProj', array(false));
        $project->setReturnValue('isPublic', true);
        $project->setReturnValue('isCVSPrivate', false);

        // Simulate loginfo generated for CVSNT
        $cvsdir = $GLOBALS['cvs_prefix'] . '/TestProj';
        mkdir($cvsdir);
        mkdir($cvsdir . '/CVSROOT');
        $file = file_get_contents(dirname(__FILE__) . '/_fixtures/cvsroot/loginfo.cvsnt');
        $file = str_replace('%unix_group_name%', 'TestProj', $file);
        $file = str_replace('%cvs_dir%', $GLOBALS['cvs_prefix'] . '/TestProj', $file);
        $file = str_replace('%codendi_bin_prefix%', $GLOBALS['codendi_bin_prefix'], $file);
        file_put_contents($cvsdir . '/CVSROOT/loginfo', $file);

        $stat = stat($cvsdir . '/CVSROOT/loginfo');
        $project->setReturnValue('getUnixGID', $stat['gid']);

        $this->assertTrue(file_exists($cvsdir . '/CVSROOT/loginfo'));
        $this->assertFalse(file_exists($cvsdir . '/CVSROOT/commitinfo'));
        $this->assertFalse(file_exists($cvsdir . '/CVSROOT/config'));

        $backend = new BackendCVSTestVersion($this);
        $backend->setReturnValue('getHTTPUserUID', $stat['uid']);

        $backend->expectCallCount('log', 2);
        $backend->expectAt(0, 'log', array('File not found in cvsroot: ' . $cvsdir . '/CVSROOT/commitinfo', \Psr\Log\LogLevel::WARNING));
        $backend->expectAt(1, 'log', array('File not found in cvsroot: ' . $cvsdir . '/CVSROOT/config', \Psr\Log\LogLevel::WARNING));

        $this->assertTrue($backend->checkCVSMode($project));

        // Cleanup
        $backend->recurseDeleteInDir($GLOBALS['cvs_prefix'] . "/TestProj");
        rmdir($GLOBALS['cvs_prefix'] . "/TestProj");
    }

    public function testCheckCVSModeNeedOwnerUpdate()
    {
        $cvsdir = $GLOBALS['cvs_prefix'] . '/TestProj';
        mkdir($cvsdir . '/CVSROOT', 0700, true);
        chmod($cvsdir . '/CVSROOT', 04700);

        $project = new MockProject($this);
        $project->setReturnValue('getUnixName', 'TestProj', array(false));
        $project->setReturnValue('isPublic', true);
        $project->setReturnValue('isCVSPrivate', false);
        $project->setReturnValue('getMembersUserNames', array());

        $backend = $this->GivenACVSRepositoryWithWrongOwnership($project, $cvsdir);
        $backend->expectOnce('log', array('Restoring ownership on CVS dir: ' . $cvsdir, 'info'));

        $this->assertTrue($backend->checkCVSMode($project));
    }

    /**
     * @return BackendCVS
     */
    public function GivenACVSRepositoryWithWrongOwnership($project, $cvsdir)
    {
        $pm = new MockProjectManager();
        $pm->setReturnReference('getProject', $project, array(1));

        $backend = TestHelper::getPartialMock('BackendCVS', array('getProjectManager', 'system', 'chown', 'chgrp', 'chmod', 'getHTTPUserUID', 'log'));
        $backend->setReturnValue('getProjectManager', $pm);

        touch($cvsdir . '/CVSROOT/loginfo');
        touch($cvsdir . '/CVSROOT/commitinfo');
        touch($cvsdir . '/CVSROOT/config');

        //fake the fact that the repo has wrong ownership
        $stat = stat($cvsdir . '/CVSROOT/loginfo');
        $project->setReturnValue('getUnixGID', $stat['gid'] + 1);
        $backend->setReturnValue('getHTTPUserUID', $stat['uid']);

        return $backend;
    }
}
