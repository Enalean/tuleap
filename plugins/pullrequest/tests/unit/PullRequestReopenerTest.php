<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\PullRequest;

use GitRepository;
use GitRepositoryFactory;
use Tuleap\PullRequest\Exception\PullRequestCannotBeReopen;
use Tuleap\PullRequest\Exception\UnknownBranchNameException;
use Tuleap\PullRequest\GitReference\GitReferenceNotFound;
use Tuleap\PullRequest\PullRequest\Timeline\TimelineComment;
use Tuleap\PullRequest\Timeline\TimelineEventCreator;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class PullRequestReopenerTest extends TestCase
{
    private PullRequestReopener $reopener;
    /**
     * @var Dao&\PHPUnit\Framework\MockObject\MockObject
     */
    private $dao;
    /**
     * @var GitRepositoryFactory&\PHPUnit\Framework\MockObject\MockObject
     */
    private $repository_factory;
    /**
     * @var GitExecFactory&\PHPUnit\Framework\MockObject\MockObject
     */
    private $git_exec_factory;
    /**
     * @var PullRequestUpdater&\PHPUnit\Framework\MockObject\MockObject
     */
    private $pull_request_updater;
    /**
     * @var TimelineEventCreator&\PHPUnit\Framework\MockObject\MockObject
     */
    private TimelineEventCreator|\PHPUnit\Framework\MockObject\MockObject $timeline_event_creator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dao                    = $this->createMock(Dao::class);
        $this->repository_factory     = $this->createMock(GitRepositoryFactory::class);
        $this->git_exec_factory       = $this->createMock(GitExecFactory::class);
        $this->pull_request_updater   = $this->createMock(PullRequestUpdater::class);
        $this->timeline_event_creator = $this->createMock(TimelineEventCreator::class);

        $this->reopener = new PullRequestReopener(
            $this->dao,
            $this->repository_factory,
            $this->git_exec_factory,
            $this->pull_request_updater,
            $this->timeline_event_creator,
        );
    }

    public function testItThrowsAnExceptionIfPullRequestStatusIsReview(): void
    {
        $this->expectException(PullRequestCannotBeReopen::class);

        $this->reopener->reopen(
            $this->buildAnOpenPullRequest(),
            UserTestBuilder::aUser()->build(),
        );
    }

    public function testItThrowsAnExceptionIfPullRequestIsAlreadyMerged(): void
    {
        $this->expectException(PullRequestCannotBeReopen::class);

        $this->reopener->reopen(
            $this->buildAMergedPullRequest(),
            UserTestBuilder::aUser()->build(),
        );
    }

    public function testItThrowsAnExceptionIfSourceRepositoryNotFound(): void
    {
        $this->expectException(PullRequestCannotBeReopen::class);

        $this->repository_factory->method('getRepositoryById')->with(2)->willReturn(null);

        $this->reopener->reopen(
            $this->buildAnAbandonedPullRequest(),
            UserTestBuilder::aUser()->build(),
        );
    }

    public function testItThrowsAnExceptionIfDestinationRepositoryNotFound(): void
    {
        $this->expectException(PullRequestCannotBeReopen::class);

        $this->repository_factory->method('getRepositoryById')->willReturnMap([
            [2, $this->buildSourceRepository()],
            [1, null],
        ]);

        $this->git_exec_factory->method("getGitExec")->willReturn($this->createMock(GitExec::class));

        $this->reopener->reopen(
            $this->buildAnAbandonedPullRequest(),
            UserTestBuilder::aUser()->build(),
        );
    }

    public function testItReopensWithoutUpdatingThePullRequest(): void
    {
        $source_repository      = $this->buildSourceRepository();
        $destination_repository = $this->buildDestinationRepository();
        $this->repository_factory->method('getRepositoryById')->willReturnMap([
            [2, $source_repository],
            [1, $destination_repository],
        ]);

        $source_git_exec      = $this->createMock(GitExec::class);
        $destination_git_exec = $this->createMock(GitExec::class);
        $this->git_exec_factory->method("getGitExec")->willReturnMap([
            [$source_repository, $source_git_exec],
            [$destination_repository, $destination_git_exec],
        ]);

        $source_git_exec->method('getBranchSha1')->with('fork01')->willReturn('0000000000000000000000000000000000000000');
        $destination_git_exec->method('getBranchSha1')->with('main')->willReturn('0000000000000000000000000000000000000000');

        $this->pull_request_updater->expects(self::never())->method('updatePullRequestWithNewSourceRev');
        $this->dao->expects(self::once())->method('reopen');
        $this->timeline_event_creator->expects(self::once())->method('storeReopenEvent');

        $this->reopener->reopen(
            $this->buildAnAbandonedPullRequest(),
            UserTestBuilder::aUser()->build(),
        );
    }

    public function testItThrowsAnExceptionIfThePullRequestSourceBranchDoesNotExistAnymore(): void
    {
        $this->expectException(PullRequestCannotBeReopen::class);

        $source_repository      = $this->buildSourceRepository();
        $destination_repository = $this->buildDestinationRepository();
        $this->repository_factory->method('getRepositoryById')->willReturnMap([
            [2, $source_repository],
            [1, $destination_repository],
        ]);

        $source_git_exec      = $this->createMock(GitExec::class);
        $destination_git_exec = $this->createMock(GitExec::class);
        $this->git_exec_factory->method("getGitExec")->willReturnMap([
            [$source_repository, $source_git_exec],
            [$destination_repository, $destination_git_exec],
        ]);

        $source_git_exec->method('getBranchSha1')->with('fork01')->willThrowException(new UnknownBranchNameException('fork01'));

        $this->pull_request_updater->expects(self::never())->method('updatePullRequestWithNewSourceRev');
        $this->dao->expects(self::never())->method('reopen');

        $this->reopener->reopen(
            $this->buildAnAbandonedPullRequest(),
            UserTestBuilder::aUser()->build(),
        );
    }

    public function testItThrowsAnExceptionIfThePullRequestDestinationBranchDoesNotExistAnymore(): void
    {
        $this->expectException(PullRequestCannotBeReopen::class);

        $source_repository      = $this->buildSourceRepository();
        $destination_repository = $this->buildDestinationRepository();
        $this->repository_factory->method('getRepositoryById')->willReturnMap([
            [2, $source_repository],
            [1, $destination_repository],
        ]);

        $source_git_exec      = $this->createMock(GitExec::class);
        $destination_git_exec = $this->createMock(GitExec::class);
        $this->git_exec_factory->method("getGitExec")->willReturnMap([
            [$source_repository, $source_git_exec],
            [$destination_repository, $destination_git_exec],
        ]);

        $source_git_exec->method('getBranchSha1')->with('fork01')->willReturn('0000000000000000000000000000000000000001');
        $destination_git_exec->method('getBranchSha1')->with('main')->willThrowException(new UnknownBranchNameException('main'));

        $this->pull_request_updater->expects(self::never())->method('updatePullRequestWithNewSourceRev');
        $this->dao->expects(self::never())->method('reopen');

        $this->reopener->reopen(
            $this->buildAnAbandonedPullRequest(),
            UserTestBuilder::aUser()->build(),
        );
    }

    public function testItReopensAndUpdatesThePullRequest(): void
    {
        $source_repository      = $this->buildSourceRepository();
        $destination_repository = $this->buildDestinationRepository();
        $this->repository_factory->method('getRepositoryById')->willReturnMap([
            [2, $source_repository],
            [1, $destination_repository],
        ]);

        $source_git_exec      = $this->createMock(GitExec::class);
        $destination_git_exec = $this->createMock(GitExec::class);
        $this->git_exec_factory->method("getGitExec")->willReturnMap([
            [$source_repository, $source_git_exec],
            [$destination_repository, $destination_git_exec],
        ]);

        $source_git_exec->method('getBranchSha1')->with('fork01')->willReturn('0000000000000000000000000000000000000001');
        $destination_git_exec->method('getBranchSha1')->with('main')->willReturn('0000000000000000000000000000000000000000');

        $this->pull_request_updater->expects(self::once())->method('updatePullRequestWithNewSourceRev');
        $this->dao->expects(self::once())->method('reopen');
        $this->timeline_event_creator->expects(self::once())->method('storeReopenEvent');

        $this->reopener->reopen(
            $this->buildAnAbandonedPullRequest(),
            UserTestBuilder::aUser()->build(),
        );
    }

    public function testItThrowsAnExceptionIfThePullRequestUpdateFails(): void
    {
        $this->expectException(PullRequestCannotBeReopen::class);

        $source_repository      = $this->buildSourceRepository();
        $destination_repository = $this->buildDestinationRepository();
        $this->repository_factory->method('getRepositoryById')->willReturnMap([
            [2, $source_repository],
            [1, $destination_repository],
        ]);

        $source_git_exec      = $this->createMock(GitExec::class);
        $destination_git_exec = $this->createMock(GitExec::class);
        $this->git_exec_factory->method("getGitExec")->willReturnMap([
            [$source_repository, $source_git_exec],
            [$destination_repository, $destination_git_exec],
        ]);

        $source_git_exec->method('getBranchSha1')->with('fork01')->willReturn('0000000000000000000000000000000000000001');
        $destination_git_exec->method('getBranchSha1')->with('main')->willReturn('0000000000000000000000000000000000000000');

        $pull_request = $this->buildAnAbandonedPullRequest();
        $this->pull_request_updater->expects(self::once())->method('updatePullRequestWithNewSourceRev')->willThrowException(new GitReferenceNotFound($pull_request));
        $this->dao->expects(self::never())->method('reopen');

        $this->reopener->reopen(
            $pull_request,
            UserTestBuilder::aUser()->build(),
        );
    }

    private function buildSourceRepository(): GitRepository
    {
        $git_repository = new GitRepository();
        $git_repository->setId(2);

        return $git_repository;
    }

    private function buildDestinationRepository(): GitRepository
    {
        $git_repository = new GitRepository();
        $git_repository->setId(1);

        return $git_repository;
    }

    private function buildAnAbandonedPullRequest(): PullRequest
    {
        $id                         = 1;
        $title                      = 'title01';
        $description                = 'descr01';
        $source_repository_id       = 2;
        $destination_repository_id  = 1;
        $user_id                    = 101;
        $creation_date              = 1565169592;
        $source_reference           = 'fork01';
        $source_reference_sha1      = '0000000000000000000000000000000000000000';
        $destination_reference      = 'main';
        $destination_reference_sha1 = '0000000000000000000000000000000000000001';

        return new PullRequest(
            $id,
            $title,
            $description,
            $source_repository_id,
            $user_id,
            $creation_date,
            $source_reference,
            $source_reference_sha1,
            $destination_repository_id,
            $destination_reference,
            $destination_reference_sha1,
            TimelineComment::FORMAT_TEXT,
            'A'
        );
    }

    private function buildAnOpenPullRequest(): PullRequest
    {
        $id                         = 1;
        $title                      = 'title01';
        $description                = 'descr01';
        $source_repository_id       = 2;
        $destination_repository_id  = 1;
        $user_id                    = 101;
        $creation_date              = 1565169592;
        $source_reference           = 'fork01';
        $source_reference_sha1      = '0000000000000000000000000000000000000000';
        $destination_reference      = 'main';
        $destination_reference_sha1 = '0000000000000000000000000000000000000001';

        return new PullRequest(
            $id,
            $title,
            $description,
            $source_repository_id,
            $user_id,
            $creation_date,
            $source_reference,
            $source_reference_sha1,
            $destination_repository_id,
            $destination_reference,
            $destination_reference_sha1,
            TimelineComment::FORMAT_TEXT,
            'R'
        );
    }

    private function buildAMergedPullRequest(): PullRequest
    {
        $id                         = 1;
        $title                      = 'title01';
        $description                = 'descr01';
        $source_repository_id       = 2;
        $destination_repository_id  = 1;
        $user_id                    = 101;
        $creation_date              = 1565169592;
        $source_reference           = 'fork01';
        $source_reference_sha1      = '0000000000000000000000000000000000000000';
        $destination_reference      = 'main';
        $destination_reference_sha1 = '0000000000000000000000000000000000000001';

        return new PullRequest(
            $id,
            $title,
            $description,
            $source_repository_id,
            $user_id,
            $creation_date,
            $source_reference,
            $source_reference_sha1,
            $destination_repository_id,
            $destination_reference,
            $destination_reference_sha1,
            TimelineComment::FORMAT_TEXT,
            'M'
        );
    }
}
