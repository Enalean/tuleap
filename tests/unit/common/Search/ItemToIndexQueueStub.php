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

final class ItemToIndexQueueStub implements ItemToIndexQueue
{
    /**
     * @param callable(ItemToIndex):void $callback
     */
    private function __construct(private $callback)
    {
    }

    #[\Override]
    public function addItemToQueue(ItemToIndex $item_to_index): void
    {
        ($this->callback)($item_to_index);
    }

    public static function noop(): self
    {
        return new self(static function (ItemToIndex $item_to_index): void {
            // Do nothing
        });
    }

    /**
     * @param callable(ItemToIndex):void $callback
     */
    public static function withCallback($callback): self
    {
        return new self($callback);
    }
}
