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

use Backend;
use Mock;
use SystemEventManager;
use TuleapTestCase;
use Project;
use Tuleap\Svn\Dao;
use \ProjectManager;
use \ProjectDao;
use EventManager;
use ForgeConfig;

require_once __DIR__ .'/../../bootstrap.php';

class RepositoryManagerTest extends TuleapTestCase
{

    private $manager;
    private $project_manager;
    private $dao;


    public function setUp()
    {
        parent::setUp();

        $this->dao                   = mock('Tuleap\Svn\Dao');
        $this->project_manager       = mock('ProjectManager');
        $svn_admin                   = mock('Tuleap\Svn\SvnAdmin');
        $logger                      = mock('Logger');
        $system_command              = mock('System_Command');
        $destructor                  = mock('Tuleap\Svn\Admin\Destructor');
        $hook_dao                    = mock('Tuleap\Svn\Repository\HookDao');
        $event_manager               = EventManager::instance();
        $backend                     = Backend::instance(Backend::SVN);
        $access_file_history_factory = mock('Tuleap\Svn\AccessControl\AccessFileHistoryFactory');
        $system_event_manager        = mock('SystemEventManager');
        $this->manager               = new RepositoryManager(
            $this->dao,
            $this->project_manager,
            $svn_admin,
            $logger,
            $system_command,
            $destructor,
            $hook_dao,
            $event_manager,
            $backend,
            $access_file_history_factory,
            $system_event_manager,
            mock('ProjectHistoryDao')
        );
        $project               = stub("Project")->getId()->returns(101);

        stub($this->project_manager)->getProjectByUnixName('projectname')->returns($project);
        stub($this->dao)->searchRepositoryByName($project, 'repositoryname')->returns(
            array(
                'id'                       => 1,
                'name'                     => 'repositoryname',
                'repository_deletion_date' => '0000-00-00 00:00:00',
                'backup_path'              => ''
            )
        );
    }

    public function tearDown()
    {
        EventManager::clearInstance();
        Backend::clearInstances();
    }

    public function itReturnsRepositoryFromAPublicPath(){
        $public_path = 'projectname/repositoryname';

        $repository = $this->manager->getRepositoryFromPublicPath($public_path);
        $this->assertEqual($repository->getName(), 'repositoryname');
    }

    public function itThrowsAnExceptionWhenRepositoryNameNotFound(){
        $public_path = 'projectname/repositoryko';

        $this->expectException('Tuleap\Svn\Repository\CannotFindRepositoryException');
        $this->manager->getRepositoryFromPublicPath($public_path);
    }

    public function itThrowsAnExceptionWhenProjectNameNotFound(){
        $public_path = 'projectnameko/repositoryname';

        $this->expectException('Tuleap\Svn\Repository\CannotFindRepositoryException');
        $this->manager->getRepositoryFromPublicPath($public_path);
    }
}

class RepositoryManagerHookConfigTest extends TuleapTestCase
{
    public function setUp()
    {
        $this->project_dao           = safe_mock('ProjectDao');
        $this->dao                   = safe_mock('Tuleap\Svn\Dao');
        $this->hook_dao              = mock('Tuleap\Svn\Repository\HookDao');
        $svn_admin                   = mock('Tuleap\Svn\SvnAdmin');
        $destructor                  = mock('Tuleap\Svn\Admin\Destructor');
        $logger                      = mock('Logger');
        $system_command              = mock('System_Command');
        $backend                     = Backend::instance(Backend::SVN);
        $access_file_history_factory = mock('Tuleap\Svn\AccessControl\AccessFileHistoryFactory');

        $this->project_manager = ProjectManager::testInstance($this->project_dao);
        $event_manager         = EventManager::instance();
        $system_event_manager  = mock('SystemEventManager');
        $this->manager         = new RepositoryManager(
            $this->dao,
            $this->project_manager,
            $svn_admin,
            $logger,
            $system_command,
            $destructor,
            $this->hook_dao,
            $event_manager,
            $backend,
            $access_file_history_factory,
            $system_event_manager,
            mock('ProjectHistoryDao')
        );

        $this->project = $this->project_manager->getProjectFromDbRow(array(
            'group_id' => 123,
            'unix_group_name' => 'test_project',
            'access' => 'private',
            'svn_tracker' => null,
            'svn_can_change_log' => null));
    }

    public function tearDown(){
        ProjectManager::clearInstance();
        EventManager::clearInstance();
    }

    public function itReturnsARepositoryWithHookConfig() {
        stub($this->hook_dao)->getHookConfig(33)->returns(array());

        $repo = new Repository(33, 'reponame', '', '', $this->project);
        $cfg = $this->manager->getHookConfig($repo);

        $mandatory_ref = $cfg->getHookConfig(HookConfig::MANDATORY_REFERENCE);
        $this->assertEqual(false, $mandatory_ref);
    }

    public function itReturnsARepositoryWithDifferentHookConfig() {
        stub($this->hook_dao)->getHookConfig(33)->returns(array(
            HookConfig::MANDATORY_REFERENCE => true));

        $repo = new Repository(33, 'reponame', '', '', $this->project);
        $cfg = $this->manager->getHookConfig($repo);

        $mandatory_ref = $cfg->getHookConfig(HookConfig::MANDATORY_REFERENCE);
        $this->assertEqual(true, $mandatory_ref);
    }

    public function itCanChangeTheHookConfig(){
        stub($this->hook_dao)->updateHookConfig(22, array(
            HookConfig::MANDATORY_REFERENCE => true
        ))->once()->returns(true);

        $this->manager->updateHookConfig(22, array(
            HookConfig::MANDATORY_REFERENCE => true,
            'foo' => true));
    }
}
