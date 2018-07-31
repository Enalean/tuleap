<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once dirname(__FILE__).'/../bootstrap.php';

class SystemEvent_GIT_REPO_DELETETest extends TuleapTestCase {
    private $project_id;
    private $repository_id;
    private $repository;
    private $repository_factory;
    private $system_event_manager;

    /** @var SystemEvent_GIT_REPO_DELETE */
    private $event;

    public function setUp() {
        parent::setUp();

        $this->project_id    = 101;
        $this->repository_id = 69;

        $this->repository = mock('GitRepository');
        stub($this->repository)->getId()->returns($this->repository_id);
        stub($this->repository)->getProjectId()->returns($this->project_id);

        $this->repository_factory = mock('GitRepositoryFactory');
        stub($this->repository_factory)->getDeletedRepository($this->repository_id)->returns($this->repository);

        $this->system_event_manager  = mock('Git_SystemEventManager');
        $this->ugroups_to_notify_dao = mock('Tuleap\Git\Notifications\UgroupsToNotifyDao');
        $this->users_to_notify_dao   = mock('Tuleap\Git\Notifications\UsersToNotifyDao');
        $this->event_manager         = mock('EventManager');

        $this->event = partial_mock('SystemEvent_GIT_REPO_DELETE', array('done', 'warning', 'error', 'getId'));
        $this->event->setParameters($this->project_id . SystemEvent::PARAMETER_SEPARATOR . $this->repository_id);
        $this->event->injectDependencies(
            $this->repository_factory,
            mock('Logger'),
            $this->system_event_manager,
            $this->ugroups_to_notify_dao,
            $this->users_to_notify_dao,
            $this->event_manager
        );
    }

    public function itDeletesTheRepository() {
        expect($this->repository)->delete()->once();

        $this->event->process();
    }

    public function itDeletesNotifications()
    {
        expect($this->ugroups_to_notify_dao)->deleteByRepositoryId(69)->once();
        expect($this->users_to_notify_dao)->deleteByRepositoryId(69)->once();

        $this->event->process();
    }

    public function itAsksToDeleteRepositoryFromManifestFiles() {
        expect($this->system_event_manager)->queueGrokMirrorManifestRepoDelete($this->repository->getPath())->once();

        $this->event->process();
    }

    public function itLaunchesAnEventToLetOthersDeleteStuffLinkedToTheRepository()
    {
        expect($this->event_manager)
            ->processEvent(new IsAExpectation('Tuleap\Git\GitRepositoryDeletionEvent'))
            ->once();

        $this->event->process();
    }
}
