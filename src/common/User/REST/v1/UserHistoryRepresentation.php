<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\User\REST\v1;

use Tuleap\User\History\HistoryEntry;

/**
 * @psalm-immutable
 */
class UserHistoryRepresentation
{
    /**
     * @var UserHistoryEntryRepresentation[]
     */
    public $entries;

    /**
     * @param UserHistoryEntryRepresentation[] $entries
     */
    private function __construct(array $entries)
    {
        $this->entries = $entries;
    }

    /**
     * @param HistoryEntry[] $history
     */
    public static function build(array $history): self
    {
        $entries = [];
        foreach ($history as $history_entry) {
            $history_entry_representation = UserHistoryEntryRepresentation::build($history_entry);

            $entries[] = $history_entry_representation;
        }

        return new self($entries);
    }
}
