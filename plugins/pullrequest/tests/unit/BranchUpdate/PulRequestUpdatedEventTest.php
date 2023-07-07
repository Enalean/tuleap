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

namespace Tuleap\PullRequest\BranchUpdate;

use Tuleap\PullRequest\Notification\InvalidWorkerEventPayloadException;
use Tuleap\PullRequest\PullRequest;
use Tuleap\Test\Builders\UserTestBuilder;

final class PulRequestUpdatedEventTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testEventCanBeTransformedToAWorkerEventPayload(): void
    {
        $pull_request = $this->createMock(PullRequest::class);
        $pull_request->method('getId')->willReturn(12);

        $user  = UserTestBuilder::aUser()->withId(147)->build();
        $event = PullRequestUpdatedEvent::fromPullRequestUserAndReferences(
            $pull_request,
            $user,
            'a7d1692502252a5ec18bfcae4184498b1459810c',
            'fbe4dade4f744aa203ec35bf09f71475ecc3f9d6',
            '4682f1f1fb9ee3cf6ca518547ae5525c9768a319',
            '4682f1f1fb9ee3cf6ca518547ae5525c9768a319',
        );

        self::assertEquals(
            [
                'user_id' => $user->getId(),
                'pr_id'   => $pull_request->getId(),
                'old_src' => 'a7d1692502252a5ec18bfcae4184498b1459810c',
                'new_src' => 'fbe4dade4f744aa203ec35bf09f71475ecc3f9d6',
                'old_dst' => '4682f1f1fb9ee3cf6ca518547ae5525c9768a319',
                'new_dst' => '4682f1f1fb9ee3cf6ca518547ae5525c9768a319',
            ],
            $event->toWorkerEventPayload()
        );
    }

    public function testEventCanBeBuiltFromWorkerEventPayload(): void
    {
        $pr_id   = 13;
        $user_id = 147;
        $old_src = 'a7d1692502252a5ec18bfcae4184498b1459810c';
        $new_src = 'fbe4dade4f744aa203ec35bf09f71475ecc3f9d6';
        $old_dst = '4682f1f1fb9ee3cf6ca518547ae5525c9768a319';
        $new_dst = $old_dst;

        $pull_request = $this->createMock(PullRequest::class);
        $pull_request->method('getId')->willReturn($pr_id);

        $user = UserTestBuilder::aUser()->withId($user_id)->build();

        $payload = [
            'pr_id'   => $pr_id,
            'user_id' => $user_id,
            'old_src' => $old_src,
            'new_src' => $new_src,
            'old_dst' => $old_dst,
            'new_dst' => $new_dst,
        ];

        $event = PullRequestUpdatedEvent::fromWorkerEventPayload($payload);

        self::assertEquals(
            PullRequestUpdatedEvent::fromPullRequestUserAndReferences(
                $pull_request,
                $user,
                $old_src,
                $new_src,
                $old_dst,
                $new_dst
            ),
            $event
        );
    }

    public function testBuildingFromAnInvalidPayloadIsRejected(): void
    {
        $this->expectException(InvalidWorkerEventPayloadException::class);
        PullRequestUpdatedEvent::fromWorkerEventPayload([]);
    }
}
