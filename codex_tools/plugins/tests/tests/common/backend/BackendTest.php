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

// This one seems necessary to use UserManager...
//require_once('common/dao/CodexDataAccess.class.php');

require_once('common/backend/Backend.class.php');
require_once('common/user/UserManager.class.php');
Mock::generate('UserManager');
require_once('common/user/User.class.php');
Mock::generate('User');
Mock::generatePartial('Backend', 'BackendTestVersion', array( '_getUserManager'));


class BackendTest extends UnitTestCase {
    
    function __construct($name = 'Backend test') {
        parent::__construct($name);
    }

    function setUp() {
        $GLOBALS['homedir_prefix']            = dirname(__FILE__) . '/_fixtures/home/users';
        $GLOBALS['codex_shell_skel']          = dirname(__FILE__) . '/_fixtures/etc/skel_codendi';
    }
    
    function tearDown() {
        unset($GLOBALS['homedir_prefix']);
        unset($GLOBALS['codex_shell_skel']);
    }
    
    function testConstructor() {
        $backend = new Backend();
    }
    
    function testrecurseDeleteIndir() {
        $test_dir =  dirname(__FILE__).'/_fixtures/test_dir';
        mkdir($test_dir);

        // Create dummy dirs and files
        mkdir($test_dir."/test1");
        mkdir($test_dir."/test1/A");
        mkdir($test_dir."/test1/B");
        mkdir($test_dir."/test2");
        mkdir($test_dir."/test2/A");
        mkdir($test_dir."/test3");
   
        // Run tested method
        Backend::recurseDeleteIndir($test_dir);

        // Check result

        // Direcory should not be removed
        $this->assertTrue(is_dir($test_dir),"Directory $test_dir should still exist");
        // And should be empty
        $d = opendir($test_dir);
        while (($file = readdir($d)) !== false) {
            $this->assertTrue($file == "." || $file == "..", "Directory should be empty");
        }
        closedir($d);
        rmdir($test_dir);
    }

    function testCreateUserHome() {

        $user =& new MockUser($this);
        $user->setReturnValue('getUserName', 'codexadm');
        //$user->setReturnValue('getRealUnixUID', 104); // We use codexadm uid/gid to avoid chown warning (because test is not run as root)

        $um =& new MockUserManager();
        $um->setReturnReference('getUserById', $user, array(104));
        
        $backend =& new BackendTestVersion($this);
        $backend->setReturnValue('_getUserManager', $um);

        $backend->Backend();
        $backend->createUserHome(104);
        $this->assertTrue(is_dir($GLOBALS['homedir_prefix']."/codexadm"),"Home dir should be created");

        $this->assertTrue(is_file($GLOBALS['homedir_prefix']."/codexadm/.profile"),"User files from /etc/codendi_skel should be created");
        // Cleanup
        Backend::recurseDeleteIndir($GLOBALS['homedir_prefix']);
   
    }

    
}
?>
