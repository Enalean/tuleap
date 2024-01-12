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

use Tuleap\CrossTracker\Report\Query\Advanced\DuckTypedField\FieldTypeIsNotSupportedFault;
use Tuleap\CrossTracker\Report\Query\Advanced\DuckTypedField\FieldTypesAreIncompatibleFault;
use Tuleap\CrossTracker\Report\Query\Advanced\DuckTypedField\SearchFieldTypes;
use Tuleap\CrossTracker\Report\Query\Advanced\InvalidSearchableCollectorParameters;
use Tuleap\CrossTracker\Tests\Builders\InvalidSearchableCollectorParametersBuilder;
use Tuleap\CrossTracker\Tests\Stub\SearchFieldTypesStub;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Field;

final class FieldUsageCheckerTest extends TestCase
{
    private InvalidSearchableCollectorParameters $visitor_parameters;

    protected function setUp(): void
    {
        $this->visitor_parameters = InvalidSearchableCollectorParametersBuilder::aParameter()->build();
    }

    /**
     * @return Ok<null>|Err<Fault>
     */
    private function check(SearchFieldTypes $fields_type): Ok|Err
    {
        $checker = new FieldUsageChecker($fields_type);
        return $checker->checkFieldIsValid(
            new Field("toto"),
            $this->visitor_parameters
        );
    }

    public function testCheckWhenAllFieldsAreIntOrFloat(): void
    {
        $fields_type_stub = SearchFieldTypesStub::withTypes(
            \Tracker_FormElementFactory::FIELD_INTEGER_TYPE,
            \Tracker_FormElementFactory::FIELD_FLOAT_TYPE
        );
        self::assertTrue(Result::isOk($this->check($fields_type_stub)));
    }

    public function testCheckFailsWhenAFieldIsString(): void
    {
        $fields_type_stub = SearchFieldTypesStub::withTypes(
            \Tracker_FormElementFactory::FIELD_INTEGER_TYPE,
            \Tracker_FormElementFactory::FIELD_STRING_TYPE
        );
        $result           = $this->check($fields_type_stub);
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(FieldTypesAreIncompatibleFault::class, $result->error);
    }

    public function testCheckFailsWhenFirstFieldIsString(): void
    {
        $fields_type_stub = SearchFieldTypesStub::withTypes(
            \Tracker_FormElementFactory::FIELD_STRING_TYPE,
            \Tracker_FormElementFactory::FIELD_INTEGER_TYPE
        );
        $result           = $this->check($fields_type_stub);
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(FieldTypeIsNotSupportedFault::class, $result->error);
    }
}
