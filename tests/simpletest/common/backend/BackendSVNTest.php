<?php
/**
 * Copyright (c) Enalean, 2016-2018. All Rights Reserved.
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

Mock::generate('UserManager');
Mock::generate('PFUser');
Mock::generate('ProjectManager');
Mock::generate('Project');
Mock::generate('UGroupDao');
Mock::generate('ProjectUGroup');
Mock::generate('ServiceDao');
Mock::generate('SVNAccessFile');

Mock::generatePartial('BackendSVN', 'BackendSVNAccessTestVersion', array('updateSVNAccess',
                                                                         'repositoryExists',
                                                                         'getAllProjects',
                                                                         'getProjectManager',
                                                                        ));

class BackendSVNTest extends TuleapTestCase
{

    private $tmp_dir;
    private $cache_parameters;

    function setUp()
    {
        parent::setUp();
        $this->tmp_dir = $this->getTmpDir();

        $GLOBALS['svn_prefix']                = $this->tmp_dir . '/svnroot';
        $GLOBALS['tmp_dir']                   = $this->tmp_dir . '/tmp';
        $GLOBALS['sys_dbname']                = 'db';
        $GLOBALS['sys_name']                  = 'MyForge';
        $GLOBALS['sys_dbauth_user']           = 'dbauth_user';
        $GLOBALS['sys_dbauth_passwd']         = 'dbauth_passwd';
        $GLOBALS['svnadmin_cmd']              = '/usr/bin/svnadmin --config-dir '. dirname(__FILE__) . '/_fixtures/.subversion';

        ForgeConfig::store();
        ForgeConfig::set('sys_project_backup_path', $this->tmp_dir .'/backup');
        ForgeConfig::set('svn_root_file', $this->getTmpDir() . '/codendi_svnroot.conf');
        mkdir($GLOBALS['svn_prefix'] . '/toto/hooks', 0777, true);
        mkdir($GLOBALS['tmp_dir'], 0777, true);
        mkdir(ForgeConfig::get('sys_project_backup_path'), 0777, true);

        $this->project_manager  = mock('ProjectManager');
        $this->cache_parameters = mock('Tuleap\SvnCore\Cache\Parameters');
        ;

        $this->backend = partial_mock(
            'BackendSVN',
            array(
                'getUserManager',
                'getProjectManager',
                'getUGroupDao',
                'getUGroupFromRow',
                'getSvnDao',
                'chown',
                'chgrp',
                'chmod',
                '_getSVNAccessFile',
                'getSVNTokenManager',
                'getSVNAccessGroups',
                'getSVNCacheParameters'
            )
        );
    }


    function tearDown()
    {
        //clear the cache between each tests
        Backend::clearInstances();
        unset($GLOBALS['svn_prefix']);
        unset($GLOBALS['tmp_dir']);
        unset($GLOBALS['sys_dbname']);
        unset($GLOBALS['sys_name']);
        unset($GLOBALS['sys_dbauth_user']);
        unset($GLOBALS['sys_dbauth_passwd']);
        ForgeConfig::restore();
        parent::tearDown();
    }

    function testConstructor()
    {
        $backend = BackendSVN::instance();
    }


    function testArchiveProjectSVN()
    {
        $project = new MockProject($this);
        $project->setReturnValue('getUnixNameMixedCase', 'TestProj');
        $project->setReturnValue('getSVNRootPath', $GLOBALS['svn_prefix'].'/TestProj');

        $pm = new MockProjectManager();
        $pm->setReturnReference('getProject', $project, array(142));

        $this->backend->setReturnValue('getProjectManager', $pm);

        $projdir=$GLOBALS['svn_prefix']."/TestProj";

        // Setup test data
        mkdir($projdir);
        mkdir($projdir."/db");

        $this->assertEqual($this->backend->archiveProjectSVN(142), true);
        $this->assertFalse(is_dir($projdir), "Project SVN repository should be deleted");
        $this->assertTrue(is_file(ForgeConfig::get('sys_project_backup_path')."/TestProj-svn.tgz"), "SVN Archive should be created");

        // Check that a wrong project id does not raise an error
        $this->assertEqual($this->backend->archiveProjectSVN(99999), false);
    }


    function testCreateProjectSVN()
    {
        $user1   = mock('PFUser');
        $user1->setReturnValue('getUserName', 'user1');
        $user2   = mock('PFUser');
        $user2->setReturnValue('getUserName', 'user2');
        $user3   = mock('PFUser');
        $user3->setReturnValue('getUserName', 'user3');
        $user4   = mock('PFUser');
        $user4->setReturnValue('getUserName', 'user4');
        $project = new MockProject($this);
        $project->setReturnValue('getUnixNameMixedCase', 'TestProj');
        $project->setReturnValue('getSVNRootPath', $GLOBALS['svn_prefix'].'/TestProj');
        $project->setReturnValue('isSVNTracked', true);
        $proj_members = array("0" =>
                              array (
                                     "user_name"=> "user1",
                                     "user_id"  => "1"),
                              "1" =>
                              array (
                                     "user_name"=> "user2",
                                     "user_id"  => "2"),
                              "2" =>
                              array (
                                     "user_name"=> "user3",
                                     "user_id"  => "3"));
        $project->setReturnValue('getMembersUserNames', $proj_members);
        $project->setReturnValue('getMembers', array($user1, $user2, $user3));

        $pm = new MockProjectManager();
        $pm->setReturnReference('getProject', $project, array(142));

        $ugroups = array("0" =>
                         array (
                                "name"=> "QA",
                                "ugroup_id"  => "104"),
                         "1" =>
                         array (
                                "name"=> "Customers",
                                "ugroup_id"  => "102"));
        $ugdao = new MockUGroupDao();
        $ugdao->setReturnValue('searchByGroupId', $ugroups);

        $ugroup = new MockProjectUGroup($this);
        $ugroup->setReturnValueAt(0, 'getMembersUserName', array('user1', 'user2', 'user3'));
        $ugroup->setReturnValueAt(0, 'getMembers', array($user1, $user2, $user3));
        $ugroup->setReturnValueAt(1, 'getMembersUserName', array('user1', 'user4'));
        $ugroup->setReturnValueAt(1, 'getMembers', array($user1, $user4));
        $ugroup->setReturnValueAt(2, 'getMembers', array($user1, $user4));
        $ugroup->setReturnValue('getMembers', array($user1, $user4));
        $ugroup->setReturnValueAt(0, 'getName', "QA");
        $ugroup->setReturnValueAt(1, 'getName', "QA");
        $ugroup->setReturnValueAt(2, 'getName', "customers");
        $ugroup->setReturnValueAt(3, 'getName', "customers");

        $this->backend->setReturnValue('getProjectManager', $pm);
        $this->backend->setReturnValue('getUGroupFromRow', $ugroup);
        $this->backend->setReturnValue('getUGroupDao', $ugdao);

        $access_file = new SVNAccessFile();
        $this->backend->setReturnValue('_getSVNAccessFile', $access_file);

        $this->assertEqual($this->backend->createProjectSVN(142), true);
        $this->assertTrue(is_dir($GLOBALS['svn_prefix']."/TestProj"), "SVN dir should be created");
        $this->assertTrue(is_dir($GLOBALS['svn_prefix']."/TestProj/hooks"), "hooks dir should be created");
        $this->assertTrue(is_file($GLOBALS['svn_prefix']."/TestProj/hooks/post-commit"), "post-commit file should be created");
    }

    function testUpdateSVNAccess()
    {
        $user1   = mock('PFUser');
        $user1->setReturnValue('getUserName', 'user1');
        $user2   = mock('PFUser');
        $user2->setReturnValue('getUserName', 'user2');
        $user3   = mock('PFUser');
        $user3->setReturnValue('getUserName', 'user3');
        $user4   = mock('PFUser');
        $user4->setReturnValue('getUserName', 'user4');
        $user5   = mock('PFUser');
        $user5->setReturnValue('getUserName', 'user5');
        $project = new MockProject($this);
        $project->setReturnValue('getUnixNameMixedCase', 'TestProj');
        $project->setReturnValue('getSVNRootPath', $GLOBALS['svn_prefix'].'/TestProj');
        $project->setReturnValue('isSVNTracked', true);
        $proj_members = array("0" =>
                              array (
                                     "user_name"=> "user1",
                                     "user_id"  => "1"),
                              "1" =>
                              array (
                                     "user_name"=> "user2",
                                     "user_id"  => "2"),
                              "2" =>
                              array (
                                     "user_name"=> "user3",
                                     "user_id"  => "3"));
        $project->setReturnValue('getMembersUserNames', $proj_members);
        $project->setReturnValue('getMembers', array($user1, $user2, $user3));

        $pm = new MockProjectManager();
        $pm->setReturnReference('getProject', $project, array(142));

        $ugroups = array("0" =>
                         array (
                                "name"=> "QA",
                                "ugroup_id"  => "104"),
                         "1" =>
                         array (
                                "name"=> "Customers",
                                "ugroup_id"  => "102"));
        $ugdao = new MockUGroupDao();
        $ugdao->setReturnValue('searchByGroupId', $ugroups);

        $ugroup = new MockProjectUGroup($this);
        $ugroup->setReturnValueAt(0, 'getMembersUserName', array('user1', 'user2', 'user3'));
        $ugroup->setReturnValueAt(0, 'getMembers', array($user1, $user2, $user3));
        $ugroup->setReturnValueAt(1, 'getMembersUserName', array('user1', 'user4'));
        $ugroup->setReturnValueAt(1, 'getMembers', array($user1, $user4));
        $ugroup->setReturnValueAt(2, 'getMembersUserName', array('user1', 'user2', 'user3'));
        $ugroup->setReturnValueAt(2, 'getMembers', array($user1, $user2, $user3));
        $ugroup->setReturnValueAt(3, 'getMembersUserName', array('user1', 'user4'));
        $ugroup->setReturnValueAt(3, 'getMembers', array($user1, $user4));
        $ugroup->setReturnValueAt(4, 'getMembersUserName', array('user1', 'user2', 'user3'));
        $ugroup->setReturnValueAt(4, 'getMembers', array($user1, $user2, $user3));
        $ugroup->setReturnValueAt(5, 'getMembersUserName', array('user1', 'user4', 'user5'));
        $ugroup->setReturnValueAt(5, 'getMembers', array($user1, $user4, $user5));
        $ugroup->setReturnValueAt(0, 'getName', "QA");
        $ugroup->setReturnValueAt(1, 'getName', "QA");
        $ugroup->setReturnValueAt(4, 'getName', "QA");
        $ugroup->setReturnValueAt(5, 'getName', "QA");
        $ugroup->setReturnValueAt(8, 'getName', "QA");
        $ugroup->setReturnValueAt(9, 'getName', "QA");
        $ugroup->setReturnValueAt(2, 'getName', "customers");
        $ugroup->setReturnValueAt(3, 'getName', "customers");
        $ugroup->setReturnValueAt(6, 'getName', "customers");
        $ugroup->setReturnValueAt(7, 'getName', "customers");
        $ugroup->setReturnValueAt(10, 'getName', "customers");
        $ugroup->setReturnValueAt(11, 'getName', "customers");

        $this->backend->setReturnValue('getProjectManager', $pm);
        $this->backend->setReturnValue('getUGroupFromRow', $ugroup);
        $this->backend->setReturnValue('getUGroupDao', $ugdao);
        $this->backend->setReturnValue('getSVNAccessGroups', "");

        $access_file = new SVNAccessFile();
        $this->backend->setReturnValue('_getSVNAccessFile', $access_file);

        $this->assertEqual($this->backend->createProjectSVN(142), true);
        $this->assertTrue(is_dir($GLOBALS['svn_prefix']."/TestProj"), "SVN dir should be created");
        $this->assertTrue(is_file($GLOBALS['svn_prefix']."/TestProj/.SVNAccessFile"), "SVN access file should be created");

        // Update without modification
        $this->assertEqual($this->backend->updateSVNAccess(142, $GLOBALS['svn_prefix'].'/TestProj'), true);
        $this->assertTrue(is_file($GLOBALS['svn_prefix']."/TestProj/.SVNAccessFile"), "SVN access file should exist");
        $this->assertTrue(is_file($GLOBALS['svn_prefix']."/TestProj/.SVNAccessFile.new"), "SVN access file (.new) should be created");
        $this->assertFalse(is_file($GLOBALS['svn_prefix']."/TestProj/.SVNAccessFile.old"), "SVN access file (.old) should not be created");
    }

    function testGenerateSVNApacheConf()
    {
        $svn_dao = stub('SVN_DAO')->searchSvnRepositories()->returnsDar(
            array (
                "group_id"        => "101",
                "group_name"      => "Guinea Pig",
                "repository_name" => "gpig",
                "public_path"     => "/svnroot/gpig",
                "system_path"     => "/svnroot/gpig"
            ),
            array (
                "group_id"        => "102",
                "group_name"      => "Guinea Pig is \"back\"",
                "repository_name" => "gpig2",
                "public_path"     => "/svnroot/gpig2",
                "system_path"     => "/svnroot/gpig2"
            ),
            array (
                "group_id"        => "103",
                "group_name"      => "Guinea Pig is 'angry'",
                "repository_name" => "gpig3",
                "public_path"     => "/svnroot/gpig3",
                "system_path"     => "/svnroot/gpig3"
            )
        );
        $this->backend->setReturnReference('getSvnDao', $svn_dao);
        $this->backend->setReturnReference('getProjectManager', $this->project_manager);
        $this->backend->setReturnReference('getSVNCacheParameters', $this->cache_parameters);

        $this->assertEqual($this->backend->generateSVNApacheConf(), true);
        $svnroots=file_get_contents(ForgeConfig::get('svn_root_file'));

        $this->assertFalse($svnroots === false);
        $this->assertPattern("/gpig2/", $svnroots, "Project name not found in SVN root");
        $this->assertPattern("/AuthName \"Subversion Authorization \(Guinea Pig is 'back'\)\"/", $svnroots, "Group name double quotes in realm");
    }

    public function testSetSVNPrivacy_private()
    {
        $this->backend->setReturnValue('chmod', true);
        $this->backend->expectOnce('chmod', array($GLOBALS['svn_prefix'] . '/' . 'toto', 0770));
        $this->backend->setReturnReference('getProjectManager', $this->project_manager);

        $project = new MockProject($this);
        $project->setReturnValue('getUnixNameMixedCase', 'toto');
        $project->setReturnValue('getSVNRootPath', $GLOBALS['svn_prefix'].'/toto');

        $this->assertTrue($this->backend->setSVNPrivacy($project, true));
    }

    public function testsetSVNPrivacy_public()
    {
        $this->backend->setReturnValue('chmod', true);
        $this->backend->expectOnce('chmod', array($GLOBALS['svn_prefix'] . '/' . 'toto', 0775));

        $project = new MockProject($this);
        $project->setReturnValue('getUnixNameMixedCase', 'toto');
        $project->setReturnValue('getSVNRootPath', $GLOBALS['svn_prefix'].'/toto');

        $this->assertTrue($this->backend->setSVNPrivacy($project, false));
    }

    public function testSetSVNPrivacy_no_repository()
    {
        $path_that_doesnt_exist = md5(uniqid(rand(), true));

        $this->backend->expectNever('chmod');

        $project = new MockProject($this);
        $project->setReturnValue('getUnixNameMixedCase', $path_that_doesnt_exist);
        $project->setReturnValue('getSVNRootPath', $GLOBALS['svn_prefix'].'/'.$path_that_doesnt_exist);

        $this->assertFalse($this->backend->setSVNPrivacy($project, true));
        $this->assertFalse($this->backend->setSVNPrivacy($project, false));
    }

    public function testRenameSVNRepository()
    {
        $project = new MockProject($this);
        $project->setReturnValue('getUnixNameMixedCase', 'TestProj');
        $project->setReturnValue('getSVNRootPath', $GLOBALS['svn_prefix'].'/TestProj');
        $project->setReturnValue('isSVNTracked', false);

        $project->setReturnValue('getMembersUserNames', array());

        $pm = new MockProjectManager();
        $pm->setReturnReference('getProject', $project, array(142));

        $ugdao = new MockUGroupDao();
        $ugdao->setReturnValue('searchByGroupId', array());

        $access_file = new SVNAccessFile();
        $this->backend->setReturnValue('_getSVNAccessFile', $access_file);

        $this->backend->setReturnValue('getProjectManager', $pm);
        $this->backend->setReturnValue('getUGroupDao', $ugdao);
        $this->backend->createProjectSVN(142);

        $this->assertEqual($this->backend->renameSVNRepository($project, "foobar"), true);

        $this->assertTrue(is_dir($GLOBALS['svn_prefix']."/foobar"), "SVN dir should be renamed");
    }

    public function testUpdateSVNAccessForGivenMember()
    {

        $backend = new BackendSVNAccessTestVersion($this);

        // The user
        $user = mock('PFUser');
        $user->setReturnValue('getId', array(142));

        $project1 = new MockProject($this);
        $project1->setReturnValue('getId', 102);

        $project2 = new MockProject($this);
        $project2->setReturnValue('getId', 101);

        $projects =  array(102, 101);
        $user->setReturnValue('getAllProjects', $projects);

        $pm = new MockProjectManager();
        $backend->setReturnValue('getProjectManager', $pm);

        $pm->setReturnReference('getProject', $project1, array(102));
        $pm->setReturnReference('getProject', $project2, array(101));

        $backend->setReturnValue('repositoryExists', true);
        $backend->setReturnValue('updateSVNAccess', true);

        $this->assertEqual($backend->updateSVNAccessForGivenMember($user), true);

        $backend->expectCallCount('repositoryExists', 2);
        $backend->expectAt(0, 'repositoryExists', array($project1));
        $backend->expectAt(1, 'repositoryExists', array($project2));

        $backend->expectCallCount('updateSVNAccess', 2);
        $backend->expectAt(0, 'updateSVNAccess', array(102));
        $backend->expectAt(1, 'updateSVNAccess', array(101));
    }
}

class BackendSVN_EnableLogChangeHooks_Test extends TuleapTestCase
{

    private $project;
    private $bin_dir;
    private $fake_revprop;
    private $svn_prefix;

    public function setUp()
    {
        parent::setUp();
        $this->tmp_dir = $this->getTmpDir();
        $this->svn_prefix = $this->tmp_dir. '/svnroot';
        mkdir($this->svn_prefix . '/toto/hooks', 0777, true);
        ForgeConfig::store();
        $this->bin_dir = dirname(__FILE__) . '/_fixtures';
        $this->fake_revprop = $this->bin_dir.'/post-revprop-change.php';
        ForgeConfig::set('codendi_bin_prefix', $this->bin_dir);
        $this->project = stub('Project')->getUnixName()->returns('toto');
        stub($this->project)->getSVNRootPath()->returns($this->svn_prefix.'/toto');
    }

    public function tearDown()
    {
        unset($this->svn_prefix);
        ForgeConfig::restore();
        parent::tearDown();
    }


    public function testItThrowsAnExceptionIfFileForSimlinkAlreadyExists()
    {
        $backend = partial_mock('BackendSVN', array('log', 'chgrp', 'chown'));
        $path    = $this->svn_prefix . '/toto/hooks';
        touch($path.'/post-revprop-change');

        stub($this->project)->isSVNTracked()->returns(true);
        expect($backend)->log()->once();

        $this->expectException('BackendSVNFileForSimlinkAlreadyExistsException');
        $backend->updateHooks(
            $this->project,
            $this->svn_prefix.'/toto',
            true,
            ForgeConfig::get('codendi_bin_prefix'),
            'commit-email.pl',
            "",
            "codendi_svn_pre_commit.php"
        );
    }

    public function testDoesntThrowAnExceptionIfTheHookIsALinkToOurImplementation()
    {
        $backend = partial_mock('BackendSVN', array('log', 'chgrp', 'chown'));
        $path    = $this->svn_prefix . '/toto/hooks';

        // Create link to fake post-revprop-change
        symlink($this->fake_revprop, $path.'/post-revprop-change');

        stub($this->project)->isSVNTracked()->returns(true);
        expect($backend)->log()->never();

        $backend->updateHooks(
            $this->project,
            $this->svn_prefix.'/toto',
            true,
            ForgeConfig::get('codendi_bin_prefix'),
            'commit-email.pl',
            "",
            "codendi_svn_pre_commit.php"
        );
    }
}
