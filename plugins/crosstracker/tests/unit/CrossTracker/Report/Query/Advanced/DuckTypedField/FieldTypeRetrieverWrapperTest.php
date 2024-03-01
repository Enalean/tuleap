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

use Tracker_FormElement;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\Fields\DateFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\FloatFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\IntFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListUserBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListUserGroupBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\StringFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\TextFieldBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveFieldTypeStub;

final class FieldTypeRetrieverWrapperTest extends TestCase
{
    public function testItReturnsDatetime(): void
    {
        $retriever = new FieldTypeRetrieverWrapper(RetrieveFieldTypeStub::withDetectionOfType());

        self::assertSame(FieldTypeRetrieverWrapper::FIELD_DATETIME_TYPE, $retriever->getType(
            DateFieldBuilder::aDateField(452)
                ->withTime()
                ->build()
        ));
    }

    public function testItReturnsStaticList(): void
    {
        $retriever = new FieldTypeRetrieverWrapper(RetrieveFieldTypeStub::withDetectionOfType());

        self::assertSame(FieldTypeRetrieverWrapper::FIELD_STATIC_LIST_TYPE, $retriever->getType(
            ListStaticBindBuilder::aStaticBind(
                ListFieldBuilder::aListField(125)->build()
            )->build()->getField()
        ));
    }

    public function testItReturnsUGroupList(): void
    {
        $retriever = new FieldTypeRetrieverWrapper(RetrieveFieldTypeStub::withDetectionOfType());

        self::assertSame(FieldTypeRetrieverWrapper::FIELD_UGROUP_LIST_TYPE, $retriever->getType(
            ListUserGroupBindBuilder::aUserGroupBind(
                ListFieldBuilder::aListField(563)->build()
            )->build()->getField()
        ));
    }

    public static function notHandledTypeProvider(): iterable
    {
        yield 'int field' => [IntFieldBuilder::anIntField(784)->build()];
        yield 'float field' => [FloatFieldBuilder::aFloatField(456)->build()];
        yield 'date field' => [DateFieldBuilder::aDateField(134)->build()];
        yield 'text field' => [TextFieldBuilder::aTextField(458)->build()];
        yield 'string field' => [StringFieldBuilder::aStringField(856)->build()];
        yield 'user list field' => [
            ListUserBindBuilder::aUserBind(
                ListFieldBuilder::aListField(832)->build()
            )->build()->getField(),
        ];
    }

    /**
     * @dataProvider notHandledTypeProvider
     */
    public function testItNotHandleFormElement(Tracker_FormElement $form_element): void
    {
        $retriever = new FieldTypeRetrieverWrapper(RetrieveFieldTypeStub::withType('not-handled-type'));

        self::assertSame('not-handled-type', $retriever->getType($form_element));
    }
}
