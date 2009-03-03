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
Mock::generatePartial('BackendCVS', 'BackendCVSTestVersion', array('_getUserManager', 
                                                             '_getProjectManager',
                                                             'chown',
                                                             'chgrp',
                                                             ));


class BackendCVSTest extends UnitTestCase {
    
    function __construct($name = 'BackendCVS test') {
        parent::__construct($name);
    }

    function setUp() {
        $GLOBALS['cvs_prefix']                = dirname(__FILE__) . '/_fixtures/cvsroot';
        $GLOBALS['tmp_dir']                   = dirname(__FILE__) . '/_fixtures/var/tmp';
    }
    
    function tearDown() {
        unset($GLOBALS['cvs_prefix']);
        unset($GLOBALS['tmp_dir']);
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
}
?>
