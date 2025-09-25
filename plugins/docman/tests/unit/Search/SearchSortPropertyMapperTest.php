<?php
/**
 * Copyright (c) Enalean 2022 -  Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Docman\Search;

use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SearchSortPropertyMapperTest extends TestCase
{
    private SearchSortPropertyMapper $sort_property_mapper;

    #[\Override]
    protected function setUp(): void
    {
        $this->sort_property_mapper = new SearchSortPropertyMapper();
    }

    public function testItThrowsAnExceptionWhenTheGivenSortTypeCannotBeMapped(): void
    {
        $this->expectException(InvalidSortTypeException::class);

        $this->sort_property_mapper->convertToLegacySort('brrrrrr');
    }

    public function testItReturnsTheLegacySortValueAccordingTheGivenSort(): void
    {
        $legacy_sort = $this->sort_property_mapper->convertToLegacySort('desc');
        self::assertEquals(0, $legacy_sort);

        $legacy_sort = $this->sort_property_mapper->convertToLegacySort('asc');
        self::assertEquals(1, $legacy_sort);
    }
}
