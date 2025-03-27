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

namespace Tuleap\Git\SystemEvents;

use Git_Backend_Gitolite;
use GitRepository;
use GitRepositoryFactory;
use PHPUnit\Framework\MockObject\MockObject;
use SystemEvent;
use SystemEvent_GIT_REPO_FORK;
use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SystemEvent_GIT_REPO_FORKTest extends TestCase
{
    private GitRepository&MockObject $old_repository;
    private GitRepository $new_repository;
    private int $old_repository_id = 115;
    private int $new_repository_id = 123;
    private Git_Backend_Gitolite&MockObject $backend;
    private SystemEvent_GIT_REPO_FORK&MockObject $event;
    private GitRepositoryFactory&MockObject $repository_factory;

    protected function setUp(): void
    {
        $this->backend = $this->createMock(Git_Backend_Gitolite::class);

        $this->old_repository = $this->createMock(GitRepository::class);
        $this->old_repository->method('getBackend')->willReturn($this->backend);

        $this->new_repository     = GitRepositoryTestBuilder::aProjectRepository()->build();
        $this->repository_factory = $this->createMock(GitRepositoryFactory::class);

        $this->event = $this->createPartialMock(SystemEvent_GIT_REPO_FORK::class, ['done', 'warning']);
        $this->event->setParameters($this->old_repository_id . SystemEvent::PARAMETER_SEPARATOR . $this->new_repository_id);
        $this->event->injectDependencies($this->repository_factory);
    }

    public function testItDelegatesToBackendRepositoryCreation(): void
    {
        $this->repository_factory->method('getRepositoryById')->willReturnCallback(fn(int $id) => match ($id) {
            $this->old_repository_id => $this->old_repository,
            $this->new_repository_id => $this->new_repository,
        });
        $this->backend->expects($this->once())->method('forkOnFilesystem')->with(self::anything(), $this->new_repository);
        $this->event->expects($this->once())->method('done');
        $this->event->process();
    }

    public function testItMarksTheEventAsWarningWhenTheRepoDoesNotExist(): void
    {
        $this->repository_factory->method('getRepositoryById')->willReturn(null);
        $this->event->expects($this->once())->method('warning')->with('Unable to find repository, perhaps it was deleted in the mean time?');
        $this->event->process();
    }
}
