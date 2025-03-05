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

namespace Tuleap\FullTextSearchCommon\Index;

use Tuleap\Search\ItemToIndex;
use Tuleap\Search\ItemToIndexQueue;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ItemToIndexLimitedBatchQueueTest extends TestCase
{
    public function testAddsAllItemsToIndexPerBatch(): void
    {
        $inserter = new class implements InsertItemsIntoIndex
        {
            public int $nb_items = 0;
            public int $nb_calls = 0;

            public function indexItems(ItemToIndex ...$items): void
            {
                $this->nb_items += count($items);
                $this->nb_calls++;
            }
        };

        $batch_queue = new ItemToIndexLimitedBatchQueue($inserter, 2);

        $batch_queue->startBatchingItemsIntoQueue(
            function (ItemToIndexQueue $queue): void {
                $queue->addItemToQueue(new ItemToIndex('type', 102, 'content1', 'plaintext', ['A' => 'A']));
                $queue->addItemToQueue(new ItemToIndex('type', 102, 'content2', 'plaintext', ['A' => 'A']));
                $queue->addItemToQueue(new ItemToIndex('type', 102, 'content3', 'plaintext', ['A' => 'A']));
            }
        );

        self::assertEquals(3, $inserter->nb_items);
        self::assertEquals(2, $inserter->nb_calls);
    }
}
