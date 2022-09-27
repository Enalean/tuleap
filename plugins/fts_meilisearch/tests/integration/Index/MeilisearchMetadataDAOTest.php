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

use ParagonIE\EasyDB\EasyDB;
use Tuleap\DB\DBFactory;
use Tuleap\Search\IndexedItemFound;
use Tuleap\Search\IndexedItemsToRemove;
use Tuleap\Search\ItemToIndex;
use Tuleap\Test\PHPUnit\TestCase;

final class MeilisearchMetadataDAOTest extends TestCase
{
    private MeilisearchMetadataDAO $dao;

    protected function setUp(): void
    {
        $this->dao = new MeilisearchMetadataDAO();
    }

    public function tearDown(): void
    {
        $this->getDB()->run('DELETE FROM plugin_fts_meilisearch_item');
        $this->getDB()->run('DELETE FROM plugin_fts_meilisearch_metadata');
    }

    private function getDB(): EasyDB
    {
        return DBFactory::getMainTuleapDBConnection()->getDB();
    }

    public function testCanInsertMetadataAndRetrieveThem(): void
    {
        $item_1_id = $this->dao->saveItemMetadata(new ItemToIndex('type', 102, 'content', ['A' => 'A1']));
        $item_2_id = $this->dao->saveItemMetadata(new ItemToIndex('type', 102, 'content', ['A' => 'A2']));

        self::assertEquals(
            $item_1_id,
            $this->dao->saveItemMetadata(new ItemToIndex('type', 102, 'content updated', ['A' => 'A1']))
        );

        self::assertEquals(
            [
                new IndexedItemFound('type', ['A' => 'A2'], '... another excerpt ...'),
                new IndexedItemFound('type', ['A' => 'A1'], '... excerpt ...'),
            ],
            $this->dao->searchMatchingResultsByItemIDs([$item_2_id, $item_1_id], [$item_1_id => '... excerpt ...', $item_2_id => '... another excerpt ...'])
        );
    }

    public function testCanRemoveItemsMetadata(): void
    {
        $item_1_id = $this->dao->saveItemMetadata(new ItemToIndex('type', 102, 'content', ['A' => 'A1', 'B' => 'B']));
        $item_2_id = $this->dao->saveItemMetadata(new ItemToIndex('type', 102, 'content', ['A' => 'A2', 'B' => 'B']));
        $item_3_id = $this->dao->saveItemMetadata(new ItemToIndex('type', 103, 'content', ['A' => 'A3', 'B' => 'B']));

        self::assertEqualsCanonicalizing([$item_1_id, $item_2_id, $item_3_id], $this->dao->searchMatchingEntries(new IndexedItemsToRemove('type', ['B' => 'B'])));
        self::assertEqualsCanonicalizing([$item_1_id, $item_2_id], $this->dao->searchMatchingEntriesByProjectID(102));
        self::assertEqualsCanonicalizing([$item_2_id], $this->dao->searchMatchingEntries(new IndexedItemsToRemove('type', ['A' => 'A2'])));

        $this->dao->deleteIndexedItemsFromIDs([$item_2_id]);

        self::assertEqualsCanonicalizing([$item_1_id, $item_3_id], $this->dao->searchMatchingEntries(new IndexedItemsToRemove('type', ['B' => 'B'])));

        $this->dao->deleteIndexedItemsFromIDs([$item_1_id, $item_3_id]);

        self::assertEquals(0, $this->getDB()->single('SELECT COUNT(id) FROM plugin_fts_meilisearch_item'));
        self::assertEquals(0, $this->getDB()->single('SELECT COUNT(item_id) FROM plugin_fts_meilisearch_metadata'));
    }
}
