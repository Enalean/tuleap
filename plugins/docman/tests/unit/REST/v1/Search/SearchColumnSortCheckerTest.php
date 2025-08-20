<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Docman\REST\v1\Search;

use Docman_Metadata;
use Docman_ReportColumn;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SearchColumnSortCheckerTest extends TestCase
{
    /**
     * @throws ColumnCannotBeSortedException
     */
    public function testItThrowsAnExceptionWhenTheSortedColumnIsLocation(): void
    {
        $sort_representation        = new SearchSortRepresentation();
        $sort_representation->name  = 'location';
        $sort_representation->order = 'asc';

        $column_report = new Docman_ReportColumn(new Docman_Metadata());

        $this->expectException(ColumnCannotBeSortedException::class);

        SearchColumnSortChecker::checkColumnCanBeSorted($sort_representation, $column_report);
    }

    /**
     * @throws ColumnCannotBeSortedException
     */
    public function testItThrowsAnExceptionWhenTheSortedColumnIsAMetadataWhichAllowMultiValueList(): void
    {
        $sort_representation        = new SearchSortRepresentation();
        $sort_representation->name  = 'field_484';
        $sort_representation->order = 'asc';

        $property                          = new Docman_Metadata();
        $property->name                    = 'Multi List Value';
        $property->label                   = 'field_484';
        $property->isMultipleValuesAllowed = true;

        $column_report = new Docman_ReportColumn($property);

        $this->expectException(ColumnCannotBeSortedException::class);

        SearchColumnSortChecker::checkColumnCanBeSorted($sort_representation, $column_report);
    }

    /**
     * @throws ColumnCannotBeSortedException
     */
    public function testItThrowsAnExceptionWhenTheSortedColumnHasNoMetadata(): void
    {
        $sort_representation        = new SearchSortRepresentation();
        $sort_representation->name  = 'field_484';
        $sort_representation->order = 'asc';

        $column_report = new Docman_ReportColumn(null);

        $this->expectException(ColumnCannotBeSortedException::class);

        SearchColumnSortChecker::checkColumnCanBeSorted($sort_representation, $column_report);
    }

    /**
     * @throws ColumnCannotBeSortedException
     */
    public function testTheProvidedColumnCanBeSorted(): void
    {
        $sort_representation        = new SearchSortRepresentation();
        $sort_representation->name  = 'title';
        $sort_representation->order = 'asc';

        $property        = new Docman_Metadata();
        $property->name  = 'Title';
        $property->label = 'title';

        $column_report = new Docman_ReportColumn($property);
        SearchColumnSortChecker::checkColumnCanBeSorted($sort_representation, $column_report);

        self::addToAssertionCount(1);
    }
}
