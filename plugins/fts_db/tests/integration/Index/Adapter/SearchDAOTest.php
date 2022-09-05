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
use Tuleap\DB\DBFactory;
use Tuleap\FullTextSearchDB\Index\SearchResultPage;
use Tuleap\Search\IndexedItemFound;
use Tuleap\Search\ItemToIndex;

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
        $this->dao->indexItem(new ItemToIndex('type', 'content A', ['A' => 'A', 'B' => 'B']));
        $this->dao->indexItem(new ItemToIndex('type', 'content B', ['A' => 'A', 'B' => 'B2']));

        $result = $this->getDB()->run('SELECT content FROM plugin_fts_db_search');

        self::assertEqualsCanonicalizing([['content' => 'content A'], ['content' => 'content B']], $result);
    }

    public function testUpdatesAlreadyIndexedItem(): void
    {
        $this->dao->indexItem(new ItemToIndex('type', 'content', ['A' => 'A', 'B' => 'B']));
        $this->dao->indexItem(new ItemToIndex('type', 'content updated', ['A' => 'A', 'B' => 'B']));

        $result = $this->getDB()->run('SELECT content FROM plugin_fts_db_search');

        self::assertEqualsCanonicalizing([['content' => 'content updated']], $result);
    }

    public function testSearchIndexedItems(): void
    {
        $this->dao->indexItem(new ItemToIndex('type A', 'content content A value', ['A' => 'A', 'B' => 'B']));
        $this->dao->indexItem(new ItemToIndex('type B', 'content B value', ['A' => 'A', 'B' => 'B2']));

        $this->searchMostRelevantItem();
        $this->searchMostRecentItemBetweenItemsWithEquivalentRelevance();
        $this->searchForSomethingWithNoMatch();
    }

    private function searchMostRelevantItem(): void
    {
        self::assertEquals(
            SearchResultPage::page(2, [new IndexedItemFound('type A', ['A' => 'A', 'B' => 'B'])]),
            $this->dao->searchItems('content', 1, 0),
        );
    }

    private function searchMostRecentItemBetweenItemsWithEquivalentRelevance(): void
    {
        self::assertEquals(
            SearchResultPage::page(2, [new IndexedItemFound('type B', ['A' => 'A', 'B' => 'B2'])]),
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
}
