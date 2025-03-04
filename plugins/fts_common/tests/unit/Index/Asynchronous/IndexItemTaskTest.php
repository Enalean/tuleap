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

namespace Tuleap\FullTextSearchCommon\Index\Asynchronous;

use Psr\Log\NullLogger;
use Tuleap\Queue\WorkerEvent;
use Tuleap\Queue\WorkerEventContent;
use Tuleap\Search\ItemToIndex;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class IndexItemTaskTest extends TestCase
{
    public function testInstantiatesTaskToQueueFromItemToIndex(): void
    {
        $item_to_index = new ItemToIndex('type', 102, 'content', 'plaintext', ['A' => 'A']);

        $task = IndexItemTask::fromItemToIndex($item_to_index);

        self::assertEquals(
            ['type' => 'type', 'project_id' => 102, 'content' => 'content', 'content_type' => 'plaintext', 'metadata' => ['A' => 'A']],
            $task->getPayload()
        );
    }

    public function testBuildsItemToIndexFromWorkerEvent(): void
    {
        $worker_event = new WorkerEvent(
            new NullLogger(),
            new WorkerEventContent(
                'tuleap.fts.index-item',
                ['type' => 'type', 'project_id' => 102, 'content' => 'content', 'content_type' => 'plaintext', 'metadata' => ['A' => 'A']]
            )
        );

        self::assertEquals(
            new ItemToIndex('type', 102, 'content', 'plaintext', ['A' => 'A']),
            IndexItemTask::parseWorkerEventIntoItemToIndexWhenPossible($worker_event)
        );
    }

    public function testSkipsWorkerEventWithNotExpectedType(): void
    {
        $worker_event = new WorkerEvent(
            new NullLogger(),
            new WorkerEventContent(
                'something.else',
                ['type' => 'type', 'content' => 'content', 'metadata' => ['A' => 'A']]
            )
        );

        self::assertNull(
            IndexItemTask::parseWorkerEventIntoItemToIndexWhenPossible($worker_event)
        );
    }

    public function testSkipsWorkerEventWithUnexpectedPayload(): void
    {
        $worker_event = new WorkerEvent(
            new NullLogger(),
            new WorkerEventContent(
                'tuleap.fts.index-item',
                []
            )
        );

        self::assertNull(
            IndexItemTask::parseWorkerEventIntoItemToIndexWhenPossible($worker_event)
        );
    }
}
