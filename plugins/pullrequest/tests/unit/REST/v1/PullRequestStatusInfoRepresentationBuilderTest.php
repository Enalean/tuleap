<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

namespace Tuleap\PullRequest\REST\v1;

use Tuleap\PullRequest\Tests\Builders\PullRequestTestBuilder;
use Tuleap\PullRequest\Tests\Stub\SearchAbandonEventStub;
use Tuleap\PullRequest\Tests\Stub\SearchMergeEventStub;
use Tuleap\PullRequest\Timeline\TimelineGlobalEvent;
use Tuleap\REST\JsonCast;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stubs\RetrieveUserByIdStub;
use Tuleap\Test\Stubs\User\Avatar\ProvideUserAvatarUrlStub;
use Tuleap\User\REST\MinimalUserRepresentation;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PullRequestStatusInfoRepresentationBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const USER_ID = 102;

    public function testItReturnsARepresentationWhenThePullRequestHasBeenMergedAndAMergeEventExists(): void
    {
        $merge_date_timestamp = 1679910276;
        $user                 = $this->buildUser();
        $builder              = new PullRequestStatusInfoRepresentationBuilder(
            SearchMergeEventStub::withMergeEvent(
                $merge_date_timestamp,
                self::USER_ID
            ),
            SearchAbandonEventStub::withAbandonEvent(
                167990000,
                self::USER_ID
            ),
            RetrieveUserByIdStub::withUser($user),
            ProvideUserAvatarUrlStub::build(),
        );

        $representation = $builder->buildPullRequestStatusInfoRepresentation(
            PullRequestTestBuilder::aMergedPullRequest()->build()
        );

        self::assertEquals(
            new PullRequestStatusInfoRepresentation(
                PullRequestStatusTypeConverter::fromIntStatusToStringStatus(TimelineGlobalEvent::MERGE),
                JsonCast::toDate($merge_date_timestamp),
                MinimalUserRepresentation::build($user, ProvideUserAvatarUrlStub::build())
            ),
            $representation
        );
    }

    public function testItReturnsNullWhenThePullRequestHasBeenMergedButTheUserThatDidTheActionIsNotFound(): void
    {
        $builder = new PullRequestStatusInfoRepresentationBuilder(
            SearchMergeEventStub::withMergeEvent(
                1679910276,
                666
            ),
            SearchAbandonEventStub::withNoAbandonEvent(),
            RetrieveUserByIdStub::withNoUser(),
            ProvideUserAvatarUrlStub::build(),
        );

        self::assertNull(
            $builder->buildPullRequestStatusInfoRepresentation(
                PullRequestTestBuilder::aMergedPullRequest()->build()
            )
        );
    }

    public function testItReturnsNullWhenThePullRequestHasBeenMergedButNoMergeEventExists(): void
    {
        $builder = new PullRequestStatusInfoRepresentationBuilder(
            SearchMergeEventStub::withNoMergeEvent(),
            SearchAbandonEventStub::withNoAbandonEvent(),
            RetrieveUserByIdStub::withUser($this->buildUser()),
            ProvideUserAvatarUrlStub::build(),
        );

        self::assertNull(
            $builder->buildPullRequestStatusInfoRepresentation(
                PullRequestTestBuilder::aMergedPullRequest()->build()
            )
        );
    }

    public function testItReturnsNullWhenThePullRequestIsStillInReview(): void
    {
        $builder = new PullRequestStatusInfoRepresentationBuilder(
            SearchMergeEventStub::withNoMergeEvent(),
            SearchAbandonEventStub::withNoAbandonEvent(),
            RetrieveUserByIdStub::withNoUser(),
            ProvideUserAvatarUrlStub::build(),
        );

        self::assertNull(
            $builder->buildPullRequestStatusInfoRepresentation(
                PullRequestTestBuilder::aPullRequestInReview()->build()
            )
        );
    }

    public function testItReturnsARepresentationWhenThePullRequestHasBeenAbandoned(): void
    {
        $merge_date_timestamp = 1679910276;
        $user                 = $this->buildUser();
        $builder              = new PullRequestStatusInfoRepresentationBuilder(
            SearchMergeEventStub::withNoMergeEvent(),
            SearchAbandonEventStub::withAbandonEvent(
                $merge_date_timestamp,
                self::USER_ID
            ),
            RetrieveUserByIdStub::withUser($user),
            ProvideUserAvatarUrlStub::build(),
        );

        $representation = $builder->buildPullRequestStatusInfoRepresentation(
            PullRequestTestBuilder::anAbandonedPullRequest()->build()
        );

        self::assertEquals(
            new PullRequestStatusInfoRepresentation(
                PullRequestStatusTypeConverter::fromIntStatusToStringStatus(TimelineGlobalEvent::ABANDON),
                JsonCast::toDate($merge_date_timestamp),
                MinimalUserRepresentation::build($user, ProvideUserAvatarUrlStub::build())
            ),
            $representation
        );
    }

    public function testItReturnsNullWhenThePullRequestHasBeenAbandonedButTheUserThatDidTheActionIsNotFound(): void
    {
        $builder = new PullRequestStatusInfoRepresentationBuilder(
            SearchMergeEventStub::withNoMergeEvent(),
            SearchAbandonEventStub::withAbandonEvent(
                1679910276,
                666
            ),
            RetrieveUserByIdStub::withNoUser(),
            ProvideUserAvatarUrlStub::build(),
        );

        self::assertNull(
            $builder->buildPullRequestStatusInfoRepresentation(
                PullRequestTestBuilder::anAbandonedPullRequest()->build()
            )
        );
    }

    public function testItReturnsNullWhenThePullRequestHasBeenAbandonedButNoAbandonEventExists(): void
    {
        $builder = new PullRequestStatusInfoRepresentationBuilder(
            SearchMergeEventStub::withNoMergeEvent(),
            SearchAbandonEventStub::withNoAbandonEvent(),
            RetrieveUserByIdStub::withUser($this->buildUser()),
            ProvideUserAvatarUrlStub::build(),
        );

        self::assertNull(
            $builder->buildPullRequestStatusInfoRepresentation(
                PullRequestTestBuilder::anAbandonedPullRequest()->build()
            )
        );
    }

    private function buildUser(): \PFUser
    {
        return UserTestBuilder::anActiveUser()
            ->withId(self::USER_ID)
            ->withRealName("Joe l'Asticot")
            ->withAvatarUrl('url/to/user_avatar.png')
            ->build();
    }
}
