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

use Tuleap\PullRequest\Notification\InvalidWorkerEventPayloadException;
use Tuleap\PullRequest\PullRequest;
use Tuleap\Test\Builders\UserTestBuilder;

final class PullRequestAbandonedEventTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testEventCanBeTransformedToAWorkerEventPayload(): void
    {
        $pull_request = $this->createMock(PullRequest::class);
        $pull_request->method('getId')->willReturn(12);
        $user  = UserTestBuilder::aUser()->withId(147)->build();
        $event = PullRequestAbandonedEvent::fromPullRequestAndUserAbandoningThePullRequest($pull_request, $user);

        $this->assertEquals(
            ['user_id' => $user->getId(), 'pr_id' => $pull_request->getId()],
            $event->toWorkerEventPayload()
        );
    }

    public function testEventCanBeBuiltFromWorkerEventPayload(): void
    {
        $pr_id   = 13;
        $user_id = 147;

        $pull_request = $this->createMock(PullRequest::class);
        $pull_request->method('getId')->willReturn($pr_id);
        $user = UserTestBuilder::aUser()->withId($user_id)->build();

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

    public function testBuildingFromAnInvalidPayloadIsRejected(): void
    {
        $this->expectException(InvalidWorkerEventPayloadException::class);
        PullRequestAbandonedEvent::fromWorkerEventPayload([]);
    }
}
