<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\Tests\Stub;

use Tuleap\Docman\Log\LogEntry;

class StoredLogStub implements \Tuleap\Docman\Log\IRetrieveStoredLog
{
    private function __construct(private array $storage)
    {
    }

    public static function buildForItem(\Docman_Item $item, LogEntry ...$entries): self
    {
        return new self([
            $item->getId() => array_map(
                static fn(LogEntry $entry): array => [
                    'time'      => $entry->when->getTimestamp(),
                    'group_id'  => $entry->project_id,
                    'user_id'   => $entry->who->getId(),
                    'type'      => $entry->type,
                    'old_value' => $entry->old_value,
                    'new_value' => $entry->new_value,
                    'field'     => $entry->field,
                ],
                $entries
            ),
        ]);
    }

    #[\Override]
    public function searchByItemIdOrderByTimestamp(int $item_id): array
    {
        if (! isset($this->storage[$item_id])) {
            throw new \LogicException('Unknown item id in storage');
        }

        return $this->storage[$item_id];
    }

    #[\Override]
    public function paginatedSearchByItemIdOrderByTimestamp(int $item_id, int $limit, int $offset): array
    {
        if (! isset($this->storage[$item_id])) {
            throw new \LogicException('Unknown item id in storage');
        }

        return array_slice($this->storage[$item_id], $offset, $limit);
    }

    #[\Override]
    public function countByItemId(int $item_id): int
    {
        if (! isset($this->storage[$item_id])) {
            throw new \LogicException('Unknown item id in storage');
        }

        return count($this->storage[$item_id]);
    }
}
