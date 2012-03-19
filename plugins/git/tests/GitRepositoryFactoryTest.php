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

?>
