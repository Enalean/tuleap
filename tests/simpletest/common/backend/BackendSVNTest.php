<?php
/* 
 * Copyright (c) The Codendi Team, Xerox, 2009. All Rights Reserved.
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * 
 */


require_once('common/backend/BackendSVN.class.php');
require_once('common/user/UserManager.class.php');
Mock::generate('UserManager');
require_once('common/user/User.class.php');
Mock::generate('PFUser');
require_once('common/project/ProjectManager.class.php');
Mock::generate('ProjectManager');
require_once('common/project/Project.class.php');
Mock::generate('Project');
require_once('common/dao/UGroupDao.class.php');
Mock::generate('UGroupDao');
require_once('common/project/UGroup.class.php');
Mock::generate('UGroup');
require_once('common/dao/ServiceDao.class.php');
Mock::generate('ServiceDao');
require_once('common/svn/SVNAccessFile.class.php');
Mock::generate('SVNAccessFile');
Mock::generate('EventManager');
Mock::generatePartial('BackendSVN', 'BackendSVNTestVersion', array('getUserManager', 
                                                                   'getProjectManager',
                                                                   'getUGroupDao',
                                                                   'getUGroupFromRow',
                                                                   '_getServiceDao',
                                                                   'chown',
                                                                   'chgrp',
                                                                   'chmod',
                                                                   '_getSVNAccessFile'
                                                                   ));

Mock::generatePartial('BackendSVN', 'BackendSVNAccessTestVersion', array('updateSVNAccess',
                                                                         'repositoryExists',
                                                                         'getAllProjects',
                                                                         'getProjectManager',
                                                                        ));
                                                                   
class BackendSVNTest extends UnitTestCase {

    function setUp() {
        $GLOBALS['svn_prefix']                = dirname(__FILE__) . '/_fixtures/svnroot';
        $GLOBALS['tmp_dir']                   = dirname(__FILE__) . '/_fixtures/var/tmp';
        $GLOBALS['svn_root_file']             = dirname(__FILE__) . '/_fixtures/etc/httpd/conf.d/codendi_svnroot.conf';
        $GLOBALS['sys_dbname']                = 'db';
        $GLOBALS['sys_name']                  = 'MyForge';
        $GLOBALS['sys_dbauth_user']           = 'dbauth_user';
        $GLOBALS['sys_dbauth_passwd']         = 'dbauth_passwd';
        mkdir($GLOBALS['svn_prefix'] . '/' . 'toto', 0777, true);
    }
    
    
    function tearDown() {
        //clear the cache between each tests
        Backend::clearInstances();
        rmdir($GLOBALS['svn_prefix'] . '/' . 'toto');
        unset($GLOBALS['svn_prefix']);
        unset($GLOBALS['tmp_dir']);
        unset($GLOBALS['svn_root_file']);
        unset($GLOBALS['sys_dbname']);
        unset($GLOBALS['sys_name']);
        unset($GLOBALS['sys_dbauth_user']);
        unset($GLOBALS['sys_dbauth_passwd']);
    }
    
    function testConstructor() {
        $backend = BackendSVN::instance();
    }
    

    function testArchiveProjectSVN() { 
        $project = new MockProject($this);
        $project->setReturnValue('getUnixName', 'TestProj',array(false));
        $project->setReturnValue('getUnixName', 'testproj',array(true));

        $pm = new MockProjectManager();
        $pm->setReturnReference('getProject', $project, array(142));

        $backend = new BackendSVNTestVersion($this);
        $backend->setReturnValue('getProjectManager', $pm);

        $projdir=$GLOBALS['svn_prefix']."/TestProj";

        // Setup test data
        mkdir($projdir);
        mkdir($projdir."/db");
        
        $this->assertEqual($backend->archiveProjectSVN(142),True);
        $this->assertFalse(is_dir($projdir),"Project SVN repository should be deleted");
        $this->assertTrue(is_file($GLOBALS['tmp_dir']."/TestProj-svn.tgz"),"SVN Archive should be created");

        // Check that a wrong project id does not raise an error
        $this->assertEqual($backend->archiveProjectSVN(99999),False);

        // Cleanup
        unlink($GLOBALS['tmp_dir']."/TestProj-svn.tgz");
    }


    function testCreateProjectSVN() { 
        $project = new MockProject($this);
        $project->setReturnValue('getUnixName', 'TestProj',array(false));
        $project->setReturnValue('getUnixName', 'testproj',array(true));
        $project->setReturnValue('isSVNTracked',true);
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
        $project->setReturnValue('getMembersUserNames',$proj_members);

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
        $ugdao->setReturnValue('searchByGroupId',$ugroups);

        $ugroup = new MockUGroup($this);
        $ugroup->setReturnValueAt(0,'getMembersUserName',array('user1', 'user2', 'user3'));
        $ugroup->setReturnValueAt(1,'getMembersUserName',array('user1', 'user4'));
        $ugroup->setReturnValueAt(0,'getName',"QA");
        $ugroup->setReturnValueAt(1,'getName',"QA");
        $ugroup->setReturnValueAt(2,'getName',"customers");
        $ugroup->setReturnValueAt(3,'getName',"customers");


        $backend = new BackendSVNTestVersion($this);
        $backend->setReturnValue('getProjectManager', $pm);
        $backend->setReturnValue('getUGroupFromRow', $ugroup);
        $backend->setReturnValue('getUGroupDao', $ugdao);

        $this->assertEqual($backend->createProjectSVN(142),True);
        $this->assertTrue(is_dir($GLOBALS['svn_prefix']."/TestProj"),"SVN dir should be created");
        $this->assertTrue(is_dir($GLOBALS['svn_prefix']."/TestProj/hooks"),"hooks dir should be created");
        $this->assertTrue(is_file($GLOBALS['svn_prefix']."/TestProj/hooks/post-commit"),"post-commit file should be created");


        // Cleanup
        $backend->recurseDeleteInDir($GLOBALS['svn_prefix']."/TestProj");
        rmdir($GLOBALS['svn_prefix']."/TestProj");
    }

    function testUpdateSVNAccess() {
        $project = new MockProject($this);
        $project->setReturnValue('getUnixName', 'TestProj',array(false));
        $project->setReturnValue('getUnixName', 'testproj',array(true));
        $project->setReturnValue('isSVNTracked',true);
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
        $project->setReturnValue('getMembersUserNames',$proj_members);

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
        $ugdao->setReturnValue('searchByGroupId',$ugroups);

        $ugroup = new MockUGroup($this);
        $ugroup->setReturnValueAt(0,'getMembersUserName',array('user1', 'user2', 'user3'));
        $ugroup->setReturnValueAt(1,'getMembersUserName',array('user1', 'user4'));
        $ugroup->setReturnValueAt(2,'getMembersUserName',array('user1', 'user2', 'user3'));
        $ugroup->setReturnValueAt(3,'getMembersUserName',array('user1', 'user4'));
        $ugroup->setReturnValueAt(4,'getMembersUserName',array('user1', 'user2', 'user3'));
        $ugroup->setReturnValueAt(5,'getMembersUserName',array('user1', 'user4', 'user5'));
        $ugroup->setReturnValueAt(0,'getName',"QA");
        $ugroup->setReturnValueAt(1,'getName',"QA");
        $ugroup->setReturnValueAt(4,'getName',"QA");
        $ugroup->setReturnValueAt(5,'getName',"QA");
        $ugroup->setReturnValueAt(8,'getName',"QA");
        $ugroup->setReturnValueAt(9,'getName',"QA");
        $ugroup->setReturnValueAt(2,'getName',"customers");
        $ugroup->setReturnValueAt(3,'getName',"customers");
        $ugroup->setReturnValueAt(6,'getName',"customers");
        $ugroup->setReturnValueAt(7,'getName',"customers");
        $ugroup->setReturnValueAt(10,'getName',"customers");
        $ugroup->setReturnValueAt(11,'getName',"customers");


        $backend = new BackendSVNTestVersion($this);
        $backend->setReturnValue('getProjectManager', $pm);
        $backend->setReturnValue('getUGroupFromRow', $ugroup);
        $backend->setReturnValue('getUGroupDao', $ugdao);

        $this->assertEqual($backend->createProjectSVN(142),True);
        $this->assertTrue(is_dir($GLOBALS['svn_prefix']."/TestProj"),"SVN dir should be created");
        $this->assertTrue(is_file($GLOBALS['svn_prefix']."/TestProj/.SVNAccessFile"),"SVN access file should be created");

        $saf = new MockSVNAccessFile();
        $backend->setReturnValue('_getSVNAccessFile', $saf);
        // Update without modification
        $this->assertEqual($backend->updateSVNAccess(142),True);
        $this->assertTrue(is_file($GLOBALS['svn_prefix']."/TestProj/.SVNAccessFile"),"SVN access file should exist");
        $this->assertTrue(is_file($GLOBALS['svn_prefix']."/TestProj/.SVNAccessFile.new"),"SVN access file (.new) should be created");
        $this->assertFalse(is_file($GLOBALS['svn_prefix']."/TestProj/.SVNAccessFile.old"),"SVN access file (.old) should not be created");
        // Update with modification
        $this->assertEqual($backend->updateSVNAccess(142),True);
        $this->assertFalse(is_file($GLOBALS['svn_prefix']."/TestProj/.SVNAccessFile.new"),"SVN access file (.new) should be removed");
        $this->assertTrue(is_file($GLOBALS['svn_prefix']."/TestProj/.SVNAccessFile.old"),"SVN access file (.old) should be created");

        // Cleanup
        $backend->recurseDeleteInDir($GLOBALS['svn_prefix']."/TestProj");
        rmdir($GLOBALS['svn_prefix']."/TestProj");
    }


    function testGenerateSVNApacheConf() {
        $backend = new BackendSVNTestVersion($this);
        $service_dao = new MockServiceDao($this);
        $active_groups = array("0" =>
                              array (
                                     "group_id"=> "101",
                                     "group_name"  => "Guinea Pig",
                                     "unix_group_name" => "gpig"),
                               "1" =>
                              array (
                                     "group_id"=> "102",
                                     "group_name"  => "Guinea Pig is \"back\"",
                                     "unix_group_name" => "gpig2"),
                               "2" =>
                              array (
                                     "group_id"=> "103",
                                     "group_name"  => "Guinea Pig is 'angry'",
                                     "unix_group_name" => "gpig3"));

        $service_dao->setReturnValue('searchActiveUnixGroupByUsedService',$active_groups);
        $backend->setReturnReference('_getServiceDao', $service_dao);

        $this->assertEqual($backend->generateSVNApacheConf(),True);
        $svnroots=file_get_contents($GLOBALS['svn_root_file']);
        $this->assertFalse($svnroots === false);
        $this->assertPattern("/gpig2/",$svnroots,"Project name not found in SVN root");
        $this->assertPattern("/AuthName \"Subversion Authorization \(Guinea Pig is 'back'\)\"/",$svnroots,"Group name double quotes in realm");

        // Cleanup
        unlink($GLOBALS['svn_root_file']);
    }
    
    public function testSetSVNPrivacy_private() {
        $backend = new BackendSVNTestVersion($this);
        $backend->setReturnValue('chmod', true);
        $backend->expectOnce('chmod', array($GLOBALS['svn_prefix'] . '/' . 'toto', 0770));
        
        $project = new MockProject($this);
        $project->setReturnValue('getUnixName', 'toto');
        
        $this->assertTrue($backend->setSVNPrivacy($project, true));
    }
    
    public function testsetSVNPrivacy_public() {
        $backend = new BackendSVNTestVersion($this);
        $backend->setReturnValue('chmod', true);
        $backend->expectOnce('chmod', array($GLOBALS['svn_prefix'] . '/' . 'toto', 0775));
        
        $project = new MockProject($this);
        $project->setReturnValue('getUnixName', 'toto');
        
        $this->assertTrue($backend->setSVNPrivacy($project, false));
    }
    
    public function testSetSVNPrivacy_no_repository() {
        $path_that_doesnt_exist = md5(uniqid(rand(), true));
        
        $backend = new BackendSVNTestVersion($this);
        $backend->expectNever('chmod');
        
        $project = new MockProject($this);
        $project->setReturnValue('getUnixName', $path_that_doesnt_exist);
        
        $this->assertFalse($backend->setSVNPrivacy($project, true));
        $this->assertFalse($backend->setSVNPrivacy($project, false));
    }
    
    public function testRenameSVNRepository() {
        $project = new MockProject($this);
        $project->setReturnValue('getUnixName', 'TestProj',array(false));
        $project->setReturnValue('getUnixName', 'testproj',array(true));
        $project->setReturnValue('isSVNTracked',false);

        $project->setReturnValue('getMembersUserNames',array());

        $pm = new MockProjectManager();
        $pm->setReturnReference('getProject', $project, array(142));

        $ugdao = new MockUGroupDao();
        $ugdao->setReturnValue('searchByGroupId', array());

        $backend = new BackendSVNTestVersion($this);
        $backend->setReturnValue('getProjectManager', $pm);
        $backend->setReturnValue('getUGroupDao', $ugdao);

        $backend->createProjectSVN(142);
        
        $this->assertEqual($backend->renameSVNRepository($project, "foobar"), true);
        
        $this->assertTrue(is_dir($GLOBALS['svn_prefix']."/foobar"),"SVN dir should be renamed");

        // Cleanup
        $backend->recurseDeleteInDir($GLOBALS['svn_prefix']."/foobar");
        rmdir($GLOBALS['svn_prefix']."/foobar");
    }
    
    public function testUpdateSVNAccessForGivenMember() {
    
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
?>
