<?php
/**
* Copyright (c) Enalean, 2016 - 2018. All Rights Reserved.
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

namespace Tuleap\SVN\Repository;

use Backend;
use EventManager;
use HTTPRequest;
use ProjectManager;
use TuleapTestCase;

require_once __DIR__ .'/../../bootstrap.php';

class RepositoryManagerTest extends TuleapTestCase
{

    /**
     * @var RepositoryManager
     */
    private $manager;

    private $project_manager;
    private $dao;

    /**
     * @var HTTPRequest
     */
    private $request;

    public function setUp()
    {
        parent::setUp();

        $this->dao                   = mock('Tuleap\SVN\Dao');
        $this->project_manager       = mock('ProjectManager');
        $svn_admin                   = mock('Tuleap\SVN\SvnAdmin');
        $logger                      = mock('Logger');
        $system_command              = mock('System_Command');
        $destructor                  = mock('Tuleap\SVN\Repository\Destructor');
        $event_manager               = EventManager::instance();
        $backend                     = Backend::instance(Backend::SVN);
        $access_file_history_factory = mock('Tuleap\SVN\AccessControl\AccessFileHistoryFactory');
        $this->manager               = new RepositoryManager(
            $this->dao,
            $this->project_manager,
            $svn_admin,
            $logger,
            $system_command,
            $destructor,
            $event_manager,
            $backend,
            $access_file_history_factory
        );

        $this->project = stub("Project")->getID()->returns(101);

        stub($this->project_manager)->getProjectByUnixName('projectname')->returns($this->project);
        stub($this->project_manager)->getProject(101)->returns($this->project);

        stub($this->dao)->searchRepositoryByName($this->project, 'repositoryname')->returns(
            array(
                'id'                       => 1,
                'name'                     => 'repositoryname',
                'repository_deletion_date' => '0000-00-00 00:00:00',
                'backup_path'              => ''
            )
        );

        ProjectManager::setInstance($this->project_manager);

        $this->request = new HTTPRequest();
        $this->request->set('group_id', 101);
    }

    public function tearDown()
    {
        EventManager::clearInstance();
        Backend::clearInstances();
        ProjectManager::clearInstance();

        parent::tearDown();
    }

    public function itReturnsRepositoryFromAPublicPath()
    {
        stub($this->project)->getUnixNameMixedCase()->returns('projectname');
        $this->request->set('root', 'projectname/repositoryname');

        $repository = $this->manager->getRepositoryFromPublicPath($this->request);
        $this->assertEqual($repository->getName(), 'repositoryname');
    }

    public function itThrowsAnExceptionWhenRepositoryNameNotFound()
    {
        stub($this->project)->getUnixNameMixedCase()->returns('projectname');
        $this->request->set('root', 'projectname/repositoryko');

        $this->expectException('Tuleap\SVN\Repository\Exception\CannotFindRepositoryException');
        $this->manager->getRepositoryFromPublicPath($this->request);
    }

    public function itThrowsAnExceptionWhenProjectNameNotFound()
    {
        stub($this->project)->getUnixNameMixedCase()->returns('projectname');
        $this->request->set('root', 'projectnameko/repositoryname');

        $this->expectException('Tuleap\SVN\Repository\Exception\CannotFindRepositoryException');
        $this->manager->getRepositoryFromPublicPath($this->request);
    }

    public function itReturnsRepositoryFromAPublicPathWithLegacyAndNoMoreValidUnixName()
    {
        stub($this->project)->getUnixNameMixedCase()->returns('0abcd');
        $this->request->set('root', '0abcd/repositoryname');

        $repository = $this->manager->getRepositoryFromPublicPath($this->request);
        $this->assertEqual($repository->getName(), 'repositoryname');
    }
}
