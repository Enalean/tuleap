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

namespace Tuleap\PullRequest\Reviewer\Notification;

use PFUser;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\Reference\HTMLURLBuilder;
use Tuleap\PullRequest\Reviewer\Change\ReviewerChange;
use Tuleap\PullRequest\Reviewer\Change\ReviewerChangeEvent;
use Tuleap\PullRequest\Reviewer\Change\ReviewerChangePullRequestAssociation;
use Tuleap\PullRequest\Reviewer\Change\ReviewerChangeRetriever;
use UserHelper;

final class ReviewerChangeNotificationToProcessBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ReviewerChangeRetriever
     */
    private $reviewer_change_retriever;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&UserHelper
     */
    private $user_helper;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&HTMLURLBuilder
     */
    private $html_url_builder;

    private ReviewerChangeNotificationToProcessBuilder $builder;

    protected function setUp(): void
    {
        $this->reviewer_change_retriever = $this->createMock(ReviewerChangeRetriever::class);
        $this->user_helper               = $this->createMock(UserHelper::class);
        $this->html_url_builder          = $this->createMock(HTMLURLBuilder::class);

        $this->builder = new ReviewerChangeNotificationToProcessBuilder(
            $this->reviewer_change_retriever,
            $this->user_helper,
            $this->html_url_builder
        );
    }

    public function testBuildReviewerAddedNotificationFromReviewerChangeEvent(): void
    {
        $pull_request = $this->createMock(PullRequest::class);
        $pull_request->method('getId')->willReturn(12);
        $pull_request->method('getTitle')->willReturn('PR Title');
        $change_user   = $this->buildUser(102);
        $new_reviewers = [$this->buildUser(103), $this->buildUser(104)];

        $reviewer_change_event = ReviewerChangeEvent::fromID(147);

        $this->user_helper->method('getDisplayNameFromUser')->willReturn('Display name');
        $this->user_helper->method('getAbsoluteUserURL')->willReturn('https://example.com/users/foo');
        $this->html_url_builder->method('getAbsolutePullRequestOverviewUrl')->willReturn('https://example.com/link-to-pr');
        $this->reviewer_change_retriever->method('getChangeWithTheAssociatedPullRequestByID')
            ->with($reviewer_change_event->getChangeID())
            ->willReturn(
                new ReviewerChangePullRequestAssociation(
                    new ReviewerChange(
                        new \DateTimeImmutable('@10'),
                        $change_user,
                        $new_reviewers,
                        []
                    ),
                    $pull_request
                )
            );

        $notifications = $this->builder->getNotificationsToProcess($reviewer_change_event);
        self::assertNotEmpty($notifications);
    }

    public function testNoAddedNotificationIsBuiltIfThereIsNoNewReviewer(): void
    {
        $pull_request = $this->createMock(PullRequest::class);
        $pull_request->method('getId')->willReturn(12);
        $change_user = $this->createMock(PFUser::class);

        $reviewer_change_event = ReviewerChangeEvent::fromID(148);

        $this->reviewer_change_retriever->method('getChangeWithTheAssociatedPullRequestByID')
            ->with($reviewer_change_event->getChangeID())
            ->willReturn(
                new ReviewerChangePullRequestAssociation(
                    new ReviewerChange(
                        new \DateTimeImmutable('@20'),
                        $change_user,
                        [],
                        []
                    ),
                    $pull_request
                )
            );

        $notifications = $this->builder->getNotificationsToProcess($reviewer_change_event);
        self::assertEmpty($notifications);
    }

    public function testNoNotificationAreBuiltWhenTheCorrespondingChangeCannotBeFound(): void
    {
        $reviewer_change_event = ReviewerChangeEvent::fromID(404);
        $this->reviewer_change_retriever->method('getChangeWithTheAssociatedPullRequestByID')
            ->with($reviewer_change_event->getChangeID())
            ->willReturn(null);

        $notifications = $this->builder->getNotificationsToProcess($reviewer_change_event);
        self::assertEmpty($notifications);
    }

    private function buildUser(int $user_id): PFUser
    {
        return new PFUser(['user_id' => $user_id, 'language_id' => 'en']);
    }
}
