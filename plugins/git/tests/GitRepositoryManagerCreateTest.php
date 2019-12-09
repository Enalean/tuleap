<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

require_once __DIR__.'/bootstrap.php';

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class GitRepositoryManagerCreateTest extends TuleapTestCase
{

    private $creator;
    private $dao;
    private $git_system_event_manager;
    private $backup_directory;

    public function setUp()
    {
        parent::setUp();
        $this->creator    = mock('GitRepositoryCreator');
        $this->repository = new GitRepository();

        $this->git_system_event_manager = mock('Git_SystemEventManager');
        $this->dao                      = safe_mock(GitDao::class);
        $this->backup_directory         = "/tmp/";
        $this->mirror_updater           = mock('GitRepositoryMirrorUpdater');
        $this->mirror_data_mapper       = mock('Git_Mirror_MirrorDataMapper');

        $this->manager = partial_mock(
            'GitRepositoryManager',
            array('isRepositoryNameAlreadyUsed'),
            array(
                mock('GitRepositoryFactory'),
                $this->git_system_event_manager,
                $this->dao,
                $this->backup_directory,
                $this->mirror_updater,
                $this->mirror_data_mapper,
                mock('Tuleap\Git\Permissions\FineGrainedPermissionReplicator'),
                mock('ProjectHistoryDao'),
                mock('Tuleap\Git\Permissions\HistoryValueFormatter'),
                mock(EventManager::class)
            )
        );
    }

    public function itThrowAnExceptionIfRepositoryNameCannotBeUsed()
    {
        stub($this->manager)->isRepositoryNameAlreadyUsed($this->repository)->returns(true);
        stub($this->creator)->isNameValid()->returns(true);

        $this->expectException();
        $this->manager->create($this->repository, $this->creator, array());
    }

    public function itThrowsAnExceptionIfNameIsNotCompliantToBackendStandards()
    {
        stub($this->manager)->isRepositoryNameAlreadyUsed($this->repository)->returns(false);
        stub($this->creator)->isNameValid()->returns(false);

        $this->expectException();
        $this->manager->create($this->repository, $this->creator, array());
    }

    public function itCreatesOnRepositoryBackendIfEverythingIsClean()
    {
        stub($this->manager)->isRepositoryNameAlreadyUsed($this->repository)->returns(false);
        stub($this->creator)->isNameValid()->returns(true);

        expect($this->dao)->save($this->repository)->once();
        $this->manager->create($this->repository, $this->creator, array());
    }

    public function itScheduleAnEventToCreateTheRepositoryInGitolite()
    {
        stub($this->manager)->isRepositoryNameAlreadyUsed($this->repository)->returns(false);
        stub($this->creator)->isNameValid()->returns(true);

        stub($this->dao)->save()->returns(54);

        expect($this->git_system_event_manager)->queueRepositoryUpdate($this->repository)->once();

        $this->manager->create($this->repository, $this->creator, array());
    }

    public function itSetRepositoryIdOnceSavedInDatabase()
    {
        stub($this->manager)->isRepositoryNameAlreadyUsed($this->repository)->returns(false);
        stub($this->creator)->isNameValid()->returns(true);

        stub($this->dao)->save()->returns(54);

        $this->manager->create($this->repository, $this->creator, array());
        $this->assertEqual($this->repository->getId(), 54);
    }
}
