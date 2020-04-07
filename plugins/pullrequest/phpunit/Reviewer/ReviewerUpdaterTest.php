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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\PullRequest\Authorization\PullRequestPermissionChecker;
use Tuleap\PullRequest\Exception\UserCannotReadGitRepositoryException;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\Reviewer\Change\ReviewerChangeEvent;

final class ReviewerUpdaterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const USER_DOING_THE_CHANGES_ID = 999;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ReviewerDAO
     */
    private $dao;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PullRequestPermissionChecker
     */
    private $permissions_checker;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|EventDispatcherInterface
     */
    private $event_dispatcher;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\PFUser
     */
    private $user_doing_the_changes;
    /**
     * @var ReviewerUpdater
     */
    private $reviewer_updater;

    protected function setUp(): void
    {
        $this->dao                 = Mockery::mock(ReviewerDAO::class);
        $this->permissions_checker = Mockery::mock(PullRequestPermissionChecker::class);
        $this->event_dispatcher    = Mockery::mock(EventDispatcherInterface::class);

        $this->user_doing_the_changes = Mockery::mock(\PFUser::class);
        $this->user_doing_the_changes->shouldReceive('getId')->andReturn((string) self::USER_DOING_THE_CHANGES_ID);

        $this->reviewer_updater = new ReviewerUpdater($this->dao, $this->permissions_checker, $this->event_dispatcher);
    }

    public function testListOfReviewersCanBeCleared(): void
    {
        $pull_request = Mockery::mock(PullRequest::class);
        $pull_request->shouldReceive('getId')->andReturn(85);
        $pull_request->shouldReceive('getStatus')->andReturn(PullRequest::STATUS_REVIEW);

        $this->dao->shouldReceive('setReviewers')->with(85, self::USER_DOING_THE_CHANGES_ID, 1)
            ->once()->andReturn(78);

        $this->event_dispatcher->shouldReceive('dispatch')->with(Mockery::type(ReviewerChangeEvent::class));

        $this->reviewer_updater->updatePullRequestReviewers(
            $pull_request,
            $this->user_doing_the_changes,
            new \DateTimeImmutable('@1')
        );
    }

    public function testSetListOfReviewers(): void
    {
        $pull_request = Mockery::mock(PullRequest::class);
        $pull_request->shouldReceive('getId')->andReturn(85);
        $pull_request->shouldReceive('getStatus')->andReturn(PullRequest::STATUS_REVIEW);

        $user_1 = Mockery::mock(\PFUser::class);
        $user_1->shouldReceive('getId')->andReturn('101');
        $user_2 = Mockery::mock(\PFUser::class);
        $user_2->shouldReceive('getId')->andReturn('102');

        $expected_change_id = 79;
        $this->dao->shouldReceive('setReviewers')->with(85, self::USER_DOING_THE_CHANGES_ID, 1, 101, 102)
            ->once()->andReturn($expected_change_id);
        $this->permissions_checker->shouldReceive('checkPullRequestIsReadableByUser')->twice();

        $this->event_dispatcher->shouldReceive('dispatch')->with(
            Mockery::on(
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
        $pull_request = Mockery::mock(PullRequest::class);
        $pull_request->shouldReceive('getId')->andReturn(85);
        $pull_request->shouldReceive('getStatus')->andReturn(PullRequest::STATUS_REVIEW);

        $this->dao->shouldReceive('setReviewers')->once()->andReturnNull();
        $this->permissions_checker->shouldReceive('checkPullRequestIsReadableByUser');

        $this->event_dispatcher->shouldNotReceive('dispatch');

        $this->reviewer_updater->updatePullRequestReviewers(
            $pull_request,
            $this->user_doing_the_changes,
            new \DateTimeImmutable('@1'),
            $this->user_doing_the_changes
        );
    }

    public function testUpdateTheListOfReviewersIsRejectedIfOneOfTheNewReviewerCanNotAccessThePullRequest(): void
    {
        $pull_request = Mockery::mock(PullRequest::class);
        $pull_request->shouldReceive('getId')->andReturn(85);
        $pull_request->shouldReceive('getStatus')->andReturn(PullRequest::STATUS_REVIEW);

        $user = Mockery::mock(\PFUser::class);
        $user->shouldReceive('getId')->andReturn('101');

        $this->permissions_checker->shouldReceive('checkPullRequestIsReadableByUser')
            ->andThrow(UserCannotReadGitRepositoryException::class);

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
        $pull_request = Mockery::mock(PullRequest::class);
        $pull_request->shouldReceive('getId')->andReturn(86);
        $pull_request->shouldReceive('getStatus')->andReturn(PullRequest::STATUS_MERGED);

        $this->expectException(ReviewersCannotBeUpdatedOnClosedPullRequestException::class);
        $this->reviewer_updater->updatePullRequestReviewers($pull_request, $this->user_doing_the_changes, new \DateTimeImmutable('@1'));
    }
}
