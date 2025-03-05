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

namespace Tuleap\Search;

use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EventDispatcherStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ItemToIndexQueueEventBasedTest extends TestCase
{
    public function testDispatchEvent(): void
    {
        $item_to_index = new ItemToIndex('type', 102, 'content', 'plaintext', ['A' => 'A']);

        $event_dispatcher = EventDispatcherStub::withCallback(
            static function (ItemToIndex $received) use ($item_to_index): ItemToIndex {
                self::assertSame($item_to_index, $received);
                return $received;
            }
        );
        $queue            = new ItemToIndexQueueEventBased($event_dispatcher);

        $queue->addItemToQueue($item_to_index);

        self::assertEquals(1, $event_dispatcher->getCallCount());
    }
}
