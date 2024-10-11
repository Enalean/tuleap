<?php
/**
 * Copyright Enalean (c) 2011 - Present. All rights reserved.
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

declare(strict_types=1);

namespace Tuleap\Git;

use Git_SystemEventManager;
use GitRepository;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use SystemEvent;
use SystemEvent_GIT_GERRIT_ADMIN_KEY_DUMP;
use SystemEvent_GIT_GERRIT_MIGRATION;
use SystemEvent_GIT_REPO_DELETE;
use SystemEvent_GIT_REPO_FORK;
use SystemEvent_GIT_REPO_UPDATE;
use SystemEventManager;

final class SystemEventManagerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var SystemEventManager */
    private $system_event_manager;
    /** @var Git_SystemEventManager */
    private $git_system_event_manager;
    /** @var GitRepository */
    private $gitolite_repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->system_event_manager     = \Mockery::spy(\SystemEventManager::class);
        $this->git_system_event_manager = new Git_SystemEventManager($this->system_event_manager);

        $this->gitolite_repository = \Mockery::spy(\GitRepository::class);
        $this->gitolite_repository->shouldReceive('getId')->andReturns(54);
        $this->gitolite_repository->shouldReceive('getProjectId')->andReturns(116);
        $this->gitolite_repository->shouldReceive('getBackend')->andReturns(\Mockery::spy(\Git_Backend_Gitolite::class));
    }

    public function testItCreatesRepositoryUpdateEvent(): void
    {
        $this->system_event_manager->shouldReceive('createEvent')->with(SystemEvent_GIT_REPO_UPDATE::NAME, 54, SystemEvent::PRIORITY_HIGH, SystemEvent::OWNER_APP)->once();

        $this->git_system_event_manager->queueRepositoryUpdate($this->gitolite_repository);
    }

    public function testItCreatesRepositoryDeletionEvent(): void
    {
        $repository = \Mockery::spy(\GitRepository::class);
        $repository->shouldReceive('getId')->andReturns(54);
        $repository->shouldReceive('getProjectId')->andReturns(116);
        $repository->shouldReceive('getBackend')->andReturns(\Mockery::spy(\Git_Backend_Gitolite::class));
        $this->system_event_manager->shouldReceive('createEvent')->with(SystemEvent_GIT_REPO_DELETE::NAME, '116' . SystemEvent::PARAMETER_SEPARATOR . '54', \Mockery::any(), SystemEvent::OWNER_APP)->once();

        $this->git_system_event_manager->queueRepositoryDeletion($repository);
    }

    public function testItCreatesRepositoryForkEvent(): void
    {
        $old_repository = \Mockery::spy(\GitRepository::class)->shouldReceive('getId')->andReturns(554)->getMock();
        $new_repository = \Mockery::spy(\GitRepository::class)->shouldReceive('getId')->andReturns(667)->getMock();

        $this->system_event_manager->shouldReceive('createEvent')->with(SystemEvent_GIT_REPO_FORK::NAME, '554' . SystemEvent::PARAMETER_SEPARATOR . '667', SystemEvent::PRIORITY_MEDIUM, SystemEvent::OWNER_APP)->once();

        $this->git_system_event_manager->queueRepositoryFork($old_repository, $new_repository);
    }

    public function testItCreatesGerritMigrationEvent(): void
    {
        $repository           = \Mockery::spy(\GitRepository::class)->shouldReceive('getId')->andReturns(54)->getMock();
        $remote_server_id     = 3;
        $migrate_access_right = true;
        $requester            = \Mockery::spy(\PFUser::class)->shouldReceive('getId')->andReturns(1001)->getMock();

        $this->system_event_manager->shouldReceive('createEvent')->with(SystemEvent_GIT_GERRIT_MIGRATION::NAME, 54 . SystemEvent::PARAMETER_SEPARATOR . $remote_server_id . SystemEvent::PARAMETER_SEPARATOR . true . SystemEvent::PARAMETER_SEPARATOR . 1001, SystemEvent::PRIORITY_HIGH, SystemEvent::OWNER_APP)->once();

        $this->git_system_event_manager->queueMigrateToGerrit($repository, $remote_server_id, $migrate_access_right, $requester);
    }

    public function testItCreatesGerritReplicationKeyUpdateEvent(): void
    {
        $server = \Mockery::spy(\Git_RemoteServer_GerritServer::class)->shouldReceive('getId')->andReturns(9)->getMock();

        $this->system_event_manager->shouldReceive('createEvent')->with(SystemEvent_GIT_GERRIT_ADMIN_KEY_DUMP::NAME, 9, SystemEvent::PRIORITY_HIGH, SystemEvent::OWNER_APP)->once();

        $this->git_system_event_manager->queueGerritReplicationKeyUpdate($server);
    }
}
