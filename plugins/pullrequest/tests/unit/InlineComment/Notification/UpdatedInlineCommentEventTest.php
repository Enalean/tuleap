<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

use Tuleap\PullRequest\Notification\InvalidWorkerEventPayloadException;
use Tuleap\PullRequest\Tests\Builders\InlineCommentTestBuilder;

final class UpdatedInlineCommentEventTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testEventCanBeTransformedToAWorkerEventPayload(): void
    {
        $event = UpdatedInlineCommentEvent::fromInlineComment(InlineCommentTestBuilder::aMarkdownComment("C. Sa Majesté")->withId(753)->build());

        $this->assertEquals(['inline_comment_id' => 753], $event->toWorkerEventPayload());
    }

    public function testEventCanBeBuiltFromWorkerEventPayload(): void
    {
        $payload = [
            'inline_comment_id' => 754,
        ];

        $event = UpdatedInlineCommentEvent::fromWorkerEventPayload($payload);

        $this->assertEquals(UpdatedInlineCommentEvent::fromInlineComment(InlineCommentTestBuilder::aMarkdownComment("C. Sa Majesté")->withId(754)->build()), $event);
    }

    public function testBuildingFromAnInvalidPayloadIsRejected(): void
    {
        $this->expectException(InvalidWorkerEventPayloadException::class);
        UpdatedInlineCommentEvent::fromWorkerEventPayload([]);
    }
}
