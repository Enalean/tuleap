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

declare(strict_types=1);

namespace Tuleap\Git;

use EventManager;
use Git_Backend_Gitolite;
use Git_SystemEventManager;
use GitDao;
use GitRepository;
use GitRepositoryFactory;
use GitRepositoryManager;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use ProjectHistoryDao;
use Tuleap\Git\Permissions\FineGrainedPermissionReplicator;
use Tuleap\Git\Permissions\HistoryValueFormatter;
use Tuleap\Git\SystemEvent\OngoingDeletionDAO;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GitRepositoryManagerDeleteAllRepositoriesTest extends TestCase
{
    private Project $project;
    private GitRepositoryManager $git_repository_manager;
    private Git_SystemEventManager&MockObject $git_system_event_manager;
    private GitRepositoryFactory&MockObject $repository_factory;

    protected function setUp(): void
    {
        $this->project                  = ProjectTestBuilder::aProject()->withId(42)->build();
        $this->repository_factory       = $this->createMock(GitRepositoryFactory::class);
        $this->git_system_event_manager = $this->createMock(Git_SystemEventManager::class);

        $this->git_repository_manager = new GitRepositoryManager(
            $this->repository_factory,
            $this->git_system_event_manager,
            $this->createMock(GitDao::class),
            vfsStream::setup()->url(),
            $this->createMock(FineGrainedPermissionReplicator::class),
            $this->createMock(ProjectHistoryDao::class),
            $this->createMock(HistoryValueFormatter::class),
            $this->createMock(EventManager::class),
            $this->createMock(OngoingDeletionDAO::class),
        );
    }

    public function testItDeletesNothingWhenThereAreNoRepositories(): void
    {
        $this->repository_factory->expects($this->once())->method('getAllRepositories')
            ->with($this->project)->willReturn([]);

        $this->git_repository_manager->deleteProjectRepositories($this->project);
    }

    public function testItDeletesEachRepository(): void
    {
        $backend         = $this->createMock(Git_Backend_Gitolite::class);
        $repository_1_id = 1;
        $repository_1    = $this->createMock(GitRepository::class);
        $repository_1->expects($this->once())->method('forceMarkAsDeleted');
        $repository_1->method('getId')->willReturn($repository_1_id);
        $repository_1->method('getProjectId')->willReturn($this->project);
        $repository_1->method('getBackend')->willReturn($backend);

        $repository_2_id = 2;
        $repository_2    = $this->createMock(GitRepository::class);
        $repository_2->expects($this->once())->method('forceMarkAsDeleted');
        $repository_2->method('getId')->willReturn($repository_2_id);
        $repository_2->method('getProjectId')->willReturn($this->project);
        $repository_2->method('getBackend')->willReturn($backend);
        $matcher = self::exactly(2);

        $this->git_system_event_manager->expects($matcher)->method('queueRepositoryDeletion')->willReturnCallback(function (...$parameters) use ($matcher, $repository_1, $repository_2) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame($repository_1, $parameters[0]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame($repository_2, $parameters[0]);
            }
        });

        $this->repository_factory->method('getAllRepositories')->willReturn([$repository_1, $repository_2]);

        $this->git_repository_manager->deleteProjectRepositories($this->project);
    }
}
