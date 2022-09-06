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
use Tuleap\FullTextSearchDB\Index\DeleteIndexedItems;
use Tuleap\FullTextSearchDB\Index\InsertItemIntoIndex;
use Tuleap\FullTextSearchDB\Index\SearchIndexedItem;
use Tuleap\FullTextSearchDB\Index\SearchResultPage;
use Tuleap\Search\IndexedItemFound;
use Tuleap\Search\IndexedItemsToRemove;
use Tuleap\Search\ItemToIndex;

final class SearchDAO extends DataAccessObject implements InsertItemIntoIndex, SearchIndexedItem, DeleteIndexedItems
{
    private const DEFAULT_MIN_LENGTH_FOR_FTS = 3;

    public function indexItem(ItemToIndex $item): void
    {
        if (mb_strlen(trim($item->content)) < self::DEFAULT_MIN_LENGTH_FOR_FTS) {
            $this->deleteIndexedItems(new IndexedItemsToRemove($item->type, $item->metadata));
            return;
        }
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
        $metadata_statement_filter = $this->getFilterSearchIDFromMetadata($item_to_index->metadata);

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

    public function searchItems(string $keywords, int $limit, int $offset): SearchResultPage
    {
        return $this->getDB()->tryFlatTransaction(
            function () use ($keywords, $limit, $offset): SearchResultPage {
                $match_statement = EasyStatement::open()->with('MATCH (plugin_fts_db_search.content) AGAINST(? IN NATURAL LANGUAGE MODE)', $keywords);

                $nb_hits = $this->countSearchHits($match_statement);
                if ($nb_hits === 0) {
                    return SearchResultPage::noHits();
                }

                return SearchResultPage::page(
                    $nb_hits,
                    $this->searchMatchingResults($match_statement, $limit, $offset)
                );
            }
        );
    }

    /**
     * @psalm-return positive-int|0
     */
    private function countSearchHits(EasyStatement $match_statement): int
    {
        return $this->getDB()->single(
            "SELECT COUNT(plugin_fts_db_search.id) FROM plugin_fts_db_search WHERE $match_statement",
            $match_statement->values()
        );
    }

    /**
     * @return IndexedItemFound[]
     */
    private function searchMatchingResults(EasyStatement $match_statement, int $limit, int $offset): array
    {
        /** @psalm-var array{id: int, type: non-empty-string}[] $rows */
        $rows = $this->getDB()->safeQuery(
            "SELECT plugin_fts_db_search.id, plugin_fts_db_search.type
                FROM plugin_fts_db_search
                WHERE $match_statement
                ORDER BY $match_statement DESC, plugin_fts_db_search.id DESC
                LIMIT ? OFFSET ?",
            array_merge($match_statement->values(), $match_statement->values(), [$limit, $offset])
        );

        $search_id_matches = array_map(
            static fn (array $row): int => $row['id'],
            $rows
        );

        $statement_metadata_filter = EasyStatement::open()->in(
            'plugin_fts_db_metadata.search_id IN (?*)',
            $search_id_matches,
        );
        /** @psalm-var array<int,array{name: non-empty-string, value: string}[]> $metadata_rows_by_id */
        $metadata_rows_by_id = $this->getDB()->safeQuery(
            "SELECT plugin_fts_db_metadata.search_id, plugin_fts_db_metadata.name, plugin_fts_db_metadata.value
            FROM plugin_fts_db_metadata
            WHERE $statement_metadata_filter",
            $statement_metadata_filter->values(),
            \PDO::FETCH_GROUP | \PDO::FETCH_ASSOC
        );

        $results = [];
        foreach ($rows as $row) {
            $metadata_key_value = [];
            foreach (($metadata_rows_by_id[$row['id']] ?? []) as $metadata) {
                $metadata_key_value[$metadata['name']] = $metadata['value'];
            }

            if (count($metadata_key_value) === 0) {
                continue;
            }

            $results[] = new IndexedItemFound($row['type'], $metadata_key_value);
        }

        return $results;
    }

    public function deleteIndexedItems(IndexedItemsToRemove $items_to_remove): void
    {
        $metadata_statement_filter = $this->getFilterSearchIDFromMetadata($items_to_remove->metadata);

        $this->getDB()->safeQuery(
            "DELETE plugin_fts_db_search, plugin_fts_db_metadata
                    FROM plugin_fts_db_search
                    JOIN plugin_fts_db_metadata ON (plugin_fts_db_metadata.search_id = plugin_fts_db_search.id)
                    WHERE type=? AND $metadata_statement_filter",
            array_merge([$items_to_remove->type], $metadata_statement_filter->values())
        );
    }

    /**
     * @param non-empty-array<non-empty-string,string> $metadata
     */
    private function getFilterSearchIDFromMetadata(array $metadata): EasyStatement
    {
        $metadata_statement_filter = EasyStatement::open();

        foreach ($metadata as $name => $value) {
            $metadata_statement_filter->andWith(
                'plugin_fts_db_search.id IN (SELECT search_id FROM plugin_fts_db_metadata WHERE name = ? AND value = ?)',
                $name,
                $value
            );
        }

        return $metadata_statement_filter;
    }
}
