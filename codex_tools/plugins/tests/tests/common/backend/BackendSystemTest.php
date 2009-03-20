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
Mock::generate('User');
require_once('common/project/ProjectManager.class.php');
Mock::generate('ProjectManager');
require_once('common/project/Project.class.php');
Mock::generate('Project');
Mock::generatePartial('BackendSystem', 'BackendTestVersion', array('_getUserManager', 
                                                             '_getProjectManager',
                                                             'chown',
                                                             'chgrp',
                                                             ));


class BackendSystemTest extends UnitTestCase {
    
    function __construct($name = 'BackendSystem test') {
        parent::__construct($name);
    }

    function setUp() {
        $GLOBALS['homedir_prefix']            = dirname(__FILE__) . '/_fixtures/home/users';
        $GLOBALS['grpdir_prefix']             = dirname(__FILE__) . '/_fixtures/home/groups';
        $GLOBALS['codendi_shell_skel']        = dirname(__FILE__) . '/_fixtures/etc/skel_codendi';
        $GLOBALS['tmp_dir']                   = dirname(__FILE__) . '/_fixtures/var/tmp';
    }
    
    function tearDown() {
        unset($GLOBALS['homedir_prefix']);
        unset($GLOBALS['grpdir_prefix']);
        unset($GLOBALS['codendi_shell_skel']);
        unset($GLOBALS['tmp_dir']);
    }
    
    function testConstructor() {
        $backend = BackendSystem::instance();
    }
    

    function testCreateUserHome() {

        $user =& new MockUser($this);
        // We use codexadm uid/gid to avoid chown warnings (because test is not run as root)
        $user->setReturnValue('getUserName', 'codexadm');

        $um =& new MockUserManager();
        $um->setReturnReference('getUserById', $user, array(104));
        
        $backend =& new BackendTestVersion($this);
        $backend->setReturnValue('_getUserManager', $um);

        $this->assertEqual($backend->createUserHome(104),True);
        $this->assertTrue(is_dir($GLOBALS['homedir_prefix']."/codexadm"),"Home dir should be created");

        $this->assertTrue(is_file($GLOBALS['homedir_prefix']."/codexadm/.profile"),"User files from /etc/codendi_skel should be created");


        // Check that a wrong user id does not raise an error
        $this->assertEqual($backend->createUserHome(99999),False);

        // Cleanup
        $backend->recurseDeleteInDir($GLOBALS['homedir_prefix']."/codexadm");
        rmdir($GLOBALS['homedir_prefix']."/codexadm");
   
    }

    function testArchiveUserHome() {
        $user =& new MockUser($this);
        // We use codexadm uid/gid to avoid chown warnings (because test is not run as root)
        $user->setReturnValue('getUserName', 'codexadm');

        $um =& new MockUserManager();
        $um->setReturnReference('getUserById', $user, array(104));
        
        $backend =& new BackendTestVersion($this);
        $backend->setReturnValue('_getUserManager', $um);

        $backend->createUserHome(104);
        $this->assertTrue(is_dir($GLOBALS['homedir_prefix']."/codexadm"),"Home dir should be created");

        $this->assertEqual($backend->archiveUserHome(104),True);
        $this->assertFalse(is_dir($GLOBALS['homedir_prefix']."/codexadm"),"Home dir should be deleted");
        $this->assertTrue(is_file($GLOBALS['tmp_dir']."/codexadm.tgz"),"Archive should be created");

        // Check that a wrong user id does not raise an error
        $this->assertEqual($backend->archiveUserHome(99999),False);

        // Cleanup
        unlink($GLOBALS['tmp_dir']."/codexadm.tgz");
    }

    function testArchiveProjectHome() {
        $project =& new MockProject($this);
        $project->setReturnValue('getUnixName', 'TestProj',array(false));
        $project->setReturnValue('getUnixName', 'testproj',array(true));

        $pm =& new MockProjectManager();
        $pm->setReturnReference('getProject', $project, array(142));
        //$pm->setReturnReference('getProject', $project);

        $backend =& new BackendTestVersion($this);
        $backend->setReturnValue('_getProjectManager', $pm);

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
        $this->assertTrue(is_file($GLOBALS['tmp_dir']."/TestProj.tgz"),"Archive should be created");

        // Check that a wrong project id does not raise an error
        $this->assertEqual($backend->archiveProjectHome(99999),False);

        // Cleanup
        unlink($GLOBALS['tmp_dir']."/TestProj.tgz");
    }

}
?>
