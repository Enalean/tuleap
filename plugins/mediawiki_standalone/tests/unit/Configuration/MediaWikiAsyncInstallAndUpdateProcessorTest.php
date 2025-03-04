<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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


namespace Tuleap\MediawikiStandalone\Configuration;

use Psr\Log\NullLogger;
use Tuleap\Queue\WorkerEvent;
use Tuleap\Queue\WorkerEventContent;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MediaWikiAsyncInstallAndUpdateProcessorTest extends TestCase
{
    private \Closure $has_update_been_executed;
    private MediaWikiAsyncUpdateProcessor $update_processor;

    protected function setUp(): void
    {
        $update_handler = new class implements MediaWikiInstallAndUpdateHandler {
            public bool $has_update_been_executed = false;
            public function runInstallAndUpdate(): void
            {
                $this->has_update_been_executed = true;
            }
        };

        $this->has_update_been_executed = static fn(): bool => $update_handler->has_update_been_executed;

        $this->update_processor = new MediaWikiAsyncUpdateProcessor($update_handler);
    }

    public function testExecutesUpdateWhenAppropriateEventIsProcessed(): void
    {
        $this->update_processor->process(
            new WorkerEvent(
                new NullLogger(),
                new WorkerEventContent(
                    UpdateMediaWikiTask::TOPIC,
                    []
                )
            )
        );
        self::assertTrue(($this->has_update_been_executed)());
    }

    public function testDoesNothingForEventsTheUpdateProcessorDoesNotKnow(): void
    {
        $this->update_processor->process(
            new WorkerEvent(
                new NullLogger(),
                new WorkerEventContent(
                    'something',
                    []
                )
            )
        );
        self::assertFalse(($this->has_update_been_executed)());
    }
}
