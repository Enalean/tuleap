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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Tuleap\PullRequest\Notification\InvalidWorkerEventPayloadException;
use Tuleap\PullRequest\PullRequest;

final class PulRequestUpdatedEventTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testEventCanBeTransformedToAWorkerEventPayload(): void
    {
        $pull_request = \Mockery::mock(PullRequest::class);
        $pull_request->shouldReceive('getId')->andReturn(12);
        $user = \Mockery::mock(PFUser::class);
        $user->shouldReceive('getId')->andReturn(147);
        $event = PullRequestUpdatedEvent::fromPullRequestUserAndReferences(
            $pull_request,
            $user,
            'a7d1692502252a5ec18bfcae4184498b1459810c',
            'fbe4dade4f744aa203ec35bf09f71475ecc3f9d6',
            '4682f1f1fb9ee3cf6ca518547ae5525c9768a319',
            '4682f1f1fb9ee3cf6ca518547ae5525c9768a319',
        );

        $this->assertEquals(
            [
                'user_id' => $user->getId(),
                'pr_id'   => $pull_request->getId(),
                'old_src' => 'a7d1692502252a5ec18bfcae4184498b1459810c',
                'new_src' => 'fbe4dade4f744aa203ec35bf09f71475ecc3f9d6',
                'old_dst' => '4682f1f1fb9ee3cf6ca518547ae5525c9768a319',
                'new_dst' => '4682f1f1fb9ee3cf6ca518547ae5525c9768a319'
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

        $pull_request = \Mockery::mock(PullRequest::class);
        $pull_request->shouldReceive('getId')->andReturn($pr_id);
        $user = \Mockery::mock(PFUser::class);
        $user->shouldReceive('getId')->andReturn($user_id);

        $payload = [
            'pr_id'   => $pr_id,
            'user_id' => $user_id,
            'old_src' => $old_src,
            'new_src' => $new_src,
            'old_dst' => $old_dst,
            'new_dst' => $new_dst
        ];

        $event = PullRequestUpdatedEvent::fromWorkerEventPayload($payload);

        $this->assertEquals(
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
