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


require_once('common/backend/BackendCVS.class.php');
require_once('common/user/UserManager.class.php');
Mock::generate('UserManager');
require_once('common/user/User.class.php');
Mock::generate('User');
require_once('common/project/ProjectManager.class.php');
Mock::generate('ProjectManager');
require_once('common/project/Project.class.php');
Mock::generate('Project');
Mock::generatePartial('BackendCVS', 'BackendCVSTestVersionInit', array('_getUserManager', 
                                                             '_getProjectManager',
                                                             'chown',
                                                             'chgrp',
                                                           ));

class BackendCVSTestVersion extends BackendCVSTestVersionInit {
 
    // log to apache error logs (does not seem to work??)
    function log($message) {
        echo "<br>LOG: $message\n";
    }
}

class BackendCVSTest extends UnitTestCase {
    
    function __construct($name = 'BackendCVS test') {
        parent::__construct($name);
    }

    function setUp() {
        $GLOBALS['cvs_prefix']                = dirname(__FILE__) . '/_fixtures/cvsroot';
        $GLOBALS['cvslock_prefix']            = dirname(__FILE__) . '/_fixtures/var/lock/cvs';
        $GLOBALS['tmp_dir']                   = dirname(__FILE__) . '/_fixtures/var/tmp';
        $GLOBALS['cvs_cmd']                   = "/usr/bin/cvs";
    }
    
    function tearDown() {
        unset($GLOBALS['cvs_prefix']);
        unset($GLOBALS['cvslock_prefix']);
        unset($GLOBALS['tmp_dir']);
        unset($GLOBALS['cvs_cmd']);
    }
    
    function testConstructor() {
        $backend = BackendCVS::instance();
    }
    

    function testArchiveProjectCVS() {
        $project =& new MockProject($this);
        $project->setReturnValue('getUnixName', 'TestProj',array(false));
        $project->setReturnValue('getUnixName', 'testproj',array(true));

        $pm =& new MockProjectManager();
        $pm->setReturnReference('getProject', $project, array(142));

        $backend =& new BackendCVSTestVersion($this);
        $backend->setReturnValue('_getProjectManager', $pm);

        $projdir=$GLOBALS['cvs_prefix']."/TestProj";

        // Setup test data
        mkdir($projdir);
        mkdir($projdir."/CVSROOT");
        
        //$this->assertTrue(is_dir($projdir),"Project dir should be created");

        $this->assertEqual($backend->archiveProjectCVS(142),True);
        $this->assertFalse(is_dir($projdir),"Project CVS repository should be deleted");
        $this->assertTrue(is_file($GLOBALS['tmp_dir']."/TestProj-cvs.tgz"),"CVS Archive should be created");

        // Check that a wrong project id does not raise an error
        $this->assertEqual($backend->archiveProjectCVS(99999),False);

        // Cleanup
        unlink($GLOBALS['tmp_dir']."/TestProj-cvs.tgz");
    }

    function testCreateProjectCVS() {
        $project =& new MockProject($this);
        $project->setReturnValue('getUnixName', 'TestProj',array(false));
        $project->setReturnValue('getUnixName', 'testproj',array(true));
        $project->setReturnValue('isCVSTracked',true);
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

        $pm =& new MockProjectManager();
        $pm->setReturnReference('getProject', $project, array(142));

        $backend =& new BackendCVSTestVersion($this);
        $backend->setReturnValue('_getProjectManager', $pm);

        $this->assertEqual($backend->createProjectCVS(142),True);
        $this->assertTrue(is_dir($GLOBALS['cvs_prefix']."/TestProj"),"CVS dir should be created");
        $this->assertTrue(is_dir($GLOBALS['cvs_prefix']."/TestProj/CVSROOT"),"CVSROOT dir should be created");
        $this->assertTrue(is_file($GLOBALS['cvs_prefix']."/TestProj/CVSROOT/loginfo"),"loginfo file should be created");

        $loginfo_file = file($GLOBALS['cvs_prefix']."/TestProj/CVSROOT/loginfo");
        $this->assertTrue(in_array($backend->block_marker_start."\n",$loginfo_file),"loginfo file should contain block");
        $commitinfo_file = file($GLOBALS['cvs_prefix']."/TestProj/CVSROOT/commitinfo");
        $this->assertTrue(in_array($backend->block_marker_start."\n",$commitinfo_file),"commitinfo file should contain block");

         $commitinfov_file = file($GLOBALS['cvs_prefix']."/TestProj/CVSROOT/commitinfo,v");
        $this->assertTrue(in_array($backend->block_marker_start."\n",$commitinfov_file),"commitinfo file should be under version control and contain block");
       
        $this->assertTrue(is_dir($GLOBALS['cvslock_prefix']."/TestProj"),"CVS lock dir should be created");

        $writers_file = file($GLOBALS['cvs_prefix']."/TestProj/CVSROOT/writers");
        $this->assertTrue(in_array("user1\n",$writers_file),"writers file should contain user1");
        $this->assertTrue(in_array("user2\n",$writers_file),"writers file should contain user2");
        $this->assertTrue(in_array("user3\n",$writers_file),"writers file should contain user3");

        // Cleanup
        $backend->recurseDeleteInDir($GLOBALS['cvs_prefix']."/TestProj");
        rmdir($GLOBALS['cvs_prefix']."/TestProj");
        rmdir($GLOBALS['cvslock_prefix']."/TestProj");
   }

}
?>
