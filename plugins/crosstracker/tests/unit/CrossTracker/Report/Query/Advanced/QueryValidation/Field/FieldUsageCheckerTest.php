<?php
/**
 * Copyright (c) Enalean 2024 - Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Field;

use Tuleap\CrossTracker\Report\Query\Advanced\InvalidComparisonCollectorParameters;
use Tuleap\CrossTracker\Report\Query\Advanced\InvalidSearchableCollectorParameters;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\ComparisonChecker;
use Tuleap\CrossTracker\Tests\Stub\SearchFieldTypesStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Field;
use Tuleap\Tracker\Report\Query\Advanced\InvalidSearchablesCollection;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class FieldUsageCheckerTest extends TestCase
{
    private InvalidSearchableCollectorParameters $visitor_parameters;

    protected function setUp(): void
    {
        $this->visitor_parameters = new InvalidSearchableCollectorParameters(
            new InvalidComparisonCollectorParameters(
                new InvalidSearchablesCollection(),
                [TrackerTestBuilder::aTracker()->withId(10)->build()],
                UserTestBuilder::aUser()->build(),
            ),
            $this->createMock(ComparisonChecker::class),
            $this->createMock(Comparison::class),
        );
    }

    public function testCheckWhenAllFieldsAreIntOrFloat(): void
    {
        $fields_type_stub = SearchFieldTypesStub::withTypes("int", "float");
        $this->check($fields_type_stub);

        $this->expectNotToPerformAssertions();
    }

    public function testCheckWhenAFieldIsString(): void
    {
        $fields_type_stub = SearchFieldTypesStub::withTypes("int", "string");
        $this->expectException(FieldTypesAreIncompatibleException::class);
        $this->check($fields_type_stub);
    }

    public function testCheckWhenFirstFieldIsString(): void
    {
        $fields_type_stub = SearchFieldTypesStub::withTypes("string", "int");
        $this->expectException(FieldTypeIsNotSupportedException::class);
        $this->check($fields_type_stub);
    }

    /**
     * @throws FieldTypeIsNotSupportedException
     * @throws FieldTypesAreIncompatibleException
     */
    private function check(SearchFieldTypes $fields_type): void
    {
        $checker = new FieldUsageChecker($fields_type);
        $checker->checkFieldIsValid(
            new Field("toto"),
            $this->visitor_parameters
        );
    }
}
