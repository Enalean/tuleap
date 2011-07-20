<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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

require_once dirname(__FILE__).'/../include/Git_Backend_Gitolite.class.php';
require_once dirname(__FILE__).'/../include/Git_GitoliteDriver.class.php';
require_once 'common/project/Project.class.php';

Mock::generate('Git_GitoliteDriver');
Mock::generatePartial('Project', 'Project_BackendTestVersion', array('getUnixName'));

class Git_Backend_GitoliteTest extends UnitTestCase {
    
    protected $fixturesPath;
    
    public function setUp() {
        $this->fixtureRenamePath = dirname(__FILE__).'/_fixtures/rename';
        mkdir($this->fixtureRenamePath .'/legacy', 0777, true);
    }
    
    public function tearDown() {
        @rmdir($this->fixtureRenamePath .'/legacy');
        @rmdir($this->fixtureRenamePath .'/newone');
        @rmdir($this->fixtureRenamePath);
    }
    
    public function test_renameProject() {
        $project = new Project_BackendTestVersion();
        $project->setReturnValue('getUnixName', 'legacy');
        
        $driver = new MockGit_GitoliteDriver();
        $driver->setReturnValue('getRepositoriesPath', $this->fixtureRenamePath);
        $driver->expectOnce('renameProject', array($project, 'newone'));
        $driver->setReturnValue('renameProject', true);
        
        $this->assertTrue(is_dir($this->fixtureRenamePath .'/legacy'));
        $this->assertFalse(is_dir($this->fixtureRenamePath .'/newone'));
        
        $backend = new Git_Backend_Gitolite($driver);
        $this->assertTrue($backend->renameProject($project, 'newone'));
        
        clearstatcache(true, $this->fixtureRenamePath .'/legacy');
        $this->assertFalse(is_dir($this->fixtureRenamePath .'/legacy'));
        $this->assertTrue(is_dir($this->fixtureRenamePath .'/newone'));
    }
}

?>