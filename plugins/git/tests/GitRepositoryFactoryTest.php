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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once dirname(__FILE__).'/../include/GitRepositoryFactory.class.php';

Mock::generate('GitDao');
Mock::generate('ProjectManager');
Mock::generate('Project');

class GitRepositoryFactoryTest extends UnitTestCase {
    
    function testGetRepositoryFromFullPath() {
        $dao            = new MockGitDao();
        $projectManager = new MockProjectManager();
        $project        = new MockProject();
        
        $project->setReturnValue('getID', 101);
        $project->setReturnValue('getUnixName', 'garden');
        
        $projectManager->setReturnValue('getProjectByUnixName', $project, array('garden'));
        
        $factory        = new GitRepositoryFactory($dao, $projectManager);
        
        $dao->expectOnce('searchProjectRepositoryByPath', array(101, 'garden/u/manuel/grou/ping/diskinstaller.git'));
        $dao->setReturnValue('searchProjectRepositoryByPath', new MockDataAccessResult());
        
        $factory->getFromFullPath('/data/tuleap/gitolite/repositories/garden/u/manuel/grou/ping/diskinstaller.git');
    }
    
    function testGetRepositoryFromFullPathAndGitRoot() {
        $dao            = new MockGitDao();
        $projectManager = new MockProjectManager();
        $project        = new MockProject();
        
        $project->setReturnValue('getID', 101);
        $project->setReturnValue('getUnixName', 'garden');
        
        $projectManager->setReturnValue('getProjectByUnixName', $project, array('garden'));
        
        $factory        = new GitRepositoryFactory($dao, $projectManager);
        
        $dao->expectOnce('searchProjectRepositoryByPath', array(101, 'garden/diskinstaller.git'));
        $dao->setReturnValue('searchProjectRepositoryByPath', new MockDataAccessResult());
        
        $factory->getFromFullPath('/data/tuleap/gitroot/garden/diskinstaller.git');
    }
}

class GitRepositoryFactory_IsInRepositoryNameTest extends TuleapTestCase {
    private $dao;
    private $project;
    private $factory;
    private $project_id;
    private $project_name;

    public function setUp() {
        parent::setUp();
        $this->project_id   = 12;
        $this->project_name = 'garden';
        $this->project      = mock('Project');
        stub($this->project)->getID()->returns($this->project_id);
        stub($this->project)->getUnixName()->returns($this->project_name);

        $this->dao        = mock('GitDao');
        $project_manager  = mock('ProjectManager');
        $this->factory    = new GitRepositoryFactory($this->dao, $project_manager);
    }
    
    public function itForbidCreationOfRepositoriesWhenPathAlreadyExists() {
        stub($this->dao)->getProjectRepositoryList($this->project_id, false, false)->returnsDar(
            array('repository_path' => $this->project_name.'/bla.git')
        );

        $this->assertTrue($this->factory->nameExistsAsRepositoryPath($this->project, 'bla/zoum'));
        $this->assertTrue($this->factory->nameExistsAsRepositoryPath($this->project, 'bla/zoum/zaz'));

        $this->assertFalse($this->factory->nameExistsAsRepositoryPath($this->project, 'zoum/bla'));
        $this->assertFalse($this->factory->nameExistsAsRepositoryPath($this->project, 'zoum/bla/top'));
        $this->assertFalse($this->factory->nameExistsAsRepositoryPath($this->project, 'blafoo'));
    }

    public function itForbidCreationOfRepositoriesWhenPathAlreadyExistsAndHasParents() {
        stub($this->dao)->getProjectRepositoryList($this->project_id, false, false)->returnsDar(
            array('repository_path' => $this->project_name.'/foo/bla.git')
        );

        $this->assertTrue($this->factory->nameExistsAsRepositoryPath($this->project, 'foo/bla/stuff'));
        $this->assertTrue($this->factory->nameExistsAsRepositoryPath($this->project, 'foo/bla/stuff/zaz'));

        $this->assertFalse($this->factory->nameExistsAsRepositoryPath($this->project, 'foo/bar'));
        $this->assertFalse($this->factory->nameExistsAsRepositoryPath($this->project, 'bla/foo'));
        $this->assertFalse($this->factory->nameExistsAsRepositoryPath($this->project, 'bla'));
    }

    public function itForbidCreationWhenNewRepoIsInsideExistingPath() {
        stub($this->dao)->getProjectRepositoryList($this->project_id, false, false)->returnsDar(
            array('repository_path' => $this->project_name.'/foo/bar/bla.git')
        );
        
        $this->assertTrue($this->factory->nameExistsAsRepositoryPath($this->project, 'foo'));
        $this->assertTrue($this->factory->nameExistsAsRepositoryPath($this->project, 'foo/bar'));

        $this->assertFalse($this->factory->nameExistsAsRepositoryPath($this->project, 'foo/bar/zorg'));
        $this->assertFalse($this->factory->nameExistsAsRepositoryPath($this->project, 'foo/zorg'));
        $this->assertFalse($this->factory->nameExistsAsRepositoryPath($this->project, 'foobar/zorg'));
    }
}
?>
