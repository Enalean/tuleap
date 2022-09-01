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

namespace Tuleap\FullTextSearchDB\Index\Adapter;

use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\EasyStatement;
use Tuleap\DB\DataAccessObject;
use Tuleap\FullTextSearchDB\Index\InsertItemIntoIndex;
use Tuleap\Search\ItemToIndex;

final class SearchDAO extends DataAccessObject implements InsertItemIntoIndex
{
    public function indexItem(ItemToIndex $item): void
    {
        $this->getDB()->tryFlatTransaction(
            function (EasyDB $db) use ($item): void {
                $existing_entries = $this->searchMatchingEntries($item);
                if (count($existing_entries) === 0) {
                    $this->createNewEntry($item);
                    return;
                }

                foreach ($existing_entries as $existing_entry) {
                    $db->run('UPDATE plugin_fts_db_search SET content = ? WHERE id = ?', $item->content, $existing_entry['id']);
                }
            }
        );
    }

    /**
     * @psalm-return array{id: int}[]
     */
    private function searchMatchingEntries(ItemToIndex $item_to_index): array
    {
        $metadata_statement_filter = EasyStatement::open();

        foreach ($item_to_index->metadata as $name => $value) {
            $metadata_statement_filter->andWith(
                'id IN (SELECT search_id FROM plugin_fts_db_metadata WHERE name = ? AND value = ?)',
                $name,
                $value
            );
        }

        return $this->getDB()->safeQuery(
            "SELECT id FROM plugin_fts_db_search WHERE type=? AND $metadata_statement_filter",
            array_merge([$item_to_index->type], $metadata_statement_filter->values())
        );
    }

    private function createNewEntry(ItemToIndex $item): void
    {
        $id = $this->getDB()->insertReturnId('plugin_fts_db_search', ['type' => $item->type, 'content' => $item->content]);
        foreach ($item->metadata as $name => $value) {
            $this->getDB()->insert('plugin_fts_db_metadata', ['search_id' => $id, 'name' => $name, 'value' => $value]);
        }
    }
}
