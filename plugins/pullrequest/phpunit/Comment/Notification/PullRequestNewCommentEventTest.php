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

namespace Tuleap\PullRequest\Comment\Notification;

use PHPUnit\Framework\TestCase;
use Tuleap\PullRequest\Notification\InvalidWorkerEventPayloadException;

final class PullRequestNewCommentEventTest extends TestCase
{
    public function testEventCanBeJSONSerialized(): void
    {
        $event = PullRequestNewCommentEvent::fromCommentID(951);

        $this->assertJsonStringEqualsJsonString('{"comment_id":951}', json_encode($event, JSON_THROW_ON_ERROR));
    }

    public function testEventCanBeBuiltFromWorkerEventPayload(): void
    {
        $payload = [
            'comment_id' => 952
        ];

        $event = PullRequestNewCommentEvent::fromWorkerEventPayload($payload);

        $this->assertEquals(PullRequestNewCommentEvent::fromCommentID(952), $event);
    }

    public function testBuildingFromAnInvalidPayloadIsRejected(): void
    {
        $this->expectException(InvalidWorkerEventPayloadException::class);
        PullRequestNewCommentEvent::fromWorkerEventPayload([]);
    }
}
