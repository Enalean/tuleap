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

namespace Tuleap\CrossTracker\Query\Advanced\DuckTypedField\Where;

use Tracker_FormElementFactory;
use Tuleap\CrossTracker\Query\Advanced\DuckTypedField\FieldTypeIsNotSupportedFault;
use Tuleap\CrossTracker\Query\Advanced\DuckTypedField\FieldTypeRetrieverWrapper;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DuckTypedFieldTypeWhereTest extends TestCase
{
    public function testIntBecomesNumeric(): void
    {
        $result = DuckTypedFieldTypeWhere::fromString(Tracker_FormElementFactory::FIELD_INTEGER_TYPE);
        self::assertSame(DuckTypedFieldTypeWhere::NUMERIC, $result->unwrapOr(null));
    }

    public function testFloatBecomesNumeric(): void
    {
        $result = DuckTypedFieldTypeWhere::fromString(Tracker_FormElementFactory::FIELD_FLOAT_TYPE);
        self::assertSame(DuckTypedFieldTypeWhere::NUMERIC, $result->unwrapOr(null));
    }

    public function testStringBecomesText(): void
    {
        $result = DuckTypedFieldTypeWhere::fromString(Tracker_FormElementFactory::FIELD_STRING_TYPE);
        self::assertSame(DuckTypedFieldTypeWhere::TEXT, $result->unwrapOr(null));
    }

    public function testTextBecomesText(): void
    {
        $result = DuckTypedFieldTypeWhere::fromString(Tracker_FormElementFactory::FIELD_TEXT_TYPE);
        self::assertSame(DuckTypedFieldTypeWhere::TEXT, $result->unwrapOr(null));
    }

    public function testDateBecomesDate(): void
    {
        $result = DuckTypedFieldTypeWhere::fromString(Tracker_FormElementFactory::FIELD_DATE_TYPE);
        self::assertSame(DuckTypedFieldTypeWhere::DATE, $result->unwrapOr(null));
    }

    public function testDatetimeBecomesDatetime(): void
    {
        $result = DuckTypedFieldTypeWhere::fromString(FieldTypeRetrieverWrapper::FIELD_DATETIME_TYPE);
        self::assertSame(DuckTypedFieldTypeWhere::DATETIME, $result->unwrapOr(null));
    }

    public function testStaticListBecomesStaticList(): void
    {
        $result = DuckTypedFieldTypeWhere::fromString(FieldTypeRetrieverWrapper::FIELD_STATIC_LIST_TYPE);
        self::assertSame(DuckTypedFieldTypeWhere::STATIC_LIST, $result->unwrapOr(null));
    }

    public function testUGroupListBecomesUGroupList(): void
    {
        $result = DuckTypedFieldTypeWhere::fromString(FieldTypeRetrieverWrapper::FIELD_UGROUP_LIST_TYPE);
        self::assertSame(DuckTypedFieldTypeWhere::UGROUP_LIST, $result->unwrapOr(null));
    }

    public static function generateTypes(): iterable
    {
        yield [Tracker_FormElementFactory::FIELD_COMPUTED];
        yield [Tracker_FormElementFactory::FIELD_SELECT_BOX_TYPE];
        yield [Tracker_FormElementFactory::FIELD_RADIO_BUTTON_TYPE];
        yield [Tracker_FormElementFactory::FIELD_CHECKBOX_TYPE];
        yield [Tracker_FormElementFactory::FIELD_MULTI_SELECT_BOX_TYPE];
        yield [Tracker_FormElementFactory::FIELD_OPEN_LIST_TYPE];
        yield [Tracker_FormElementFactory::FIELD_SHARED];
        yield [Tracker_FormElementFactory::FIELD_FILE_TYPE];
        yield [Tracker_FormElementFactory::FIELD_CROSS_REFERENCES];
        yield [Tracker_FormElementFactory::FIELD_ARTIFACT_LINKS];
        yield [Tracker_FormElementFactory::FIELD_BURNDOWN];
        yield [Tracker_FormElementFactory::FIELD_RANK];
        yield ['burnup']; // Burn-up
        yield ['ttmstepdef']; // Step definition
        yield ['ttmstepexec']; // Step execution
        yield [Tracker_FormElementFactory::FIELD_PERMISSION_ON_ARTIFACT_TYPE];
        yield [Tracker_FormElementFactory::FIELD_SUBMITTED_ON_TYPE];
        yield [Tracker_FormElementFactory::FIELD_SUBMITTED_BY_TYPE];
        yield [Tracker_FormElementFactory::FIELD_LAST_UPDATE_DATE_TYPE];
        yield [Tracker_FormElementFactory::FIELD_LAST_MODIFIED_BY];
        yield [Tracker_FormElementFactory::FIELD_ARTIFACT_ID_TYPE];
        yield [Tracker_FormElementFactory::FIELD_ARTIFACT_IN_TRACKER];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('generateTypes')]
    public function testOtherTypesReturnAnError(string $type_name): void
    {
        $result = DuckTypedFieldTypeWhere::fromString($type_name);
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(FieldTypeIsNotSupportedFault::class, $result->error);
    }
}
