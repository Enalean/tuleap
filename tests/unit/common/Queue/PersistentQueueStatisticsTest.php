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

use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PersistentQueueStatisticsTest extends TestCase
{
    public function testBuildStatisticsForAnEmptyQueue(): void
    {
        $stats = PersistentQueueStatistics::emptyQueue();

        self::assertEquals(0, $stats->size);
        self::assertEquals(null, $stats->oldest_message);
    }

    public function testBuildStatisticsForAQueueWithNonProcessedMessages(): void
    {
        $oldest_message = new \DateTimeImmutable('@10');
        $stats          = PersistentQueueStatistics::queueWithMessageToProcess(2, $oldest_message);

        self::assertEquals(2, $stats->size);
        self::assertSame($oldest_message, $stats->oldest_message);
    }
}
