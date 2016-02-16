<?php
/**
* Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\Svn\Repository;

use Mock;
use TuleapTestCase;
use Project;
use Tuleap\Svn\Dao;


require_once __DIR__ .'/../../bootstrap.php';

class RepositoryManagerTest extends TuleapTestCase {

    private $manager;
    private $project_manager;
    private $dao;


    public function setUp() {
        parent::setUp();

        $this->dao = mock('Tuleap\Svn\Dao');
        $this->project_manager = mock('ProjectManager');
        $this->manager = new RepositoryManager($this->dao, $this->project_manager);
        $project     = stub("Project")->getId()->returns(101);

        stub($this->project_manager)->getProjectByUnixName('projectname')->returns($project);
        stub($this->dao)->searchRepositoryByName($project, 'repositoryname')->returns(
            array(
                'id'   => 1,
                'name' => 'repositoryname'
            )
        );
    }

    public function itReturnsRepositoryFromAPublicPath(){
        $public_path = 'projectname/repositoryname';

        $repository = $this->manager->getRepositoryAndProjectFromPublicPath($public_path);
        $this->assertEqual($repository->getName(), 'repositoryname');
    }

    public function itThrowsAnExceptionWhenRepositoryNameNotFound(){
        $public_path = 'projectname/repositoryko';

        $this->expectException('Tuleap\Svn\Repository\CannotFindRepositoryException');
        $this->manager->getRepositoryAndProjectFromPublicPath($public_path);
    }

    public function itThrowsAnExceptionWhenProjectNameNotFound(){
        $public_path = 'projectnameko/repositoryname';

        $this->expectException('Tuleap\Svn\Repository\CannotFindRepositoryException');
        $this->manager->getRepositoryAndProjectFromPublicPath($public_path);
    }
}