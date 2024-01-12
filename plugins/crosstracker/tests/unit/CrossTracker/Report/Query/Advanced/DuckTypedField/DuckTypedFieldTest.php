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

namespace Tuleap\CrossTracker\Report\Query\Advanced\DuckTypedField;

use Tuleap\NeverThrow\Result;
use Tuleap\Test\PHPUnit\TestCase;

final class DuckTypedFieldTest extends TestCase
{
    public function testItBuildsWhenFieldHasCompatibleTypesInAllTrackers(): void
    {
        $result = DuckTypedField::build(
            'initial_effort',
            [14, 74, 27],
            [
                DuckTypedFieldType::fromString(\Tracker_FormElementFactory::FIELD_FLOAT_TYPE),
                DuckTypedFieldType::fromString(\Tracker_FormElementFactory::FIELD_INTEGER_TYPE),
                DuckTypedFieldType::fromString(\Tracker_FormElementFactory::FIELD_FLOAT_TYPE),
            ]
        );
        self::assertTrue(Result::isOk($result));
        self::assertSame(DuckTypedFieldType::NUMERIC, $result->value->type);
    }

    public function testItReturnsErrWhenFieldIsNotFoundInAnyTracker(): void
    {
        $result = DuckTypedField::build('initial_effort', [], []);
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(FieldNotFoundInAnyTrackerFault::class, $result->error);
    }

    public function testItReturnsErrWhenFirstTypeIsNotSupported(): void
    {
        $result = DuckTypedField::build(
            'initial_effort',
            [25, 17],
            [
                DuckTypedFieldType::fromString('invalid'),
                DuckTypedFieldType::fromString(\Tracker_FormElementFactory::FIELD_INTEGER_TYPE),
            ]
        );
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(FieldTypeIsNotSupportedFault::class, $result->error);
    }

    public function testItReturnsErrWhenFieldHasAnIncompatibleTypeInSecondTracker(): void
    {
        $result = DuckTypedField::build(
            'initial_effort',
            [68, 76],
            [
                DuckTypedFieldType::fromString(\Tracker_FormElementFactory::FIELD_INTEGER_TYPE),
                DuckTypedFieldType::fromString(\Tracker_FormElementFactory::FIELD_STRING_TYPE),
            ]
        );
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(FieldTypesAreIncompatibleFault::class, $result->error);
    }
}
