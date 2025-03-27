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

use Git_Backend_Gitolite;
use Git_RemoteServer_GerritServer;
use Git_SystemEventManager;
use GitRepository;
use PHPUnit\Framework\MockObject\MockObject;
use SystemEvent;
use SystemEvent_GIT_GERRIT_ADMIN_KEY_DUMP;
use SystemEvent_GIT_GERRIT_MIGRATION;
use SystemEvent_GIT_REPO_DELETE;
use SystemEvent_GIT_REPO_FORK;
use SystemEvent_GIT_REPO_UPDATE;
use SystemEventManager;
use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SystemEventManagerTest extends TestCase
{
    private SystemEventManager&MockObject $system_event_manager;
    private Git_SystemEventManager $git_system_event_manager;
    private GitRepository $gitolite_repository;

    protected function setUp(): void
    {
        $this->system_event_manager     = $this->createMock(SystemEventManager::class);
        $this->git_system_event_manager = new Git_SystemEventManager($this->system_event_manager);

        $this->gitolite_repository = GitRepositoryTestBuilder::aProjectRepository()->withId(54)
            ->inProject(ProjectTestBuilder::aProject()->withId(116)->build())
            ->withBackend($this->createMock(Git_Backend_Gitolite::class))
            ->build();
    }

    public function testItCreatesRepositoryUpdateEvent(): void
    {
        $this->system_event_manager->expects($this->once())->method('createEvent')
            ->with(SystemEvent_GIT_REPO_UPDATE::NAME, 54, SystemEvent::PRIORITY_HIGH, SystemEvent::OWNER_APP);
        $this->system_event_manager->method('areThereMultipleEventsQueuedMatchingFirstParameter');

        $this->git_system_event_manager->queueRepositoryUpdate($this->gitolite_repository);
    }

    public function testItCreatesRepositoryDeletionEvent(): void
    {
        $repository = GitRepositoryTestBuilder::aProjectRepository()->withId(54)
            ->inProject(ProjectTestBuilder::aProject()->withId(116)->build())
            ->withBackend($this->createMock(Git_Backend_Gitolite::class))
            ->build();
        $this->system_event_manager->expects($this->once())->method('createEvent')
            ->with(SystemEvent_GIT_REPO_DELETE::NAME, '116' . SystemEvent::PARAMETER_SEPARATOR . '54', self::anything(), SystemEvent::OWNER_APP);

        $this->git_system_event_manager->queueRepositoryDeletion($repository);
    }

    public function testItCreatesRepositoryForkEvent(): void
    {
        $old_repository = GitRepositoryTestBuilder::aProjectRepository()->withId(554)->build();
        $new_repository = GitRepositoryTestBuilder::aProjectRepository()->withId(667)->build();

        $this->system_event_manager->expects($this->once())->method('createEvent')
            ->with(SystemEvent_GIT_REPO_FORK::NAME, '554' . SystemEvent::PARAMETER_SEPARATOR . '667', SystemEvent::PRIORITY_MEDIUM, SystemEvent::OWNER_APP);

        $this->git_system_event_manager->queueRepositoryFork($old_repository, $new_repository);
    }

    public function testItCreatesGerritMigrationEvent(): void
    {
        $repository       = GitRepositoryTestBuilder::aProjectRepository()->withId(54)->build();
        $remote_server_id = 3;
        $requester        = UserTestBuilder::buildWithId(1001);

        $this->system_event_manager->expects($this->once())->method('createEvent')
            ->with(SystemEvent_GIT_GERRIT_MIGRATION::NAME, 54 . SystemEvent::PARAMETER_SEPARATOR . $remote_server_id . SystemEvent::PARAMETER_SEPARATOR . true . SystemEvent::PARAMETER_SEPARATOR . 1001, SystemEvent::PRIORITY_HIGH, SystemEvent::OWNER_APP);

        $this->git_system_event_manager->queueMigrateToGerrit($repository, $remote_server_id, true, $requester);
    }

    public function testItCreatesGerritReplicationKeyUpdateEvent(): void
    {
        $server = $this->createMock(Git_RemoteServer_GerritServer::class);
        $server->method('getId')->willReturn(9);

        $this->system_event_manager->expects($this->once())->method('createEvent')
            ->with(SystemEvent_GIT_GERRIT_ADMIN_KEY_DUMP::NAME, 9, SystemEvent::PRIORITY_HIGH, SystemEvent::OWNER_APP);

        $this->git_system_event_manager->queueGerritReplicationKeyUpdate($server);
    }
}
