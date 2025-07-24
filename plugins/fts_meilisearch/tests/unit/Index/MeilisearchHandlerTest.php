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
use Tuleap\FullTextSearchCommon\Index\PlaintextItemToIndex;
use Tuleap\FullTextSearchCommon\Index\SearchResultPage;
use Tuleap\Search\IndexedItemFound;
use Tuleap\Search\IndexedItemsToRemove;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MeilisearchHandlerTest extends TestCase
{
    /**
     * @var Indexes&\PHPUnit\Framework\MockObject\MockObject
     */
    private $client_index;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&MeilisearchMetadataDAO
     */
    private $metadata_dao;
    private MeilisearchHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->client_index = $this->createMock(Indexes::class);
        $this->metadata_dao = $this->createMock(MeilisearchMetadataDAO::class);

        $this->handler = new MeilisearchHandler($this->client_index, $this->metadata_dao);
    }

    public function testIndexesItems(): void
    {
        $item_1 = new PlaintextItemToIndex('type_1', 102, 'content', ['A' => 'A']);
        $item_2 = new PlaintextItemToIndex('type_2', 102, 'content', ['A' => 'A']);

        $this->metadata_dao->expects($this->exactly(2))->method('saveItemMetadata')->willReturn('uuid1', 'uuid2');
        $this->client_index->expects($this->once())->method('addDocuments');

        $this->handler->indexItems($item_1, $item_2);
    }

    public function testDoesNotIndexItemsWithEmptyContent(): void
    {
        $item_1 = new PlaintextItemToIndex('type_1', 102, '', ['A' => 'A']);
        $item_2 = new PlaintextItemToIndex('type_2', 102, '', ['A' => 'B']);

        $this->metadata_dao->expects($this->exactly(2))->method('searchMatchingEntries')->willReturnOnConsecutiveCalls([], ['uuid2']);
        $this->metadata_dao->expects($this->once())->method('deleteIndexedItemsFromIDs')->with(['uuid2']);
        $this->client_index->expects($this->once())->method('deleteDocuments');

        $this->handler->indexItems($item_1, $item_2);
    }

    public function testDeletesItem(): void
    {
        $this->metadata_dao->method('searchMatchingEntries')->willReturn(['uuid1', 'uuid2']);

        $this->client_index->expects($this->once())->method('deleteDocuments');
        $this->metadata_dao->expects($this->once())->method('deleteIndexedItemsFromIDs');

        $this->handler->deleteIndexedItems(new IndexedItemsToRemove('type', ['A' => 'A']));
    }

    public function testDeletesItemAssociatedToAProject(): void
    {
        $this->metadata_dao->method('searchMatchingEntriesByProjectID')->willReturn(['uuid1', 'uuid2']);

        $this->client_index->expects($this->once())->method('deleteDocuments');
        $this->metadata_dao->expects($this->once())->method('deleteIndexedItemsFromIDs');

        $this->handler->deleteIndexedItemsPerProjectID(102);
    }

    public function testDoesNothingWhenNoEntriesMatchItemToDelete(): void
    {
        $this->metadata_dao->method('searchMatchingEntries')->willReturn([]);

        $this->client_index->expects($this->never())->method('deleteDocuments');
        $this->metadata_dao->expects($this->never())->method('deleteIndexedItemsFromIDs');

        $this->handler->deleteIndexedItems(new IndexedItemsToRemove('404', ['A' => 'A']));
    }

    public function testSearchItems(): void
    {
        $this->client_index
            ->method('search')
            ->with(
                'keywords',
                [
                    'attributesToRetrieve' => ['id'],
                    'limit'                => 2,
                    'offset'               => 0,
                    'attributesToCrop'     => ['content'],
                    'cropLength'           => 20,
                ]
            )->willReturn(
                new SearchResult(
                    [
                        'hits'               => [['id' => 1], ['id' => 2]],
                        'estimatedTotalHits' => 99,
                        'limit'              => 2,
                        'offset'             => 0,
                        'processingTimeMs'   => 2,
                        'query'              => '',
                    ]
                )
            );

        $found_items = [new IndexedItemFound('type', ['A' => '1'], null),
            new IndexedItemFound('type', ['A' => '2'], null),
        ];
        $this->metadata_dao->method('searchMatchingResultsByItemIDs')->willReturn($found_items);

        self::assertEquals(
            SearchResultPage::page(99, $found_items),
            $this->handler->searchItems('keywords', 2, 0),
        );
    }

    public function testSearchItemsWithCroppedContent(): void
    {
        $this->client_index
            ->method('search')
            ->with(
                'keywords',
                [
                    'attributesToRetrieve' => ['id'],
                    'limit'                => 2,
                    'offset'               => 0,
                    'attributesToCrop'     => ['content'],
                    'cropLength'           => 20,
                ]
            )->willReturn(
                new SearchResult(
                    [
                        'hits'               => [
                            [
                                'id'         => 1,
                                '_formatted' => [
                                    'content' => '... excerpt ...',
                                ],
                            ],
                            [
                                'id'         => 2,
                                '_formatted' => [
                                    'content' => '... another excerpt ...',
                                ],
                            ],
                        ],
                        'estimatedTotalHits' => 99,
                        'limit'              => 2,
                        'offset'             => 0,
                        'processingTimeMs'   => 2,
                        'query'              => '',
                    ]
                )
            );

        $found_items = [
            new IndexedItemFound('type', ['A' => '1'], '... excerpt ...'),
            new IndexedItemFound('type', ['A' => '2'], '... another excerpt ...'),
        ];
        $this->metadata_dao->method('searchMatchingResultsByItemIDs')->willReturn($found_items);

        self::assertEquals(
            SearchResultPage::page(99, $found_items),
            $this->handler->searchItems('keywords', 2, 0),
        );
    }

    public function testSearchItemsButNoMatchFound(): void
    {
        $this->client_index->method('search')->willReturn(
            new SearchResult(
                [
                    'hits'               => [],
                    'estimatedTotalHits' => 0,
                    'limit'              => 50,
                    'offset'             => 0,
                    'processingTimeMs'   => 2,
                    'query'              => '',
                ]
            )
        );

        self::assertEquals(
            SearchResultPage::noHits(),
            $this->handler->searchItems('keywords', 50, 0),
        );
    }
}
