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

use Tuleap\PullRequest\Notification\InvalidWorkerEventPayloadException;
use Tuleap\PullRequest\Tests\Builders\CommentTestBuilder;

final class UpdatedCommentEventTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testEventCanBeTransformedToAWorkerEventPayload(): void
    {
        $event = UpdatedCommentEvent::fromUpdatedComment(CommentTestBuilder::aMarkdownComment("jigouli")->withId(951)->build());

        self::assertEquals(['comment_id' => 951], $event->toWorkerEventPayload());
    }

    public function testEventCanBeBuiltFromWorkerEventPayload(): void
    {
        $payload = [
            'comment_id' => 952,
        ];

        $event = UpdatedCommentEvent::fromWorkerEventPayload($payload);

        self::assertEquals(UpdatedCommentEvent::fromUpdatedComment(CommentTestBuilder::aMarkdownComment("jigouli")->withId(952)->build()), $event);
    }

    public function testBuildingFromAnInvalidPayloadIsRejected(): void
    {
        $this->expectException(InvalidWorkerEventPayloadException::class);
        UpdatedCommentEvent::fromWorkerEventPayload([]);
    }
}
