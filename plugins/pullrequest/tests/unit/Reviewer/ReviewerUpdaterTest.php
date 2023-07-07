<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\Reviewer;

use GitRepoNotFoundException;
use Project_AccessException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\PullRequest\Authorization\PullRequestPermissionChecker;
use Tuleap\PullRequest\Exception\UserCannotReadGitRepositoryException;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\Reviewer\Change\ReviewerChangeEvent;
use Tuleap\Test\Builders\UserTestBuilder;

final class ReviewerUpdaterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const USER_DOING_THE_CHANGES_ID = 999;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ReviewerDAO
     */
    private $dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&PullRequestPermissionChecker
     */
    private $permissions_checker;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&EventDispatcherInterface
     */
    private $event_dispatcher;
    private \PFUser $user_doing_the_changes;
    private ReviewerUpdater $reviewer_updater;

    protected function setUp(): void
    {
        $this->dao                 = $this->createMock(ReviewerDAO::class);
        $this->permissions_checker = $this->createMock(PullRequestPermissionChecker::class);
        $this->event_dispatcher    = $this->createMock(EventDispatcherInterface::class);

        $this->user_doing_the_changes = UserTestBuilder::aUser()->withId(self::USER_DOING_THE_CHANGES_ID)->build();

        $this->reviewer_updater = new ReviewerUpdater($this->dao, $this->permissions_checker, $this->event_dispatcher);
    }

    public function testListOfReviewersCanBeCleared(): void
    {
        $pull_request = $this->createMock(PullRequest::class);
        $pull_request->method('getId')->willReturn(85);
        $pull_request->method('getStatus')->willReturn(PullRequest::STATUS_REVIEW);

        $this->dao->expects(self::once())->method('setReviewers')->with(85, self::USER_DOING_THE_CHANGES_ID, 1)
            ->willReturn(78);

        $this->event_dispatcher->method('dispatch')->with(self::isInstanceOf(ReviewerChangeEvent::class));

        $this->reviewer_updater->updatePullRequestReviewers(
            $pull_request,
            $this->user_doing_the_changes,
            new \DateTimeImmutable('@1')
        );
    }

    public function testSetListOfReviewers(): void
    {
        $pull_request = $this->createMock(PullRequest::class);
        $pull_request->method('getId')->willReturn(85);
        $pull_request->method('getStatus')->willReturn(PullRequest::STATUS_REVIEW);

        $user_1 = $this->createMock(\PFUser::class);
        $user_1->method('getId')->willReturn('101');
        $user_2 = $this->createMock(\PFUser::class);
        $user_2->method('getId')->willReturn('102');

        $expected_change_id = 79;
        $this->dao->expects(self::once())->method('setReviewers')->with(85, self::USER_DOING_THE_CHANGES_ID, 1, 101, 102)
            ->willReturn($expected_change_id);
        $this->permissions_checker->expects(self::exactly(2))->method('checkPullRequestIsReadableByUser');

        $this->event_dispatcher->method('dispatch')->with(
            self::callback(
                static function (ReviewerChangeEvent $event) use ($expected_change_id): bool {
                    return $event->getChangeID() === $expected_change_id;
                }
            )
        );

        $this->reviewer_updater->updatePullRequestReviewers(
            $pull_request,
            $this->user_doing_the_changes,
            new \DateTimeImmutable('@1'),
            $user_1,
            $user_2
        );
    }

    public function testReviewerChangeEventIsNotSentWhenNoNewChangesAreCreated(): void
    {
        $pull_request = $this->createMock(PullRequest::class);
        $pull_request->method('getId')->willReturn(85);
        $pull_request->method('getStatus')->willReturn(PullRequest::STATUS_REVIEW);

        $this->dao->expects(self::once())->method('setReviewers')->willReturn(null);
        $this->permissions_checker->method('checkPullRequestIsReadableByUser');

        $this->event_dispatcher->expects(self::never())->method('dispatch');

        $this->reviewer_updater->updatePullRequestReviewers(
            $pull_request,
            $this->user_doing_the_changes,
            new \DateTimeImmutable('@1'),
            $this->user_doing_the_changes
        );
    }

    public function testUpdateTheListOfReviewersIsRejectedIfOneOfTheNewReviewerCanNotAccessThePullRequest(): void
    {
        $pull_request = $this->createMock(PullRequest::class);
        $pull_request->method('getId')->willReturn(85);
        $pull_request->method('getStatus')->willReturn(PullRequest::STATUS_REVIEW);

        $user = $this->createMock(\PFUser::class);
        $user->method('getId')->willReturn('101');

        $this->permissions_checker->method('checkPullRequestIsReadableByUser')
            ->willThrowException(new UserCannotReadGitRepositoryException());

        $this->expectException(UserCannotBeAddedAsReviewerException::class);
        $this->reviewer_updater->updatePullRequestReviewers(
            $pull_request,
            $this->user_doing_the_changes,
            new \DateTimeImmutable('@1'),
            $user
        );
    }

    public function testUpdateTheListOfReviewersIsRejectedIfOneOfTheNewReviewerCanNotAccessTheProject(): void
    {
        $pull_request = $this->createMock(PullRequest::class);
        $pull_request->method('getId')->willReturn(85);
        $pull_request->method('getStatus')->willReturn(PullRequest::STATUS_REVIEW);

        $user = $this->createMock(\PFUser::class);
        $user->method('getId')->willReturn('101');

        $this->permissions_checker->method('checkPullRequestIsReadableByUser')
            ->willThrowException(
                new class extends Project_AccessException
                {
                }
            );

        $this->expectException(UserCannotBeAddedAsReviewerException::class);
        $this->reviewer_updater->updatePullRequestReviewers(
            $pull_request,
            $this->user_doing_the_changes,
            new \DateTimeImmutable('@1'),
            $user
        );
    }

    public function testUpdateTheListOfReviewersIsRejectedIfOneOfTheNewReviewerCanNotAccessTheGitRepository(): void
    {
        $pull_request = $this->createMock(PullRequest::class);
        $pull_request->method('getId')->willReturn(85);
        $pull_request->method('getStatus')->willReturn(PullRequest::STATUS_REVIEW);

        $user = $this->createMock(\PFUser::class);
        $user->method('getId')->willReturn('101');

        $this->permissions_checker->method('checkPullRequestIsReadableByUser')
            ->willThrowException(new GitRepoNotFoundException());

        $this->expectException(UserCannotBeAddedAsReviewerException::class);
        $this->reviewer_updater->updatePullRequestReviewers(
            $pull_request,
            $this->user_doing_the_changes,
            new \DateTimeImmutable('@1'),
            $user
        );
    }

    public function testUpdatingListOfReviewersIsNotPossibleOnAClosedPullRequest(): void
    {
        $pull_request = $this->createMock(PullRequest::class);
        $pull_request->method('getId')->willReturn(86);
        $pull_request->method('getStatus')->willReturn(PullRequest::STATUS_MERGED);

        $this->expectException(ReviewersCannotBeUpdatedOnClosedPullRequestException::class);
        $this->reviewer_updater->updatePullRequestReviewers($pull_request, $this->user_doing_the_changes, new \DateTimeImmutable('@1'));
    }
}
