<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\GitReference;

use Tuleap\NeverThrow\Result;
use Tuleap\PullRequest\GitExec;
use Tuleap\PullRequest\PullRequest;
use Tuleap\Test\Stubs\Process\ProcessFactoryStub;
use Tuleap\Test\Stubs\Process\ProcessStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GitPullRequestReferenceCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testPullRequestReferenceIsCreatedDirectlyWhenReferenceIsAvailable(): void
    {
        $dao               = $this->createMock(GitPullRequestReferenceDAO::class);
        $namespace_checker = $this->createMock(GitPullRequestReferenceNamespaceAvailabilityChecker::class);
        $reference_creator = new GitPullRequestReferenceCreator($dao, $namespace_checker, ProcessFactoryStub::withProcess(ProcessStub::successfulProcess()));

        $pull_request           = $this->createMock(PullRequest::class);
        $executor_destination   = $this->createMock(GitExec::class);
        $repository_destination = $this->createMock(\GitRepository::class);
        $repository_source      = $this->createStub(\GitRepository::class);

        $repository_source->method('getFullPath')->willReturn('/source_path');

        $dao->expects($this->once())->method('createGitReferenceForPullRequest')->willReturn(1);
        $dao->expects($this->once())->method('updateStatusByPullRequestId');
        $namespace_checker->method('isAvailable')->willReturn(true);

        $pull_request->method('getId')->willReturn(1);
        $pull_request->method('getRepoDestId')->willReturn(1);
        $pull_request->method('getSha1Src')->willReturn('38762cf7f55934b34d179ae6a4c80cadccbb7f0a');
        $repository_destination->method('getId')->willReturn(1);
        $repository_destination->method('getFullPath')->willReturn('/path');

        $result = $reference_creator->createPullRequestReference($pull_request, $repository_source, $executor_destination, $repository_destination);

        self::assertTrue(Result::isOk($result));
    }

    public function testPullRequestReferenceIsCreatedWithFirstAvailableOneWhenTheInitialOneIsAlreadyTaken(): void
    {
        $dao               = $this->createMock(GitPullRequestReferenceDAO::class);
        $namespace_checker = $this->createMock(GitPullRequestReferenceNamespaceAvailabilityChecker::class);
        $reference_creator = new GitPullRequestReferenceCreator($dao, $namespace_checker, ProcessFactoryStub::withProcess(ProcessStub::successfulProcess()));

        $pull_request           = $this->createMock(PullRequest::class);
        $executor_destination   = $this->createMock(GitExec::class);
        $repository_destination = $this->createMock(\GitRepository::class);
        $repository_source      = $this->createStub(\GitRepository::class);

        $repository_source->method('getFullPath')->willReturn('/source_path');

        $dao->expects($this->once())->method('createGitReferenceForPullRequest')->willReturn(1);
        $dao->expects($this->exactly(2))->method('updateGitReferenceToNextAvailableOne')->willReturn(2, 3);
        $dao->expects($this->once())->method('updateStatusByPullRequestId');
        $namespace_checker->method('isAvailable')->willReturn(false, false, true);

        $pull_request->method('getId')->willReturn(1);
        $pull_request->method('getRepoDestId')->willReturn(1);
        $pull_request->method('getSha1Src')->willReturn('38762cf7f55934b34d179ae6a4c80cadccbb7f0a');
        $repository_destination->method('getId')->willReturn(1);
        $repository_destination->method('getFullPath')->willReturn('/path');

        $reference_creator->createPullRequestReference($pull_request, $repository_source, $executor_destination, $repository_destination);
    }

    public function testExpectedDestinationRepositoryIsGiven(): void
    {
        $dao               = $this->createMock(GitPullRequestReferenceDAO::class);
        $namespace_checker = $this->createMock(GitPullRequestReferenceNamespaceAvailabilityChecker::class);
        $reference_creator = new GitPullRequestReferenceCreator($dao, $namespace_checker, ProcessFactoryStub::withProcess(ProcessStub::successfulProcess()));

        $pull_request           = $this->createMock(PullRequest::class);
        $executor_destination   = $this->createMock(GitExec::class);
        $repository_destination = $this->createMock(\GitRepository::class);
        $repository_source      = $this->createStub(\GitRepository::class);

        $pull_request->method('getRepoDestId')->willReturn(1);
        $repository_destination->method('getId')->willReturn(2);

        $this->expectException(\LogicException::class);

        $reference_creator->createPullRequestReference($pull_request, $repository_source, $executor_destination, $repository_destination);
    }

    public function testGitReferenceIsMarkedAsBrokenWhenItCannotBeCreated(): void
    {
        $dao               = $this->createMock(GitPullRequestReferenceDAO::class);
        $namespace_checker = $this->createMock(GitPullRequestReferenceNamespaceAvailabilityChecker::class);
        $reference_creator = new GitPullRequestReferenceCreator($dao, $namespace_checker, ProcessFactoryStub::withProcess(ProcessStub::failingProcess()));

        $pull_request           = $this->createMock(PullRequest::class);
        $executor_destination   = $this->createMock(GitExec::class);
        $repository_destination = $this->createMock(\GitRepository::class);
        $repository_source      = $this->createStub(\GitRepository::class);

        $repository_source->method('getFullPath')->willReturn('/source_path');

        $dao->expects($this->once())->method('createGitReferenceForPullRequest')->willReturn(1);
        $dao->expects($this->once())->method('updateStatusByPullRequestId')->with(1, GitPullRequestReference::STATUS_BROKEN);
        $namespace_checker->method('isAvailable')->willReturn(true);

        $pull_request->method('getId')->willReturn(1);
        $pull_request->method('getRepoDestId')->willReturn(1);
        $pull_request->method('getSha1Src')->willReturn('38762cf7f55934b34d179ae6a4c80cadccbb7f0a');
        $repository_destination->method('getId')->willReturn(1);
        $repository_destination->method('getFullPath')->willReturn('/path');


        $result = $reference_creator->createPullRequestReference($pull_request, $repository_source, $executor_destination, $repository_destination);
        self::assertTrue(Result::isErr($result));
    }
}
