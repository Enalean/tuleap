<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\Notification;

use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PullRequestDescriptionUpdatedEventTest extends TestCase
{
    public function testEventCanBeTransformedToWorkerEventPayload(): void
    {
        $event = PullRequestDescriptionUpdatedEvent::fromPullRequestIdAndUserId(1, 2);

        self::assertSame([
            'pull_request_id' => 1,
            'user_id'         => 2,
        ], $event->toWorkerEventPayload());
    }

    public function testEventCanBeBuiltFromWorkerEventPayload(): void
    {
        $event = PullRequestDescriptionUpdatedEvent::fromWorkerEventPayload([
            'pull_request_id' => 3,
            'user_id'         => 4,
        ]);

        self::assertSame(3, $event->getPullRequestId());
        self::assertSame(4, $event->getUserId());
    }

    public function testItThrowsIfWorkerEventPayloadIsInvalid(): void
    {
        $this->expectException(InvalidWorkerEventPayloadException::class);
        PullRequestDescriptionUpdatedEvent::fromWorkerEventPayload([]);
    }
}
