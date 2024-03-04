<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Queue;

use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\DB\CheckThereIsAnOngoingTransactionStub;

final class PersistentQueueNoTransactionWrapperTest extends TestCase
{
    public function testPushSinglePersistentMessageIsDelegatedIfNoTransaction(): void
    {
        $topic   = 'topic';
        $content = 'content';

        $queue = $this->createMock(PersistentQueue::class);
        $queue->expects(self::once())->method('pushSinglePersistentMessage')->with($topic, $content);

        $wrapper = new PersistentQueueNoTransactionWrapper($queue, CheckThereIsAnOngoingTransactionStub::notInTransaction());
        $wrapper->pushSinglePersistentMessage($topic, $content);
    }

    public function testPushSinglePersistentMessageRaisesExceptionIfInTransaction(): void
    {
        $topic   = 'topic';
        $content = 'content';

        $queue = $this->createMock(PersistentQueue::class);
        $queue->expects(self::never())->method('pushSinglePersistentMessage');

        $this->expectException(\RuntimeException::class);

        $wrapper = new PersistentQueueNoTransactionWrapper($queue, CheckThereIsAnOngoingTransactionStub::inTransaction());
        $wrapper->pushSinglePersistentMessage($topic, $content);
    }

    public function testListenIsDelegated(): void
    {
        $queue_id = 'topic';
        $topic    = 'content';
        $callback = static function (string $value) {
        };

        $queue = $this->createMock(PersistentQueue::class);
        $queue->expects(self::once())->method('listen')->with($queue_id, $topic, $callback);

        $wrapper = new PersistentQueueNoTransactionWrapper($queue, CheckThereIsAnOngoingTransactionStub::inTransaction());
        $wrapper->listen($queue_id, $topic, $callback);
    }

    public function testGetStatisticsIsDelegated(): void
    {
        $statistics = PersistentQueueStatistics::emptyQueue();

        $queue = $this->createMock(PersistentQueue::class);
        $queue->expects(self::once())->method('getStatistics')->willReturn($statistics);

        $wrapper = new PersistentQueueNoTransactionWrapper($queue, CheckThereIsAnOngoingTransactionStub::inTransaction());
        self::assertSame(
            $statistics,
            $wrapper->getStatistics(),
        );
    }
}
