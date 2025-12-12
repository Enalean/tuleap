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
final class GitPullRequestReferenceUpdaterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testGitReferenceIsUpdated(): void
    {
        $dao               = $this->createMock(GitPullRequestReferenceDAO::class);
        $namespace_checker = $this->createMock(GitPullRequestReferenceNamespaceAvailabilityChecker::class);

        $reference_updater = new GitPullRequestReferenceUpdater($dao, $namespace_checker, ProcessFactoryStub::withProcess(ProcessStub::successfulProcess()));

        $pull_request           = $this->createMock(PullRequest::class);
        $executor_destination   = $this->createMock(GitExec::class);
        $repository_destination = $this->createMock(\GitRepository::class);
        $repository_source      = $this->createStub(\GitRepository::class);

        $repository_source->method('getFullPath')->willReturn('/source_path');

        $pull_request->method('getId')->willReturn(1);
        $pull_request->method('getSha1Src')->willReturn('38762cf7f55934b34d179ae6a4c80cadccbb7f0a');
        $pull_request->method('getRepoDestId')->willReturn(1);
        $repository_destination->method('getId')->willReturn(1);
        $repository_destination->method('getFullPath')->willReturn('/dest_path');
        $dao->method('getReferenceByPullRequestId')->willReturn(
            ['pr_id' => 1, 'reference_id' => 1, 'repository_dest_id' => 1, 'status' => GitPullRequestReference::STATUS_OK]
        );
        $dao->expects($this->once())->method('updateStatusByPullRequestId');

        $result = $reference_updater->updatePullRequestReference(
            $pull_request,
            $repository_source,
            $executor_destination,
            $repository_destination
        );

        self::assertTrue(Result::isOk($result));
    }

    public function testGitReferenceUpdateWhenTheReferenceIsNotFound(): void
    {
        $dao               = $this->createMock(GitPullRequestReferenceDAO::class);
        $namespace_checker = $this->createMock(GitPullRequestReferenceNamespaceAvailabilityChecker::class);

        $reference_updater = new GitPullRequestReferenceUpdater($dao, $namespace_checker, ProcessFactoryStub::withProcess(ProcessStub::successfulProcess()));

        $pull_request           = $this->createMock(PullRequest::class);
        $executor_destination   = $this->createMock(GitExec::class);
        $repository_destination = $this->createMock(\GitRepository::class);
        $repository_source      = $this->createStub(\GitRepository::class);


        $pull_request->method('getId')->willReturn(1);
        $pull_request->method('getRepoDestId')->willReturn(1);
        $repository_destination->method('getId')->willReturn(1);
        $dao->method('getReferenceByPullRequestId')->willReturn([]);

        $dao->expects($this->never())->method('updateStatusByPullRequestId');
        $this->expectException(GitReferenceNotFound::class);

        $result = $reference_updater->updatePullRequestReference(
            $pull_request,
            $repository_source,
            $executor_destination,
            $repository_destination
        );

        self::assertTrue(Result::isOk($result));
    }

    public function testExpectedDestinationRepositoryIsGiven(): void
    {
        $dao               = $this->createMock(GitPullRequestReferenceDAO::class);
        $namespace_checker = $this->createMock(GitPullRequestReferenceNamespaceAvailabilityChecker::class);

        $reference_updater = new GitPullRequestReferenceUpdater($dao, $namespace_checker, ProcessFactoryStub::withProcess(ProcessStub::successfulProcess()));

        $pull_request           = $this->createMock(PullRequest::class);
        $executor_destination   = $this->createMock(GitExec::class);
        $repository_destination = $this->createMock(\GitRepository::class);
        $repository_source      = $this->createStub(\GitRepository::class);

        $pull_request->method('getRepoDestId')->willReturn(1);
        $repository_destination->method('getId')->willReturn(2);

        $this->expectException(\LogicException::class);

        $reference_updater->updatePullRequestReference(
            $pull_request,
            $repository_source,
            $executor_destination,
            $repository_destination
        );
    }

    public function testGitReferenceUpdateIsIgnoredWhenPullRequestIsKnownAsBroken(): void
    {
        $dao               = $this->createMock(GitPullRequestReferenceDAO::class);
        $namespace_checker = $this->createMock(GitPullRequestReferenceNamespaceAvailabilityChecker::class);

        $reference_updater = new GitPullRequestReferenceUpdater($dao, $namespace_checker, ProcessFactoryStub::withProcess(ProcessStub::successfulProcess()));

        $pull_request           = $this->createMock(PullRequest::class);
        $executor_destination   = $this->createMock(GitExec::class);
        $repository_destination = $this->createMock(\GitRepository::class);
        $repository_source      = $this->createStub(\GitRepository::class);

        $pull_request->method('getId')->willReturn(1);
        $pull_request->method('getSha1Src')->willReturn('38762cf7f55934b34d179ae6a4c80cadccbb7f0a');
        $pull_request->method('getRepoDestId')->willReturn(1);
        $repository_destination->method('getId')->willReturn(1);
        $dao->method('getReferenceByPullRequestId')->willReturn(
            ['pr_id' => 1, 'reference_id' => 1, 'repository_dest_id' => 1, 'status' => GitPullRequestReference::STATUS_BROKEN]
        );

        $dao->expects($this->never())->method('updateStatusByPullRequestId');

        $result = $reference_updater->updatePullRequestReference(
            $pull_request,
            $repository_source,
            $executor_destination,
            $repository_destination
        );

        self::assertTrue(Result::isOk($result));
    }

    public function testGitReferenceIsMarkedAsBrokenWhenCannotBeUpdated(): void
    {
        $dao               = $this->createMock(GitPullRequestReferenceDAO::class);
        $namespace_checker = $this->createMock(GitPullRequestReferenceNamespaceAvailabilityChecker::class);

        $reference_updater = new GitPullRequestReferenceUpdater($dao, $namespace_checker, ProcessFactoryStub::withProcess(ProcessStub::failingProcess()));

        $pull_request           = $this->createMock(PullRequest::class);
        $executor_destination   = $this->createMock(GitExec::class);
        $repository_destination = $this->createMock(\GitRepository::class);
        $repository_source      = $this->createStub(\GitRepository::class);

        $repository_source->method('getFullPath')->willReturn('/source_path');

        $pull_request->method('getId')->willReturn(1);
        $pull_request->method('getSha1Src')->willReturn('38762cf7f55934b34d179ae6a4c80cadccbb7f0a');
        $pull_request->method('getRepoDestId')->willReturn(1);
        $repository_destination->method('getId')->willReturn(1);
        $repository_destination->method('getFullPath')->willReturn('/dest_path');
        $dao->method('getReferenceByPullRequestId')->willReturn(
            ['pr_id' => 1, 'reference_id' => 1, 'repository_dest_id' => 1, 'status' => GitPullRequestReference::STATUS_NOT_YET_CREATED]
        );
        $namespace_checker->method('isAvailable')->willReturn(true);
        $dao->expects($this->once())->method('updateStatusByPullRequestId')->with($pull_request->getId(), GitPullRequestReference::STATUS_BROKEN);

        $result = $reference_updater->updatePullRequestReference(
            $pull_request,
            $repository_source,
            $executor_destination,
            $repository_destination
        );
        self::assertTrue(Result::isErr($result));
    }

    public function testGitReferenceIdIsUpdatedForYetToBeCreatedReferenceWhenNamespaceIsNotAvailable(): void
    {
        $dao               = $this->createMock(GitPullRequestReferenceDAO::class);
        $namespace_checker = $this->createMock(GitPullRequestReferenceNamespaceAvailabilityChecker::class);

        $reference_updater = new GitPullRequestReferenceUpdater($dao, $namespace_checker, ProcessFactoryStub::withProcess(ProcessStub::successfulProcess()));

        $pull_request           = $this->createMock(PullRequest::class);
        $executor_destination   = $this->createMock(GitExec::class);
        $repository_destination = $this->createMock(\GitRepository::class);
        $repository_source      = $this->createStub(\GitRepository::class);

        $repository_source->method('getFullPath')->willReturn('/source_path');

        $pull_request->method('getId')->willReturn(1);
        $pull_request->method('getSha1Src')->willReturn('38762cf7f55934b34d179ae6a4c80cadccbb7f0a');
        $pull_request->method('getRepoDestId')->willReturn(1);
        $repository_destination->method('getId')->willReturn(1);
        $repository_destination->method('getFullPath')->willReturn('/dest_path');
        $dao->method('getReferenceByPullRequestId')->willReturn(
            ['pr_id' => 1, 'reference_id' => 1, 'repository_dest_id' => 1, 'status' => GitPullRequestReference::STATUS_NOT_YET_CREATED]
        );
        $namespace_checker->method('isAvailable')->willReturn(false, true);
        $dao->expects($this->once())->method('updateGitReferenceToNextAvailableOne');
        $dao->expects($this->once())->method('updateStatusByPullRequestId')->with($pull_request->getId(), GitPullRequestReference::STATUS_OK);

        $result = $reference_updater->updatePullRequestReference(
            $pull_request,
            $repository_source,
            $executor_destination,
            $repository_destination
        );
        self::assertTrue(Result::isOk($result));
    }
}
