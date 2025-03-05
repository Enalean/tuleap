<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\REST\v1\Search;

use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SearchColumnCollectionTest extends TestCase
{
    public function testEmptyCollectionByDefault(): void
    {
        $collection = new SearchColumnCollection();
        self::assertCount(0, $collection->getColumns());
    }

    public function testItReturnsOnlyColumnNames(): void
    {
        $collection = new SearchColumnCollection();
        $collection->add(SearchColumn::buildForHardcodedProperty('id', 'Id'));
        $collection->add(SearchColumn::buildForHardcodedProperty('title', 'Title'));
        $collection->add(SearchColumn::buildForSingleValueCustomProperty('field_1', 'Priority'));
        $collection->add(SearchColumn::buildForSingleValueCustomProperty('field_2', 'Comments'));
        $collection->add(SearchColumn::buildForHardcodedProperty('status', 'Status'));
        $collection->add(SearchColumn::buildForSingleValueCustomProperty('field_3', 'Audit date'));

        self::assertEquals(
            ['id', 'title', 'field_1', 'field_2', 'status', 'field_3'],
            $collection->getColumnNames(),
        );
    }

    public function testItExtractsANewCollectionWithOnlyCustomProperties(): void
    {
        $collection = new SearchColumnCollection();
        $collection->add(SearchColumn::buildForHardcodedProperty('id', 'Id'));
        $collection->add(SearchColumn::buildForHardcodedProperty('title', 'Title'));
        $collection->add(SearchColumn::buildForSingleValueCustomProperty('field_1', 'Priority'));
        $collection->add(SearchColumn::buildForSingleValueCustomProperty('field_2', 'Comments'));
        $collection->add(SearchColumn::buildForHardcodedProperty('status', 'Status'));
        $collection->add(SearchColumn::buildForSingleValueCustomProperty('field_3', 'Audit date'));

        $only_custom_collection = $collection->extractColumnsOnCustomProperties();
        self::assertEquals(
            ['field_1', 'field_2', 'field_3'],
            $only_custom_collection->getColumnNames(),
        );
    }
}
