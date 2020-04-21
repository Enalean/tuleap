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

namespace Tuleap\PullRequest\InlineComment\Notification;

use PHPUnit\Framework\TestCase;
use Tuleap\PullRequest\Notification\InvalidWorkerEventPayloadException;

final class PullRequestNewInlineCommentEventTest extends TestCase
{
    public function testEventCanBeTransformedToAWorkerEventPayload(): void
    {
        $event = PullRequestNewInlineCommentEvent::fromInlineCommentID(753);

        $this->assertEquals(['inline_comment_id' => 753], $event->toWorkerEventPayload());
    }

    public function testEventCanBeBuiltFromWorkerEventPayload(): void
    {
        $payload = [
            'inline_comment_id' => 754
        ];

        $event = PullRequestNewInlineCommentEvent::fromWorkerEventPayload($payload);

        $this->assertEquals(PullRequestNewInlineCommentEvent::fromInlineCommentID(754), $event);
    }

    public function testBuildingFromAnInvalidPayloadIsRejected(): void
    {
        $this->expectException(InvalidWorkerEventPayloadException::class);
        PullRequestNewInlineCommentEvent::fromWorkerEventPayload([]);
    }
}
