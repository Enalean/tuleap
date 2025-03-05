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

namespace Tuleap\FullTextSearchDB\Index;

use ParagonIE\EasyDB\EasyDB;
use Tuleap\DB\DBFactory;
use Tuleap\FullTextSearchCommon\Index\PlaintextItemToIndex;
use Tuleap\FullTextSearchCommon\Index\SearchResultPage;
use Tuleap\Search\IndexedItemFound;
use Tuleap\Search\IndexedItemsToRemove;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SearchDAOTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private SearchDAO $dao;

    protected function setUp(): void
    {
        $this->dao = new SearchDAO();
    }

    public function tearDown(): void
    {
        $this->getDB()->run('DELETE FROM plugin_fts_db_search');
        $this->getDB()->run('DELETE FROM plugin_fts_db_metadata');
    }

    private function getDB(): EasyDB
    {
        return DBFactory::getMainTuleapDBConnection()->getDB();
    }

    public function testIndexItems(): void
    {
        $this->dao->indexItems(
            new PlaintextItemToIndex('type', 102, 'content A', ['A' => 'A', 'B' => 'B']),
            new PlaintextItemToIndex('type', 102, 'content B', ['A' => 'A', 'B' => 'B2']),
            new PlaintextItemToIndex('type', 102, 'AA', ['A' => 'A', 'B' => 'B3']), // Small content, not indexed
        );

        $result = $this->getDB()->run('SELECT content FROM plugin_fts_db_search');

        self::assertEqualsCanonicalizing([['content' => 'content A'], ['content' => 'content B']], $result);
    }

    public function testUpdatesAlreadyIndexedItem(): void
    {
        $this->dao->indexItems(new PlaintextItemToIndex('type', 102, 'content', ['A' => 'A', 'B' => 'B']));
        $this->dao->indexItems(new PlaintextItemToIndex('type', 102, 'content updated', ['A' => 'A', 'B' => 'B']));

        $result = $this->getDB()->run('SELECT content FROM plugin_fts_db_search');

        self::assertEqualsCanonicalizing([['content' => 'content updated']], $result);

        // Drop the entry if content becomes empty
        $this->dao->indexItems(new PlaintextItemToIndex('type', 102, '   ', ['A' => 'A', 'B' => 'B']));

        $result = $this->getDB()->run('SELECT content FROM plugin_fts_db_search');

        self::assertEmpty($result);
    }

    public function testSearchIndexedItems(): void
    {
        $this->dao->indexItems(
            new PlaintextItemToIndex('type A', 102, 'content content A value', ['A' => 'A', 'B' => 'B']),
            new PlaintextItemToIndex('type B', 102, 'content B value', ['A' => 'A', 'B' => 'B2']),
        );

        $this->searchMostRelevantItem();
        $this->searchMostRecentItemBetweenItemsWithEquivalentRelevance();
        $this->searchForSomethingWithNoMatch();
    }

    private function searchMostRelevantItem(): void
    {
        self::assertEquals(
            SearchResultPage::page(2, [new IndexedItemFound('type A', ['A' => 'A', 'B' => 'B'], null)]),
            $this->dao->searchItems('content', 1, 0),
        );
    }

    private function searchMostRecentItemBetweenItemsWithEquivalentRelevance(): void
    {
        self::assertEquals(
            SearchResultPage::page(2, [new IndexedItemFound('type B', ['A' => 'A', 'B' => 'B2'], null)]),
            $this->dao->searchItems('value', 1, 0),
        );
    }

    private function searchForSomethingWithNoMatch(): void
    {
        self::assertEquals(
            SearchResultPage::noHits(),
            $this->dao->searchItems('donotexist', 50, 0)
        );
    }

    public function testItemsRemoval(): void
    {
        $this->dao->indexItems(new PlaintextItemToIndex('type', 102, 'content', ['A' => 'A']));

        // Type does not match, nothing should be deleted
        $this->dao->deleteIndexedItems(new IndexedItemsToRemove('anothertype', ['A' => 'A', 'B' => 'B']));
        self::assertEquals(1, $this->getDB()->single('SELECT COUNT(id) FROM plugin_fts_db_search'));
        self::assertEquals(1, $this->getDB()->single('SELECT COUNT(search_id) FROM plugin_fts_db_metadata'));

        // No metadata match, nothing should be deleted
        $this->dao->deleteIndexedItems(new IndexedItemsToRemove('type', ['A' => 'A2']));
        self::assertEquals(1, $this->getDB()->single('SELECT COUNT(id) FROM plugin_fts_db_search'));
        self::assertEquals(1, $this->getDB()->single('SELECT COUNT(search_id) FROM plugin_fts_db_metadata'));

        // No all metadata match, nothing should be deleted
        $this->dao->deleteIndexedItems(new IndexedItemsToRemove('type', ['A' => 'A', 'B' => 'B1']));
        self::assertEquals(1, $this->getDB()->single('SELECT COUNT(id) FROM plugin_fts_db_search'));
        self::assertEquals(1, $this->getDB()->single('SELECT COUNT(search_id) FROM plugin_fts_db_metadata'));

        // Type and metadata match, entry should be deleted
        $this->dao->deleteIndexedItems(new IndexedItemsToRemove('type', ['A' => 'A']));
        self::assertEquals(0, $this->getDB()->single('SELECT COUNT(id) FROM plugin_fts_db_search'));
        self::assertEquals(0, $this->getDB()->single('SELECT COUNT(search_id) FROM plugin_fts_db_metadata'));
    }

    public function testItemsRemoveWithProject(): void
    {
        $this->dao->indexItems(new PlaintextItemToIndex('type', 102, 'content', ['A' => 'A']));
        $this->dao->indexItems(new PlaintextItemToIndex('type2', 102, 'content', ['A' => 'A']));
        $this->dao->indexItems(new PlaintextItemToIndex('type', 103, 'content', ['A' => 'A1']));

        $this->dao->deleteIndexedItemsPerProjectID(102);
        self::assertEquals(1, $this->getDB()->single('SELECT COUNT(id) FROM plugin_fts_db_search'));
        self::assertEquals(1, $this->getDB()->single('SELECT COUNT(search_id) FROM plugin_fts_db_metadata'));

        $this->dao->deleteIndexedItemsPerProjectID(103);
        self::assertEquals(0, $this->getDB()->single('SELECT COUNT(id) FROM plugin_fts_db_search'));
        self::assertEquals(0, $this->getDB()->single('SELECT COUNT(search_id) FROM plugin_fts_db_metadata'));
    }
}
