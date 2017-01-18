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


require_once('common/backend/BackendSystem.class.php');
require_once('common/user/UserManager.class.php');
Mock::generate('UserManager');
require_once('common/user/User.class.php');
Mock::generate('PFUser');
require_once(dirname(__FILE__).'/../user/UserTestBuilder.php');
require_once('common/project/ProjectManager.class.php');
Mock::generate('ProjectManager');
require_once('common/project/Project.class.php');
Mock::generate('Project');
require_once('common/frs/FRSFileFactory.class.php');
Mock::generate('FRSFileFactory');
Mock::generate('WikiAttachment');
Mock::generatePartial('BackendSystem', 'BackendTestVersion', array('getUserManager', 
                                                             'getProjectManager',
                                                             'chown',
                                                             'chgrp',
                                                             'chmod',
                                                             'getFRSFileFactory',
                                                             'getWikiAttachment'
                                                             ));


class BackendSystemTest extends TuleapTestCase {

    function setUp() {
        parent::setUp();
        $GLOBALS['homedir_prefix']            = dirname(__FILE__) . '/_fixtures/home/users';
        $GLOBALS['grpdir_prefix']             = dirname(__FILE__) . '/_fixtures/home/groups';
        $GLOBALS['codendi_shell_skel']        = dirname(__FILE__) . '/_fixtures/etc/skel_codendi';
        $GLOBALS['tmp_dir']                   = dirname(__FILE__) . '/_fixtures/var/tmp';
        $GLOBALS['ftp_frs_dir_prefix']        = dirname(__FILE__) . '/_fixtures/var/lib/codendi/ftp/codendi';
        $GLOBALS['ftp_anon_dir_prefix']       = dirname(__FILE__) . '/_fixtures/var/lib/codendi/ftp/pub';
        $GLOBALS['sys_file_deletion_delay']   = 5;
        $GLOBALS['sys_custom_incdir']         = $GLOBALS['tmp_dir'];
        $GLOBALS['sys_incdir']                = $GLOBALS['tmp_dir'];
        $GLOBALS['codendi_log']               = $GLOBALS['tmp_dir'];
        ForgeConfig::store();
        ForgeConfig::set('sys_project_backup_path', dirname(__FILE__) . '/_fixtures/var/tmp');
    }
    
    
    function tearDown() {
        //clear the cache between each tests
        Backend::clearInstances();
        ForgeConfig::restore();

        $logfile = $GLOBALS['codendi_log'].'/codendi_syslog';
        if (is_file($logfile)) {
            unlink($logfile);
        }
        parent::tearDown();
    }
    
    function testConstructor() {
        $backend = BackendSystem::instance();
    }
    

    function testCreateUserHome() {
        // We use codendiadm uid/gid to avoid chown warnings (because test is not run as root)
        $user = aUser()->withUserName('codendiadm')->build();
        
        $backend = new BackendTestVersion();

        $this->assertEqual($backend->createUserHome($user),True);
        $this->assertTrue(is_dir($GLOBALS['homedir_prefix']."/codendiadm"),"Home dir should be created");

        $this->assertTrue(is_file($GLOBALS['homedir_prefix']."/codendiadm/.profile"),"User files from /etc/codendi_skel should be created");

        // Cleanup
        $backend->recurseDeleteInDir($GLOBALS['homedir_prefix']."/codendiadm");
        rmdir($GLOBALS['homedir_prefix']."/codendiadm");
   
    }
    
    function testCreateProjectHome() {

        $project = new MockProject($this);
        $project->setReturnValue('getUnixName', 'TestPrj',array(false));
        $project->setReturnValue('getUnixName', 'testprj',array(true));

        $pm = new MockProjectManager();
        $pm->setReturnReference('getProject', $project, array(142));
       

        $backend = new BackendTestVersion($this);
        $backend->setReturnValue('getProjectManager', $pm);

        $projdir=$GLOBALS['grpdir_prefix']."/TestPrj";
        $ftpdir = $GLOBALS['ftp_anon_dir_prefix']."/TestPrj";
        $frsdir = $GLOBALS['ftp_frs_dir_prefix']."/TestPrj";
      
        $this->assertEqual($backend->createProjectHome(142),True);
        $this->assertTrue(is_dir($projdir),"Project Home should be created");
        $this->assertTrue(is_dir($ftpdir),"Ftp dir should be created");
        $this->assertTrue(is_dir($frsdir),"Frs dir should be created");
      
        // Check that a wrong project id does not raise an error
        $this->assertEqual($backend->createProjectHome(99999),False);
        
        // Cleanup
        $backend->recurseDeleteInDir($projdir);
        unlink($GLOBALS['grpdir_prefix']."/testprj");
        rmdir($projdir);
  
        $backend->recurseDeleteInDir($ftpdir);
        rmdir($ftpdir);
       
        $backend->recurseDeleteInDir($frsdir);
        rmdir($frsdir);
    }

    function testArchiveUserHome() {
        // We use codendiadm uid/gid to avoid chown warnings (because test is not run as root)
        $user = aUser()->withUserName('codendiadm')->build();
        
        $backend = new BackendTestVersion();

        $backend->createUserHome($user);
        $this->assertTrue(is_dir($GLOBALS['homedir_prefix']."/codendiadm"),"Home dir should be created");

        //
        // Run test
        //
        
        $um = new MockUserManager();
        $um->setReturnReference('getUserById', $user, array(104));
        
        $backend->setReturnValue('getUserManager', $um);
        
        $this->assertEqual($backend->archiveUserHome(104),True);
        $this->assertFalse(is_dir($GLOBALS['homedir_prefix']."/codendiadm"),"Home dir should be deleted");
        $this->assertTrue(is_file(ForgeConfig::get('sys_project_backup_path')."/codendiadm.tgz"),"Archive should be created");

        // Cleanup
        unlink(ForgeConfig::get('sys_project_backup_path')."/codendiadm.tgz");
    }

    function testArchiveProjectHome() {
        $project = new MockProject($this);
        $project->setReturnValue('getUnixName', 'TestProj',array(false));
        $project->setReturnValue('getUnixName', 'testproj',array(true));

        $pm = new MockProjectManager();
        $pm->setReturnReference('getProject', $project, array(142));
        //$pm->setReturnReference('getProject', $project);

        $backend = new BackendTestVersion($this);
        $backend->setReturnValue('getProjectManager', $pm);

        $projdir=$GLOBALS['grpdir_prefix']."/TestProj";
        $lcprojlnk=$GLOBALS['grpdir_prefix']."/testproj";

        // Setup test data
        mkdir($projdir);
        touch($projdir."/testfile.txt");
        symlink($projdir,$lcprojlnk);
        
        //$this->assertTrue(is_dir($projdir),"Project dir should be created");

        $this->assertEqual($backend->archiveProjectHome(142),True);
        $this->assertFalse(is_dir($projdir),"Project dir should be deleted");
        $this->assertFalse(is_link($lcprojlnk),"Project link should be deleted");
        $this->assertTrue(is_file(ForgeConfig::get('sys_project_backup_path')."/TestProj.tgz"),"Archive should be created");

        // Check that a wrong project id does not raise an error
        $this->assertEqual($backend->archiveProjectHome(99999),False);

        // Cleanup
        unlink(ForgeConfig::get('sys_project_backup_path')."/TestProj.tgz");
    }
    
    public function testRenameProjectHomeDirectory() {
        $project = new MockProject($this);
        $project->setReturnValue('getUnixName', 'TestProject',array(false));
        $project->setReturnValue('getUnixName', 'testproject',array(true));
        
        $pm = new MockProjectManager();
        $pm->setReturnReference('getProject', $project, array(142));

       
        $backend = new BackendTestVersion($this);
        $backend->setReturnValue('getProjectManager', $pm);
      

        $backend->createProjectHome(142);
        
        $this->assertEqual($backend->renameProjectHomeDirectory($project, "FooBar"), true);
        
        $this->assertFalse(file_exists($GLOBALS['grpdir_prefix']."/TestProject"), "Old project home should no longer exists");
        $this->assertTrue(is_dir($GLOBALS['grpdir_prefix']."/FooBar"), "Project home should be renamed");
        
        $this->assertFalse(file_exists($GLOBALS['grpdir_prefix']."/testproject"), "Old project home lowercase version should no longer exists");
        $this->assertTrue(is_link($GLOBALS['grpdir_prefix']."/foobar"), "Project home lowercase version should be renamed");
        $this->assertEqual(readlink($GLOBALS['grpdir_prefix']."/foobar"), $GLOBALS['grpdir_prefix']."/FooBar", "Project home lowercase version should be link to the uppercase version");

        // Cleanup
        $backend->recurseDeleteInDir($GLOBALS['grpdir_prefix']."/FooBar");
        unlink($GLOBALS['grpdir_prefix']."/foobar");
        rmdir($GLOBALS['grpdir_prefix']."/FooBar");
        
        rmdir($GLOBALS['ftp_anon_dir_prefix']."/TestProject");
        rmdir($GLOBALS['ftp_frs_dir_prefix']."/TestProject");
    }
    
    /**
     * Special case when the project rename is just about changing case
     * TestProject -> testproject
     */
    public function testRenameProjectHomeDirectoryToLowerCase() {
        $project = new MockProject($this);
        $project->setReturnValue('getUnixName', 'TestProject',array(false));
        $project->setReturnValue('getUnixName', 'testproject',array(true));
        
        $pm = new MockProjectManager();
        $pm->setReturnReference('getProject', $project, array(142));

       
        $backend = new BackendTestVersion($this);
        $backend->setReturnValue('getProjectManager', $pm);
      

        $backend->createProjectHome(142);
        
        $this->assertEqual($backend->renameProjectHomeDirectory($project, "testproject"), true);
        
        $this->assertFalse(file_exists($GLOBALS['grpdir_prefix']."/TestProject"), "Old project home should no longer exists");
        $this->assertTrue(is_dir($GLOBALS['grpdir_prefix']."/testproject"), "Project home should be renamed");

        // Cleanup
        $backend->recurseDeleteInDir($GLOBALS['grpdir_prefix']."/testproject");
        rmdir($GLOBALS['grpdir_prefix']."/testproject");
        
        rmdir($GLOBALS['ftp_anon_dir_prefix']."/TestProject");
        rmdir($GLOBALS['ftp_frs_dir_prefix']."/TestProject");
    }
    
   /**
     * Special case when the project rename is just about changing case
     * testproject -> TestProject
     */
    public function testRenameProjectHomeDirectoryToUpperCase() {
        $project = new MockProject($this);
        $project->setReturnValue('getUnixName', 'testproject',array(false));
        $project->setReturnValue('getUnixName', 'testproject',array(true));
        
        $pm = new MockProjectManager();
        $pm->setReturnReference('getProject', $project, array(142));

       
        $backend = new BackendTestVersion($this);
        $backend->setReturnValue('getProjectManager', $pm);
      

        $backend->createProjectHome(142);
        
        $this->assertEqual($backend->renameProjectHomeDirectory($project, "TestProject"), true);
        
        // Not test possible with is_dir because is_dir resolve the link.
        // Testing lower case as a link is enough (see below).
        //$this->assertFalse(is_dir($GLOBALS['grpdir_prefix']."/testproject"), "Old project home should no longer exists as directory (it's a link now)");
        $this->assertTrue(is_dir($GLOBALS['grpdir_prefix']."/TestProject"), "Project home should be renamed");
        $this->assertEqual(readlink($GLOBALS['grpdir_prefix'].'/testproject'),$GLOBALS['grpdir_prefix'].'/TestProject',"The lower case of project should be a link");
        

        // Cleanup
        $backend->recurseDeleteInDir($GLOBALS['grpdir_prefix']."/TestProject");
        rmdir($GLOBALS['grpdir_prefix']."/TestProject");
        unlink($GLOBALS['grpdir_prefix'].'/testproject');
        
        rmdir($GLOBALS['ftp_anon_dir_prefix']."/testproject");
        rmdir($GLOBALS['ftp_frs_dir_prefix']."/testproject");
    }

    /**
     * testproject -> projecttest
     */
    public function testRenameProjectHomeDirectoryLowerCase() {
        $project = new MockProject($this);
        $project->setReturnValue('getUnixName', 'testproject',array(false));
        $project->setReturnValue('getUnixName', 'testproject',array(true));
        
        $pm = new MockProjectManager();
        $pm->setReturnReference('getProject', $project, array(142));

       
        $backend = new BackendTestVersion($this);
        $backend->setReturnValue('getProjectManager', $pm);
      

        $backend->createProjectHome(142);
        
        $this->assertEqual($backend->renameProjectHomeDirectory($project, "projecttest"), true);
        
        $this->assertFalse(file_exists($GLOBALS['grpdir_prefix']."/testproject"), "Old project home should no longer exists");
        $this->assertTrue(is_dir($GLOBALS['grpdir_prefix']."/projecttest"), "Project home should be renamed");

        // Cleanup
        $backend->recurseDeleteInDir($GLOBALS['grpdir_prefix']."/projecttest");
        rmdir($GLOBALS['grpdir_prefix']."/projecttest");
        
        rmdir($GLOBALS['ftp_anon_dir_prefix']."/testproject");
        rmdir($GLOBALS['ftp_frs_dir_prefix']."/testproject");
    }

    public function testIsProjectNameAvailableWithExistingFileInProjectHome() {
        touch($GLOBALS['grpdir_prefix']."/testproject");
        $backend = new BackendTestVersion($this);
        $this->assertFalse($backend->isProjectNameAvailable('testproject'), 'A file with the same name exists in home/groups/');
        unlink($GLOBALS['grpdir_prefix']."/testproject");
    }

    public function testIsProjectNameAvailableWithExistingFileInProjectHomeWithMixedCase() {
        touch($GLOBALS['grpdir_prefix']."/testproject");
        $backend = new BackendTestVersion($this);
        $this->assertFalse($backend->isProjectNameAvailable('TestProject'), 'A file with the same name in lowercase exists in home/groups/');
        unlink($GLOBALS['grpdir_prefix']."/testproject");
    }
    
    public function testIsProjectNameAvailableWithExistingFileInFRS() {
        touch($GLOBALS['ftp_frs_dir_prefix']."/testproject");
        $backend = new BackendTestVersion($this);
        $this->assertFalse($backend->isProjectNameAvailable('testproject'), 'A file with the same name exists in var/lib/codendi/ftp/codendi');
        unlink($GLOBALS['ftp_frs_dir_prefix']."/testproject");
    }
    
    public function testIsProjectNameAvailableWithExistingFileInAnnoFtp() {
        touch($GLOBALS['ftp_anon_dir_prefix']."/testproject");
        $backend = new BackendTestVersion($this);
        $this->assertFalse($backend->isProjectNameAvailable('testproject'), 'A file with the same name exists in var/lib/codendi/ftp/pub');
        unlink($GLOBALS['ftp_anon_dir_prefix']."/testproject");
    }
    
    public function testRenameUserHomeDirectory() {
        // We use codendiadm uid/gid to avoid chown warnings (because test is not run as root)
        $user = aUser()->withUserName('codendiadm')->build();
        
        $backend = new BackendTestVersion($this);
        
        $backend->createUserHome($user);
        $this->assertEqual($backend->renameUserHomeDirectory($user, 'toto'),True);
        $this->assertTrue(is_dir($GLOBALS['homedir_prefix']."/toto"),"Home dir should be created");

        $this->assertFalse(is_dir($GLOBALS['homedir_prefix']."/codendiadm"),"Home dir should no more exists");
        
        // Cleanup
        $backend->recurseDeleteInDir($GLOBALS['homedir_prefix']."/toto");
        rmdir($GLOBALS['homedir_prefix']."/toto");
   
    }
    
    public function testCleanupFrs() {
        $backend = new BackendTestVersion($this);
        
        $daysBefore     = $_SERVER['REQUEST_TIME'] - (24*3600*5);
        
        $ff = new MockFRSFileFactory($this);
        $ff->setReturnValue('moveFiles', true);
        //$ff->expectOnce('moveFiles', array($daysBefore, $backend));

        $wiki = new MockWikiAttachment($this);
        $wiki->setReturnValue('purgeAttachments', true);
        
        $backend->setReturnValue('getFRSFileFactory', $ff);
        $backend->setReturnValue('getWikiAttachment', $wiki);
        
        $this->assertTrue($backend->cleanupFRS());
    }
}

?>
