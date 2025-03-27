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
use Git_GitoliteDriver;
use GitRepository;
use GitRepositoryFactory;
use PHPUnit\Framework\MockObject\MockObject;
use SystemEvent;
use SystemEvent_GIT_REPO_UPDATE;
use Tuleap\Git\DefaultBranch\CannotExecuteDefaultBranchUpdateException;
use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\Git\Tests\Stub\DefaultBranch\DefaultBranchUpdateExecutorStub;
use Tuleap\Test\PHPUnit\TestCase;

//phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SystemEvent_GIT_REPO_UPDATETest extends TestCase
{
    private int $repository_id = 115;
    private DefaultBranchUpdateExecutorStub $default_branch_update_executor;
    private Git_Backend_Gitolite&MockObject $backend;
    private GitRepository $repository;
    private GitRepositoryFactory&MockObject $repository_factory;
    private SystemEvent_GIT_REPO_UPDATE&MockObject $event;

    protected function setUp(): void
    {
        $this->backend = $this->createMock(Git_Backend_Gitolite::class);

        $this->repository = GitRepositoryTestBuilder::aProjectRepository()->withBackend($this->backend)->build();

        $this->repository_factory             = $this->createMock(GitRepositoryFactory::class);
        $this->default_branch_update_executor = new DefaultBranchUpdateExecutorStub();

        $this->event = $this->createPartialMock(SystemEvent_GIT_REPO_UPDATE::class, ['done', 'warning', 'error']);
        $this->event->setParameters("$this->repository_id");
        $this->event->injectDependencies($this->repository_factory, $this->default_branch_update_executor);
    }

    public function testItGetsTheRepositoryFromTheFactory(): void
    {
        $this->repository_factory->expects($this->once())->method('getRepositoryById')
            ->with($this->repository_id)
            ->willReturn($this->repository);
        $this->backend->method('updateRepoConf');
        $this->event->method('error');

        $this->event->process();
    }

    public function testItDelegatesToBackendRepositoryCreation(): void
    {
        $this->repository_factory->method('getRepositoryById')->willReturn($this->repository);
        $this->backend->expects($this->once())->method('updateRepoConf');
        $this->event->method('error');
        $this->event->process();
    }

    public function testItMarksTheEventAsDone(): void
    {
        $this->repository_factory->method('getRepositoryById')->willReturn($this->repository);
        $this->backend->expects($this->once())->method('updateRepoConf')->willReturn(true);
        $this->event->expects($this->once())->method('done');
        $this->event->process();
    }

    public function testItMarksTheEventAsWarningWhenTheRepoDoesNotExist(): void
    {
        $this->repository_factory->method('getRepositoryById')->willReturn(null);
        $this->repository_factory->method('getDeletedRepository');
        $this->event->expects($this->once())->method('warning')->with('Unable to find repository, perhaps it was deleted in the mean time?');
        $this->event->process();
    }

    public function testItMarksTheEventAsDoneWhenTheRepoIsFlaggedAsDeleted(): void
    {
        $this->repository_factory->method('getRepositoryById')->willReturn(null);
        $this->repository_factory->method('getDeletedRepository')->willReturn($this->repository);

        $this->event->expects($this->once())->method('done')->with('Unable to update a repository marked as deleted');

        $this->event->process();
    }

    public function testDefaultBranchIsSet(): void
    {
        $this->event->setParameters($this->repository_id . SystemEvent::PARAMETER_SEPARATOR . 'main');
        $this->repository_factory->method('getRepositoryById')->willReturn($this->repository);
        $driver = $this->createStub(Git_GitoliteDriver::class);
        $driver->method('commit');
        $driver->method('push');
        $this->backend->method('getDriver')->willReturn($driver);
        $this->backend->method('updateRepoConf')->willReturn(true);
        $this->backend->method('getGitRootPath');
        $this->event->expects($this->once())->method('done');

        $this->event->process();

        self::assertTrue($this->default_branch_update_executor->doesADefaultBranchBeenSet());
    }

    public function testSystemEventIsMarkedAsFailedWhenDefaultBranchCannotBeSet(): void
    {
        $this->event->setParameters($this->repository_id . SystemEvent::PARAMETER_SEPARATOR . 'main');
        $this->repository_factory->method('getRepositoryById')->willReturn($this->repository);
        $driver = $this->createStub(Git_GitoliteDriver::class);
        $driver->method('commit');
        $driver->method('push');
        $this->backend->method('getDriver')->willReturn($driver);
        $this->backend->method('updateRepoConf')->willReturn(true);
        $this->backend->method('getGitRootPath');
        $this->default_branch_update_executor->setCallbackOnSetDefaultBranch(
            static fn() => throw new CannotExecuteDefaultBranchUpdateException('Something wrong happened')
        );

        $this->event->expects($this->once())->method('error');

        $this->event->process();
    }
}
