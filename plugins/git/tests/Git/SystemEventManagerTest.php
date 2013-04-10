<?php
/**
 * Copyright Enalean (c) 2011, 2012, 2013. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

require_once dirname(__FILE__).'/../bootstrap.php';

class Git_SystemEventManagerTest extends TuleapTestCase {
    /** @var SystemEventManager */
    private $system_event_manager;
    /** @var Git_SystemEventManager */
    private $git_system_event_manager;

    public function setUp() {
        parent::setUp();
        $this->system_event_manager = mock('SystemEventManager');
        $this->git_system_event_manager = new Git_SystemEventManager($this->system_event_manager);
    }

    public function itCreatesRepositoryUpdateEvent() {
        $repository = stub('GitRepository')->getId()->returns(54);

        expect($this->system_event_manager)->createEvent(
            SystemEvent_GIT_REPO_UPDATE::NAME,
            54,
            SystemEvent::PRIORITY_HIGH,
            SystemEvent::OWNER_APP
        )->once();

        $this->git_system_event_manager->queueRepositoryUpdate($repository);
    }

    public function itCreatesRepositoryDeletionEvent() {
        $repository = mock('GitRepository');
        stub($repository)->getId()->returns(54);
        stub($repository)->getProjectId()->returns(116);
        stub($repository)->getBackend()->returns(mock('Git_Backend_Gitolite'));
        expect($this->system_event_manager)->createEvent(
            SystemEvent_GIT_REPO_DELETE::NAME,
            "116".SystemEvent::PARAMETER_SEPARATOR."54",
            '*',
            SystemEvent::OWNER_APP
        )->once();

        $this->git_system_event_manager->queueRepositoryDeletion($repository);
    }

    public function itCreatesRepositoryDeletionEventForRootWhenRepositoryIsGitShell() {
        $repository = mock('GitRepository');
        stub($repository)->getId()->returns(54);
        stub($repository)->getProjectId()->returns(116);
        stub($repository)->getBackend()->returns(mock('GitBackend'));
        expect($this->system_event_manager)->createEvent(
            SystemEvent_GIT_REPO_DELETE::NAME,
            "116".SystemEvent::PARAMETER_SEPARATOR."54",
            '*',
            SystemEvent::OWNER_ROOT
        )->once();

        $this->git_system_event_manager->queueRepositoryDeletion($repository);
    }

    public function itCreatesRepositoryForkEvent() {
        $old_repository = stub('GitRepository')->getId()->returns(554);
        $new_repository = stub('GitRepository')->getId()->returns(667);

        expect($this->system_event_manager)->createEvent(
            SystemEvent_GIT_REPO_FORK::NAME,
            "554".SystemEvent::PARAMETER_SEPARATOR."667",
            SystemEvent::PRIORITY_MEDIUM,
            SystemEvent::OWNER_APP
        )->once();

        $this->git_system_event_manager->queueRepositoryFork($old_repository, $new_repository);
    }

    public function itCreatesRepositoryAccessEvent() {
        $repository = stub('GitRepository')->getId()->returns(54);

        expect($this->system_event_manager)->createEvent(
            SystemEvent_GIT_REPO_ACCESS::NAME,
            "54" . SystemEvent::PARAMETER_SEPARATOR . "private",
            SystemEvent::PRIORITY_HIGH
        )->once();

        $this->git_system_event_manager->queueGitShellAccess($repository, 'private');
    }

    public function itCreatesGerritMigrationEvent() {
        $repository       = stub('GitRepository')->getId()->returns(54);
        $remote_server_id = 3;

        expect($this->system_event_manager)->createEvent(
            SystemEvent_GIT_GERRIT_MIGRATION::TYPE,
            54 . SystemEvent::PARAMETER_SEPARATOR . $remote_server_id,
            SystemEvent::PRIORITY_HIGH,
            SystemEvent::OWNER_APP
        )->once();

        $this->git_system_event_manager->queueMigrateToGerrit($repository, $remote_server_id);
    }
}

?>
