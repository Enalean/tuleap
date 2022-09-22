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

namespace Tuleap\FullTextSearchMeilisearch\Index;

use MeiliSearch\Endpoints\Indexes;
use MeiliSearch\Search\SearchResult;
use Tuleap\FullTextSearchCommon\Index\DeleteIndexedItems;
use Tuleap\FullTextSearchCommon\Index\InsertItemsIntoIndex;
use Tuleap\FullTextSearchCommon\Index\SearchIndexedItem;
use Tuleap\FullTextSearchCommon\Index\SearchResultPage;
use Tuleap\Search\IndexedItemsToRemove;
use Tuleap\Search\ItemToIndex;

final class MeilisearchHandler implements SearchIndexedItem, InsertItemsIntoIndex, DeleteIndexedItems
{
    public function __construct(private Indexes $client_index, private MeilisearchMetadataDAO $metadata_dao)
    {
    }

    public function indexItems(ItemToIndex ...$items): void
    {
        $documents = [];
        foreach ($items as $item) {
            $documents[] = $this->mapItemToIndexToMeilisearchDocument($item);
        }
        $this->client_index->addDocuments($documents);
    }

    /**
     * @return array{id:int,content:string}
     */
    private function mapItemToIndexToMeilisearchDocument(ItemToIndex $item): array
    {
        return [
            'id' => $this->metadata_dao->saveItemMetadata($item),
            'content' => $item->content,
        ];
    }

    public function deleteIndexedItems(IndexedItemsToRemove $items_to_remove): void
    {
        $this->deleteIndexedItemsByIDs(
            $this->metadata_dao->searchMatchingEntries($items_to_remove)
        );
    }

    public function deleteIndexedItemsPerProjectID(int $project_id): void
    {
        $this->deleteIndexedItemsByIDs(
            $this->metadata_dao->searchMatchingEntriesByProjectID($project_id)
        );
    }

    private function deleteIndexedItemsByIDs(array $item_ids_to_remove): void
    {
        if (count($item_ids_to_remove) === 0) {
            return;
        }

        $this->client_index->deleteDocuments($item_ids_to_remove);
        $this->metadata_dao->deleteIndexedItemsFromIDs($item_ids_to_remove);
    }

    public function searchItems(string $keywords, int $limit, int $offset): SearchResultPage
    {
        $meilisearch_search_result = $this->client_index->search($keywords, ['attributesToRetrieve' => ['id'], 'limit' => $limit, 'offset' => $offset]);
        assert($meilisearch_search_result instanceof SearchResult);

        $estimated_total_hits = $meilisearch_search_result->getEstimatedTotalHits();
        if ($estimated_total_hits <= 0) {
            return SearchResultPage::noHits();
        }

        $found_item_ids = [];
        foreach ($meilisearch_search_result->getHits() as $hit) {
            $found_item_ids[] = $hit['id'];
        }

        return SearchResultPage::page(
            $estimated_total_hits,
            $this->metadata_dao->searchMatchingResultsByItemIDs($found_item_ids)
        );
    }
}
