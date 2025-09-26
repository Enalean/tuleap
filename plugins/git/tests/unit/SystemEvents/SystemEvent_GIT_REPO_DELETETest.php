<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Git\SystemEvents;

use EventManager;
use GitRepository;
use GitRepositoryFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use SystemEvent;
use SystemEvent_GIT_REPO_DELETE;
use Tuleap\Git\GitRepositoryDeletionEvent;
use Tuleap\Git\Notifications\UgroupsToNotifyDao;
use Tuleap\Git\Notifications\UsersToNotifyDao;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SystemEvent_GIT_REPO_DELETETest extends TestCase // phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    private GitRepository&MockObject $repository;
    private UgroupsToNotifyDao&MockObject $ugroups_to_notify_dao;
    private UsersToNotifyDao&MockObject $users_to_notify_dao;
    private SystemEvent_GIT_REPO_DELETE&MockObject $event;
    private EventManager&MockObject $event_manager;

    #[\Override]
    protected function setUp(): void
    {
        $project_id    = 101;
        $repository_id = 69;

        $this->repository = $this->createMock(GitRepository::class);
        $this->repository->method('getId')->willReturn($repository_id);
        $this->repository->method('getProjectId')->willReturn($project_id);
        $this->repository->method('getPath');

        $repository_factory = $this->createMock(GitRepositoryFactory::class);
        $repository_factory->method('getDeletedRepository')->with($repository_id)->willReturn($this->repository);

        $this->ugroups_to_notify_dao = $this->createMock(UgroupsToNotifyDao::class);
        $this->users_to_notify_dao   = $this->createMock(UsersToNotifyDao::class);
        $this->event_manager         = $this->createMock(EventManager::class);

        $this->event = $this->createPartialMock(SystemEvent_GIT_REPO_DELETE::class, []);
        $this->event->setParameters($project_id . SystemEvent::PARAMETER_SEPARATOR . $repository_id);
        $this->event->injectDependencies(
            $repository_factory,
            new NullLogger(),
            $this->ugroups_to_notify_dao,
            $this->users_to_notify_dao,
            $this->event_manager
        );
    }

    public function testItDeletesTheRepository(): void
    {
        $this->ugroups_to_notify_dao->expects($this->once())->method('deleteByRepositoryId')->with(69);
        $this->users_to_notify_dao->expects($this->once())->method('deleteByRepositoryId')->with(69);
        $this->event_manager->expects($this->atLeastOnce())->method('processEvent')->with(self::isInstanceOf(GitRepositoryDeletionEvent::class));
        $this->repository->expects($this->once())->method('delete');

        $this->event->process();
    }
}
