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

namespace Tuleap\Search;

use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class IndexedItemFoundToSearchResultTest extends TestCase
{
    public function testCanProvideASearchResultForAnIdentifiedIndexedItem(): void
    {
        $user  = UserTestBuilder::buildWithDefaults();
        $event = new IndexedItemFoundToSearchResult([1, new IndexedItemFound('type', ['a' => 'a'])], $user);

        $search_result = self::buildSearchResultEntry();
        $event->addSearchResult(1, $search_result);

        self::assertEquals([1 => $search_result], $event->search_results);
        self::assertEquals($user, $event->user);
    }

    public function testCannotProvideASearchResultAtAPriorityThatDoesNotExist(): void
    {
        $event = new IndexedItemFoundToSearchResult([1, new IndexedItemFound('type', ['a' => 'a'])], UserTestBuilder::buildWithDefaults());

        $this->expectException(\LogicException::class);
        $event->addSearchResult(2, self::buildSearchResultEntry());
    }

    public function testCannotOverwriteASearchResultAtAGivenPriority(): void
    {
        $event = new IndexedItemFoundToSearchResult([1, new IndexedItemFound('type', ['a' => 'a'])], UserTestBuilder::buildWithDefaults());

        $event->addSearchResult(1, self::buildSearchResultEntry());
        $this->expectException(\LogicException::class);
        $event->addSearchResult(1, self::buildSearchResultEntry());
    }

    private static function buildSearchResultEntry(): SearchResultEntry
    {
        return new SearchResultEntry(null, '/', 'Title', 'Color', null, null, 'icon_name', ProjectTestBuilder::aProject()->build(), []);
    }
}
