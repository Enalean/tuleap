<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

use Tuleap\SVN\Dao;

require_once __DIR__ . '/../../bootstrap.php';

class RepositoryDeleterTest extends \TuleapTestCase
{
    /**
     * @var \SystemEventManager
     */
    private $system_event_manager;
    /**
     * @var RepositoryManager
     */
    private $repository_manager;
    /**
     * @var Dao
     */
    private $dao;
    /**
     * @var \Project
     */
    private $project;
    /**
     * @var Repository
     */
    private $repository;
    /**
     * @var RepositoryDeleter
     */
    private $repository_deleter;
    /**
     * @var \System_Command
     */
    private $system_command;

    private $fixtures_dir;

    public function setUp()
    {
        parent::setUp();

        $this->system_command       = mock('System_Command');
        $project_history_dao        = mock('ProjectHistoryDao');
        $this->dao                  = mock('Tuleap\SVN\Dao');
        $this->system_event_manager = mock('SystemEventManager');
        $this->repository_manager   = mock('Tuleap\SVN\Repository\RepositoryManager');

        $this->repository_deleter = new RepositoryDeleter(
            $this->system_command,
            $project_history_dao,
            $this->dao,
            $this->system_event_manager,
            $this->repository_manager
        );

        $this->repository = mock('Tuleap\SVN\Repository\Repository');
        $this->project    = aMockProject()->withId(101)->build();

        $this->fixtures_dir = dirname(__FILE__) . '/../_fixtures';
    }

    public function itReturnFalseWhenRepositoryIsNotLinkedToAProject()
    {
        stub($this->repository)->getProject()->returns(null);

        $this->assertFalse($this->repository_deleter->delete($this->repository));
    }

    public function itReturnFalseWhenRepositoryIsNotFoundOnFileSystem()
    {
        stub($this->repository)->getProject()->returns($this->project);
        stub($this->repository)->getSystemPath()->returns('/a/non/existing/path');

        $this->assertFalse($this->repository_deleter->delete($this->repository));
    }

    public function itDeleteTheRepository()
    {
        stub($this->repository)->getProject()->returns($this->project);
        stub($this->repository)->getSystemPath()->returns($this->fixtures_dir);
        stub($this->system_command)->exec()->returns(true);

        $this->assertFalse($this->repository_deleter->delete($this->repository));
    }

    public function itThrowsAnExceptionWhenRepositoryCantBeMarkedAsDeleted()
    {
        stub($this->repository)->canBeDeleted()->returns(false);
        $this->expectException('Tuleap\SVN\Repository\Exception\CannotDeleteRepositoryException');

        $this->repository_deleter->markAsDeleted($this->repository);
    }

    public function itMarkTheRepositoryAsDeleted()
    {
        stub($this->repository)->canBeDeleted()->returns(true);
        expect($this->dao)->markAsDeleted()->once();

        $this->repository_deleter->markAsDeleted($this->repository);
    }

    public function itShouldRemoveAllRepositoryOfAProject()
    {
        stub($this->repository_manager)->getRepositoriesInProject()->returns(
            array(
                new Repository(1, 'repo01', '', '', $this->project),
                new Repository(2, 'repo02', '', '', $this->project),
                new Repository(3, 'repo03', '', '', $this->project),
            )
        );

        expect($this->system_event_manager)->createEvent()->count(3);
        $this->repository_deleter->deleteProjectRepositories($this->project);
    }
}
