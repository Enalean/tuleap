<?php
/**
 * Copyright (c) Enalean, 2013 - present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

declare(strict_types=1);

namespace Tuleap\Tracker\FormElement;

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker_FormElement_Field_List_Bind;
use Tracker_FormElement_Field_List_Bind_Static;
use Tracker_FormElement_Field_List_Bind_Users;
use Tracker_FormElement_Field_List_BindDecorator;
use Tracker_FormElement_Field_List_BindValue;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindDefaultValueDao;
use Tuleap\Tracker\FormElement\Field\ListField;
use Tuleap\Tracker\Test\Builders\Fields\List\ListUserValueBuilder;
use Tuleap\Tracker\Test\Builders\Fields\OpenListFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\SelectboxFieldBuilder;

#[DisableReturnValueGenerationForTestDoubles]
final class Tracker_FormElement_Field_List_BindTest extends TestCase //phpcs:ignore Squiz.Classes.ValidClassName.NotPascalCase
{
    private Tracker_FormElement_Field_List_BindValue&MockObject $value_2;
    private Tracker_FormElement_Field_List_BindValue&MockObject $value_1;
    private Tracker_FormElement_Field_List_Bind&MockObject $bind;
    private ListField $field;

    #[\Override]
    protected function setUp(): void
    {
        $decorator   = new Tracker_FormElement_Field_List_BindDecorator(101, 1, 0, 0, 0, 'inca-silver');
        $this->field = SelectboxFieldBuilder::aSelectboxField(42)->build();
        $this->bind  = $this->getMockBuilder(Tracker_FormElement_Field_List_Bind_Static::class)
            ->setConstructorArgs([new DatabaseUUIDV7Factory(), $this->field, '', [], [], $decorator])
            ->onlyMethods(['getAllValues', 'getAllVisibleValues'])
            ->getMock();

        $this->value_1 = $this->createMock(Tracker_FormElement_Field_List_BindValue::class);
        $this->value_2 = $this->createMock(Tracker_FormElement_Field_List_BindValue::class);
    }

    public function testItDelegatesFormattingToValues(): void
    {
        $this->value_1->expects($this->once())->method('fetchFormattedForJson');
        $this->value_2->expects($this->once())->method('fetchFormattedForJson');

        $this->bind->method('getAllValues')->willReturn([$this->value_1, $this->value_2]);

        $this->bind->fetchFormattedForJson();
    }

    public function testItFormatsValuesForJson(): void
    {
        $this->value_1->method('fetchFormattedForJson')->willReturn('whatever 1');
        $this->value_2->method('fetchFormattedForJson')->willReturn('whatever 2');
        $this->bind->method('getAllValues')->willReturn([$this->value_1, $this->value_2]);

        self::assertSame(
            [
                'whatever 1',
                'whatever 2',
            ],
            $this->bind->fetchFormattedForJson()
        );
    }

    public function testItSendsAnEmptyArrayInJSONFormatWhenNoValues(): void
    {
        $this->bind->method('getAllValues')->willReturn([]);
        self::assertSame(
            [],
            $this->bind->fetchFormattedForJson()
        );
    }

    public function testItVerifiesAValueExist(): void
    {
        $this->bind->method('getAllValues')->willReturn([101 => 101]);

        self::assertTrue($this->bind->isExistingValue(101));
        self::assertFalse($this->bind->isExistingValue(201));
    }

    public function testItFilterDefaultValuesReturnEmptyArrayIfNoDefaultValues(): void
    {
        $default_value_dao = $this->createMock(BindDefaultValueDao::class);

        $this->bind->setDefaultValueDao($default_value_dao);
        $this->bind->expects($this->never())->method('getAllVisibleValues')->willReturn(([]));

        $default_value_dao->method('save')->with(42, [])->willReturn(true);

        $params = [];
        $this->bind->process($params, true);
    }

    public function testItExtractDefaultValues(): void
    {
        $default_value_dao = $this->createMock(BindDefaultValueDao::class);
        $this->bind->setDefaultValueDao($default_value_dao);

        $this->bind->method('getAllVisibleValues')->willReturn((['111' => 'value1', '112' => 'value1', '114' => 'value1']));

        $default_value_dao->expects($this->atLeastOnce())->method('save')->with(42, ['111', '112'])->willReturn(true);

        $params = ['default' => ['111', '112', '116']];
        $this->bind->process($params, true);
    }

    public function testItExtractDefaultValuesFromOpenValue(): void
    {
        $field     = OpenListFieldBuilder::anOpenListField()->withId(42)->build();
        $decorator = new Tracker_FormElement_Field_List_BindDecorator(101, 1, 0, 0, 0, 'inca-silver');

        $user_list = [
            103 => ListUserValueBuilder::aUserWithId(103)->build(),
            111 => ListUserValueBuilder::aUserWithId(111)->build(),
            117 => ListUserValueBuilder::aUserWithId(117)->build(),
        ];

        $bind = $this->getMockBuilder(Tracker_FormElement_Field_List_Bind_Users::class)
            ->setConstructorArgs([new DatabaseUUIDV7Factory(), $field, [], [], $decorator])
            ->onlyMethods(['getAllValues'])
            ->getMock();

        $default_value_dao = $this->createMock(BindDefaultValueDao::class);

        $bind->method('getAllValues')->willReturn($user_list);

        $default_value_dao->expects($this->once())->method('save')->with(42, ['103', '111', '117'])->willReturn(true);

        $bind->setDefaultValueDao($default_value_dao);

        $params = ['default' => ['103,111,b117']];
        $bind->process($params, true);
    }

    public function testItReturnOnlyValidDefaultValues(): void
    {
        $bind = $this->getMockBuilder(Tracker_FormElement_Field_List_Bind_Users::class)
            ->setConstructorArgs([new DatabaseUUIDV7Factory(), $this->field, [], [112 => true, 0 => 103, 111 => true], []])
            ->onlyMethods(['getAllValues'])
            ->getMock();

        $user_list = [
            103 => ListUserValueBuilder::aUserWithId(103)->build(),
            111 => ListUserValueBuilder::aUserWithId(111)->build(),
            117 => ListUserValueBuilder::aUserWithId(117)->build(),
        ];

        $bind->method('getAllValues')->willReturn($user_list);

        self::assertSame([111 => true], $bind->getDefaultValues());
    }
}
