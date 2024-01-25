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

use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\EqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Field;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SimpleValueWrapper;
use Tuleap\Tracker\Report\Query\IProvideParametrizedFromAndWhereSQLFragments;
use Tuleap\Tracker\Test\Builders\TrackerExternalFormElementBuilder;
use Tuleap\Tracker\Test\Builders\TrackerFormElementIntFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveFieldTypeStub;
use Tuleap\Tracker\Test\Stub\RetrieveUsedFieldsStub;

final class EqualComparisonFromWhereBuilderTest extends TestCase
{
    private const FIELD_NAME = 'my_field';
    private RetrieveUsedFieldsStub $fields_retriever;
    private \PFUser $user;
    private \Tracker $first_tracker;
    private \Tracker $second_tracker;

    protected function setUp(): void
    {
        $this->user             = UserTestBuilder::buildWithId(133);
        $this->first_tracker    = TrackerTestBuilder::aTracker()->withId(38)->build();
        $this->second_tracker   = TrackerTestBuilder::aTracker()->withId(4)->build();
        $this->fields_retriever = RetrieveUsedFieldsStub::withFields(
            TrackerFormElementIntFieldBuilder::anIntField(134)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->first_tracker)
                ->withReadPermission($this->user, true)
                ->build(),
            TrackerFormElementIntFieldBuilder::anIntField(859)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->second_tracker)
                ->withReadPermission($this->user, true)
                ->build()
        );
    }

    private function getFromWhere(): IProvideParametrizedFromAndWhereSQLFragments
    {
        $builder = new EqualComparisonFromWhereBuilder(
            $this->fields_retriever,
            RetrieveFieldTypeStub::withDetectionOfType(),
            new Numeric\EqualComparisonFromWhereBuilder(),
        );
        $field   = new Field(self::FIELD_NAME);
        return $builder->getFromWhere(
            $field,
            new EqualComparison($field, new SimpleValueWrapper(5)),
            $this->user,
            [$this->first_tracker, $this->second_tracker]
        );
    }

    public function testItReturnsSQLForNumericField(): void
    {
        $from_where = $this->getFromWhere();
        self::assertNotEmpty($from_where->getFrom());
    }

    public function testItReturnsEmptySQLForInvalidDuckTypedField(): void
    {
        $this->fields_retriever = RetrieveUsedFieldsStub::withFields(
            TrackerExternalFormElementBuilder::anExternalField(231)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->first_tracker)
                ->withReadPermission($this->user, true)
                ->build()
        );
        $from_where             = $this->getFromWhere();
        self::assertEmpty($from_where->getFrom());
        self::assertEmpty($from_where->getWhere());
        self::assertEmpty($from_where->getFromParameters());
        self::assertEmpty($from_where->getWhereParameters());
    }
}
