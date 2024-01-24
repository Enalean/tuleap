<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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
 *
 */

namespace Tuleap\PullRequest;

use GitRepositoryFactory;
use PFUser;
use Psr\EventDispatcher\EventDispatcherInterface;
use ReferenceManager;
use Tuleap\PullRequest\BranchUpdate\PullRequestUpdatedEvent;
use Tuleap\PullRequest\GitReference\GitPullRequestReferenceUpdater;
use GitRepository;
use Tuleap\PullRequest\InlineComment\Dao as InlineCommentDAO;
use Tuleap\PullRequest\InlineComment\InlineCommentUpdater;
use Tuleap\PullRequest\PullRequest\Timeline\TimelineComment;
use Tuleap\PullRequest\Timeline\TimelineEventCreator;
use Tuleap\Test\Builders\UserTestBuilder;

final class PullRequestUpdaterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private PullRequestUpdater $pull_request_updater;
    private Dao $dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&InlineCommentDAO
     */
    private $inline_comments_dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&GitRepositoryFactory
     */
    private $git_repository_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&GitExecFactory
     */
    private $git_exec_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&GitExec
     */
    private $git_exec;
    private PFUser $user;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&GitPullRequestReferenceUpdater
     */
    private $pr_reference_updater;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&PullRequestMerger
     */
    private $pr_merger;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&TimelineEventCreator
     */
    private $timeline_event_creator;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&EventDispatcherInterface
     */
    private $event_dispatcher;

    protected function setUp(): void
    {
        $reference_manager = $this->createMock(ReferenceManager::class);

        $this->dao                    = new Dao();
        $this->inline_comments_dao    = $this->createMock(InlineCommentDAO::class);
        $this->git_repository_factory = $this->createMock(GitRepositoryFactory::class);
        $this->git_exec_factory       = $this->createMock(GitExecFactory::class);
        $this->pr_reference_updater   = $this->createMock(GitPullRequestReferenceUpdater::class);
        $this->pr_merger              = $this->createMock(PullRequestMerger::class);
        $this->timeline_event_creator = $this->createMock(TimelineEventCreator::class);
        $this->event_dispatcher       = $this->createMock(EventDispatcherInterface::class);
        $this->pull_request_updater   = new PullRequestUpdater(
            new Factory($this->dao, $reference_manager),
            $this->pr_merger,
            $this->inline_comments_dao,
            $this->createMock(InlineCommentUpdater::class),
            new FileUniDiffBuilder(),
            $this->timeline_event_creator,
            $this->git_repository_factory,
            $this->git_exec_factory,
            $this->pr_reference_updater,
            $this->event_dispatcher
        );

        $this->git_exec = $this->createMock(GitExec::class);
        $this->user     = UserTestBuilder::aUser()->withId(1337)->build();
    }

    public function testItUpdatesSourceBranchInPRs(): void
    {
        $this->pr_reference_updater->method('updatePullRequestReference');
        $this->git_exec->method('getCommonAncestor')->willReturn('sha2');
        $this->pr_merger->method('detectMergeabilityStatus');
        $this->timeline_event_creator->method('storeUpdateEvent');

        $pr1_id = $this->dao->create(1, 'title', 'description', 1, 0, 'dev', 'sha1', 1, 'master', 'sha2', 0, TimelineComment::FORMAT_TEXT);
        $pr2_id = $this->dao->create(1, 'title', 'description', 1, 0, 'dev', 'sha1', 1, 'other', 'sha2', 0, TimelineComment::FORMAT_TEXT);
        $pr3_id = $this->dao->create(1, 'title', 'description', 1, 0, 'master', 'sha1', 1, 'other', 'sha2', 0, TimelineComment::FORMAT_TEXT);

        $git_repo = $this->createMock(GitRepository::class);
        $git_repo->method('getId')->willReturn(1);

        $this->inline_comments_dao->method('searchUpToDateByPullRequestId')->willReturn([]);

        $this->git_repository_factory->method('getRepositoryById')->willReturn($git_repo);
        $this->git_exec_factory->method('getGitExec')->with($git_repo)->willReturn($this->git_exec);

        $this->event_dispatcher->expects(self::atLeast(1))->method('dispatch')->with(self::isInstanceOf(PullRequestUpdatedEvent::class));

        $this->pull_request_updater->updatePullRequests($this->user, $git_repo, 'dev', 'sha1new');

        $pr1 = $this->dao->searchByPullRequestId($pr1_id);
        $pr2 = $this->dao->searchByPullRequestId($pr2_id);
        $pr3 = $this->dao->searchByPullRequestId($pr3_id);

        self::assertNotNull($pr1);
        self::assertEquals('sha1new', $pr1['sha1_src']);
        self::assertNotNull($pr2);
        self::assertEquals('sha1new', $pr2['sha1_src']);
        self::assertNotNull($pr3);
        self::assertEquals('sha1', $pr3['sha1_src']);
    }

    public function testItDoesNotUpdateSourceBranchOfOtherRepositories(): void
    {
        $this->pr_reference_updater->method('updatePullRequestReference');
        $this->git_exec->method('getCommonAncestor')->willReturn('sha2');
        $this->pr_merger->method('detectMergeabilityStatus');
        $this->timeline_event_creator->method('storeUpdateEvent');

        $pr1_id = $this->dao->create(2, 'title', 'description', 1, 0, 'dev', 'sha1', 2, 'master', 'sha2', 0, TimelineComment::FORMAT_TEXT);
        $pr2_id = $this->dao->create(2, 'title', 'description', 1, 0, 'master', 'sha1', 2, 'dev', 'sha2', 0, TimelineComment::FORMAT_TEXT);

        $git_repo = $this->createMock(GitRepository::class);
        $git_repo->method('getId')->willReturn(1);

        $this->inline_comments_dao->method('searchUpToDateByPullRequestId')->willReturn([]);

        $this->git_repository_factory->method('getRepositoryById')->willReturn($git_repo);
        $this->git_exec_factory->method('getGitExec')->with($git_repo)->willReturn($this->git_exec);

        $this->event_dispatcher->method('dispatch');

        $this->pull_request_updater->updatePullRequests($this->user, $git_repo, 'dev', 'sha1new');

        $pr1 = $this->dao->searchByPullRequestId($pr1_id);
        $pr2 = $this->dao->searchByPullRequestId($pr2_id);

        self::assertNotNull($pr1);
        self::assertEquals('sha1', $pr1['sha1_src']);
        self::assertNotNull($pr2);
        self::assertEquals('sha1', $pr2['sha1_src']);
    }

    public function testItDoesNotUpdateClosedPRs(): void
    {
        $this->pr_reference_updater->method('updatePullRequestReference');
        $this->git_exec->method('getCommonAncestor')->willReturn('sha2');
        $this->pr_merger->method('detectMergeabilityStatus');
        $this->timeline_event_creator->method('storeUpdateEvent');

        $pr1_id = $this->dao->create(1, 'title', 'description', 1, 0, 'dev', 'sha1', 1, 'master', 'sha2', 0, TimelineComment::FORMAT_TEXT);
        $pr2_id = $this->dao->create(1, 'title', 'description', 1, 0, 'master', 'sha1', 1, 'dev', 'sha2', 0, TimelineComment::FORMAT_TEXT);

        $this->dao->markAsMerged($pr1_id);
        $this->dao->markAsAbandoned($pr2_id);

        $git_repo = $this->createMock(GitRepository::class);
        $git_repo->method('getId')->willReturn(1);

        $this->inline_comments_dao->method('searchUpToDateByPullRequestId')->willReturn([]);

        $this->git_repository_factory->method('getRepositoryById')->willReturn($git_repo);
        $this->git_exec_factory->method('getGitExec')->with($git_repo)->willReturn($this->git_exec);

        $this->event_dispatcher->method('dispatch');

        $this->pull_request_updater->updatePullRequests($this->user, $git_repo, 'dev', 'sha1new');

        $pr1 = $this->dao->searchByPullRequestId($pr1_id);
        $pr2 = $this->dao->searchByPullRequestId($pr2_id);

        self::assertNotNull($pr1);
        self::assertEquals('sha1', $pr1['sha1_src']);
        self::assertNotNull($pr2);
        self::assertEquals('sha1', $pr2['sha1_src']);
    }
}
