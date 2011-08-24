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
require_once 'common/backend/Backend.class.php';

Mock::Generate('Backend');
Mock::Generate('Git_GitoliteDriver');

class Git_Backend_GitoliteTest extends UnitTestCase {
    
    protected $fixturesPath;
    
    public function setUp() {
        $this->fixtureRenamePath = dirname(__FILE__).'/_fixtures/rename';
        mkdir($this->fixtureRenamePath .'/legacy', 0777, true);
        symlink(dirname(__FILE__).'/_fixtures/perms', dirname(__FILE__).'/_fixtures/tmp/perms');
    }
    
    public function tearDown() {
        @rmdir($this->fixtureRenamePath .'/legacy');
        @rmdir($this->fixtureRenamePath .'/newone');
        @rmdir($this->fixtureRenamePath);
        unlink(dirname(__FILE__).'/_fixtures/tmp/perms');
    }
    
    function getPartialMock($className, $methods) {
        $partialName = $className.'Partial'.uniqid();
        Mock::generatePartial($className, $partialName, $methods);
        return new $partialName($this);
    }
    
    public function testRenameProjectOk() {
        $project = $this->getPartialMock('Project', array('getUnixName'));
        $project->setReturnValue('getUnixName', 'legacy');
        
        $backend = $this->getPartialMock('Git_Backend_Gitolite', array('glRenameProject', 'getBackend'));
        
        $driver = new MockGit_GitoliteDriver();
        $driver->setReturnValue('getRepositoriesPath', $this->fixtureRenamePath);
        $backend->setDriver($driver);
        
        $bck = new MockBackend();
        $bck->expectNever('log');
        $backend->setReturnValue('getBackend', $bck);
        
        $this->assertTrue(is_dir($this->fixtureRenamePath .'/legacy'));
        $this->assertFalse(is_dir($this->fixtureRenamePath .'/newone'));
        
        $backend->expectOnce('glRenameProject', array('legacy', 'newone'));
        $this->assertTrue($backend->renameProject($project, 'newone'));
        
        clearstatcache(true, $this->fixtureRenamePath .'/legacy');
        $this->assertFalse(is_dir($this->fixtureRenamePath .'/legacy'));
        $this->assertTrue(is_dir($this->fixtureRenamePath .'/newone'));
    }
    
    public function testDeletionPathShouldBeInProjectPath() {
        $backend = new Git_Backend_Gitolite(null);
        $this->assertTrue($backend->isSubPath(dirname(__FILE__).'/_fixtures/perms/', dirname(__FILE__).'/_fixtures/perms/default.conf'));
        $this->assertTrue($backend->isSubPath(dirname(__FILE__).'/_fixtures/perms/', dirname(__FILE__).'/_fixtures/tmp/perms/default.conf'));
        
        $this->assertFalse($backend->isSubPath(dirname(__FILE__).'/_fixtures/perms/', dirname(__FILE__).'/_fixtures/perms/../../default.conf'));
        $this->assertFalse($backend->isSubPath('_fixtures/perms/', 'coincoin'));
    }
    
    public function testDeletionShoultAffectDotGit() {
        $backend = new Git_Backend_Gitolite(null);
        $this->assertTrue($backend->isDotGit('default.git'));
        $this->assertFalse($backend->isDotGit('default.conf'));
        $this->assertFalse($backend->isDotGit('d'));
        $this->assertFalse($backend->isDotGit('defaultgit'));
    }
}

?>