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

use Meilisearch\Endpoints\Indexes;
use Meilisearch\Search\SearchResult;
use Tuleap\FullTextSearchCommon\Index\DeleteIndexedItems;
use Tuleap\FullTextSearchCommon\Index\InsertPlaintextItemsIntoIndex;
use Tuleap\FullTextSearchCommon\Index\PlaintextItemToIndex;
use Tuleap\FullTextSearchCommon\Index\SearchIndexedItem;
use Tuleap\FullTextSearchCommon\Index\SearchResultPage;
use Tuleap\Search\IndexedItemsToRemove;

final class MeilisearchHandler implements SearchIndexedItem, InsertPlaintextItemsIntoIndex, DeleteIndexedItems
{
    private const ID_FIELD      = 'id';
    private const CONTENT_FIELD = 'content';

    public function __construct(private Indexes $client_index, private MeilisearchMetadataDAO $metadata_dao)
    {
    }

    #[\Override]
    public function indexItems(PlaintextItemToIndex ...$items): void
    {
        $documents_to_add    = [];
        $documents_to_remove = [];
        foreach ($items as $item) {
            if (trim($item->content) === '') {
                $documents_to_remove = [...$documents_to_remove, ...array_values($this->metadata_dao->searchMatchingEntries($item))];
                continue;
            }
            $documents_to_add[] = $this->mapItemToIndexToMeilisearchDocument($item);
        }
        if (count($documents_to_remove) > 0) {
            $this->client_index->deleteDocuments($documents_to_remove);
            $this->metadata_dao->deleteIndexedItemsFromIDs($documents_to_remove);
        }
        if (count($documents_to_add) > 0) {
            $this->client_index->addDocuments($documents_to_add);
        }
    }

    /**
     * @return array{id:string,content:string}
     */
    private function mapItemToIndexToMeilisearchDocument(PlaintextItemToIndex $item): array
    {
        return [
            self::ID_FIELD      => $this->metadata_dao->saveItemMetadata($item),
            self::CONTENT_FIELD => $item->content,
        ];
    }

    #[\Override]
    public function deleteIndexedItems(IndexedItemsToRemove $items_to_remove): void
    {
        $this->deleteIndexedItemsByIDs(
            $this->metadata_dao->searchMatchingEntries($items_to_remove)
        );
    }

    #[\Override]
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

    #[\Override]
    public function searchItems(string $keywords, int $limit, int $offset): SearchResultPage
    {
        $parameters = [
            'attributesToRetrieve' => [self::ID_FIELD],
            'limit'                => $limit,
            'offset'               => $offset,
            'attributesToCrop'     => [self::CONTENT_FIELD],
            'cropLength'           => 20,
        ];

        $meilisearch_search_result = $this->client_index->search($keywords, $parameters);
        assert($meilisearch_search_result instanceof SearchResult);

        $estimated_total_hits = $meilisearch_search_result->getEstimatedTotalHits();
        if ($estimated_total_hits <= 0) {
            return SearchResultPage::noHits();
        }

        $found_item_ids        = [];
        $cropped_content_by_id = [];
        foreach ($meilisearch_search_result->getHits() as $hit) {
            $found_item_ids[] = $hit[self::ID_FIELD];
            if (isset($hit['_formatted'][self::CONTENT_FIELD])) {
                $cropped_content_by_id[$hit[self::ID_FIELD]] = $hit['_formatted'][self::CONTENT_FIELD];
            }
        }

        return SearchResultPage::page(
            $estimated_total_hits,
            $this->metadata_dao->searchMatchingResultsByItemIDs($found_item_ids, $cropped_content_by_id)
        );
    }
}
