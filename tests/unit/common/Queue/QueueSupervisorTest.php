<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

use ColinODell\PsrTestLogger\TestLogger;
use Tuleap\Test\PHPUnit\TestCase;

final class QueueSupervisorTest extends TestCase
{
    private TestLogger $logger;

    protected function setUp(): void
    {
        $this->logger = new TestLogger();
    }

    public function testNoWarningWhenQueueIsEmpty(): void
    {
        $supervisor = new QueueSupervisor(
            self::buildQueueWithStatistics(PersistentQueueStatistics::emptyQueue()),
            $this->logger
        );
        $supervisor->warnWhenThereIsTooMuchDelayInWorkerEventsProcessing(new \DateTimeImmutable('@10'));

        self::assertFalse($this->logger->hasWarningRecords());
    }

    public function testNoWarningWhenOldestMessageIsNotTooOld(): void
    {
        $supervisor = new QueueSupervisor(
            self::buildQueueWithStatistics(PersistentQueueStatistics::queueWithMessageToProcess(1, new \DateTimeImmutable('@5'))),
            $this->logger
        );
        $supervisor->warnWhenThereIsTooMuchDelayInWorkerEventsProcessing(new \DateTimeImmutable('@10'));

        self::assertFalse($this->logger->hasWarningRecords());
    }

    public function testWarnsWhenOldestMessageIsOld(): void
    {
        $supervisor = new QueueSupervisor(
            self::buildQueueWithStatistics(PersistentQueueStatistics::queueWithMessageToProcess(1, new \DateTimeImmutable('@5'))),
            $this->logger
        );
        $supervisor->warnWhenThereIsTooMuchDelayInWorkerEventsProcessing(new \DateTimeImmutable('@3600'));

        self::assertTrue($this->logger->hasWarningRecords());
    }

    private static function buildQueueWithStatistics(PersistentQueueStatistics $stats): PersistentQueue
    {
        return new class ($stats) implements PersistentQueue {
            public function __construct(private PersistentQueueStatistics $statistics)
            {
            }

            public function pushSinglePersistentMessage(string $topic, $content): void
            {
            }

            public function listen($queue_id, $topic, callable $callback): void
            {
            }

            public function getStatistics(): PersistentQueueStatistics
            {
                return $this->statistics;
            }
        };
    }
}
