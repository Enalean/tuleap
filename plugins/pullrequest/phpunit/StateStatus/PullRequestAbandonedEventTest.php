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

namespace Tuleap\PullRequest\StateStatus;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Tuleap\PullRequest\PullRequest;

final class PullRequestAbandonedEventTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testEventCanBeJSONSerialized(): void
    {
        $pull_request = \Mockery::mock(PullRequest::class);
        $pull_request->shouldReceive('getId')->andReturn(12);
        $user = \Mockery::mock(PFUser::class);
        $user->shouldReceive('getId')->andReturn(147);
        $event = PullRequestAbandonedEvent::fromPullRequestAndUserAbandoningThePullRequest($pull_request, $user);

        $this->assertJsonStringEqualsJsonString('{"user_id":147,"pr_id":12}', json_encode($event, JSON_THROW_ON_ERROR));
    }

    public function testEventCanBeBuiltFromWorkerEventPayload(): void
    {
        $pr_id   = 13;
        $user_id = 147;

        $pull_request = \Mockery::mock(PullRequest::class);
        $pull_request->shouldReceive('getId')->andReturn($pr_id);
        $user = \Mockery::mock(PFUser::class);
        $user->shouldReceive('getId')->andReturn($user_id);

        $payload = [
            'pr_id'   => $pr_id,
            'user_id' => $user_id,
        ];

        $event = PullRequestAbandonedEvent::fromWorkerEventPayload($payload);

        $this->assertEquals(
            PullRequestAbandonedEvent::fromPullRequestAndUserAbandoningThePullRequest($pull_request, $user),
            $event
        );
    }
}
