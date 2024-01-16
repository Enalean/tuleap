<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Field;

use Tuleap\CrossTracker\Tests\Stub\SearchFieldTypesStub;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\EqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Field;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SimpleValueWrapper;
use Tuleap\Tracker\Report\Query\IProvideParametrizedFromAndWhereSQLFragments;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class EqualComparisonFromWhereBuilderTest extends TestCase
{
    private SearchFieldTypesStub $types_searcher;

    protected function setUp(): void
    {
        $this->types_searcher = SearchFieldTypesStub::withTypes(
            \Tracker_FormElementFactory::FIELD_INTEGER_TYPE,
            \Tracker_FormElementFactory::FIELD_INTEGER_TYPE,
        );
    }

    private function getFromWhere(): IProvideParametrizedFromAndWhereSQLFragments
    {
        $builder = new EqualComparisonFromWhereBuilder(
            $this->types_searcher,
            new Numeric\EqualComparisonFromWhereBuilder()
        );
        $field   = new Field('my_field');
        return $builder->getFromWhere(
            $field,
            new EqualComparison($field, new SimpleValueWrapper(5)),
            [
                TrackerTestBuilder::aTracker()->withId(38)->build(),
                TrackerTestBuilder::aTracker()->withId(4)->build(),
            ]
        );
    }

    public function testItReturnsSQLForNumericField(): void
    {
        $from_where = $this->getFromWhere();
        self::assertNotEmpty($from_where->getFrom());
    }

    public function testItReturnsEmptySQLForInvalidDuckTypedField(): void
    {
        $this->types_searcher = SearchFieldTypesStub::withTypes(
            \Tracker_FormElementFactory::FIELD_INTEGER_TYPE,
            \Tracker_FormElementFactory::FIELD_STRING_TYPE,
        );
        $from_where           = $this->getFromWhere();
        self::assertEmpty($from_where->getFrom());
        self::assertEmpty($from_where->getWhere());
        self::assertEmpty($from_where->getFromParameters());
        self::assertEmpty($from_where->getWhereParameters());
    }
}
