<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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
 */

require_once dirname(__FILE__).'/../include/GitoliteDriver.class.php';

Mock::generate('Project');

class GitoliteDriverTest extends UnitTestCase {

    function setUp() {
        $this->_fixDir = dirname(__FILE__).'/_fixtures';
    }

    function testCreateRepository() {
        $driver = new Git_GitoliteDriver($this->_fixDir.'/gitolite-admin');
        
        $prj = new MockProject($this);
        $prj->setReturnValue('getUnixName', 'project1');
        
        $this->assertTrue($driver->init($prj, 'testrepo'));
        
        // Check file content
        $this->assertTrue(is_file($this->_fixDir.'/gitolite-admin/conf/projects/project1.conf'));
        $gitoliteConf = file($this->_fixDir.'/gitolite-admin/conf/projects/project1.conf', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        // Check repository def
        $repo1found = false;
        for ($i = 0; $i < count($gitoliteConf); $i++) {
            if ($gitoliteConf[$i] == 'repo project1/testrepo') {
                $repo1found = true;
                // Check default permissions
                $this->assertEqual($gitoliteConf[++$i], "\tRW = @project1_project_members");
            }
        }
        $this->assertTrue($repo1found);
        
        // Last: check that corresponding project conf exists in main file conf
        $this->assertTrue(is_file($this->_fixDir.'/gitolite-admin/conf/gitolite.conf'));
        $gitoliteConf = file_get_contents($this->_fixDir.'/gitolite-admin/conf/gitolite.conf');
        $this->assertWantedPattern('#^include "projects/project1.conf"$#m', $gitoliteConf);
        
        // Cleanup
        unlink($this->_fixDir.'/gitolite-admin/conf/projects/project1.conf');
        rmdir($this->_fixDir.'/gitolite-admin/conf/projects');
        unlink($this->_fixDir.'/gitolite-admin/conf/gitolite.conf');
    }
}

?>