<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

namespace Tuleap\Tracker\FormElement;

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use SimpleXMLElement;
use Tracker_FormElement_Field_List_Bind_Static;
use Tracker_FormElement_Field_List_Bind_StaticValue;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindStaticValueDao;
use Tuleap\Tracker\FormElement\Field\ListField;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueListTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;
use Tuleap\Tracker\Test\Builders\Fields\SelectboxFieldBuilder;
use UserXMLExporter;

#[DisableReturnValueGenerationForTestDoubles]
final class Tracker_FormElement_Field_List_Bind_StaticTest extends TestCase // phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    private Tracker_FormElement_Field_List_Bind_Static $bind;
    private Tracker_FormElement_Field_List_Bind_Static $bind_without_values;
    private Tracker_FormElement_Field_List_Bind_StaticValue $first_value;
    private Tracker_FormElement_Field_List_Bind_StaticValue $second_value;
    private ListField $field;

    protected function setUp(): void
    {
        $this->first_value  = ListStaticValueBuilder::aStaticValue('10')->withId(431)->withDescription('int value')->build();
        $this->second_value = ListStaticValueBuilder::aStaticValue('123abc')->withId(432)->withDescription('string value')->build();

        $this->field   = SelectboxFieldBuilder::aSelectboxField(851)->build();
        $is_rank_alpha = 0;
        $values        = [
            431 => $this->first_value,
            432 => $this->second_value,
        ];

        $default_values = [];
        $decorators     = [];

        $this->bind = new Tracker_FormElement_Field_List_Bind_Static(
            new DatabaseUUIDV7Factory(),
            $this->field,
            $is_rank_alpha,
            $values,
            $default_values,
            $decorators
        );

        $this->bind_without_values = new Tracker_FormElement_Field_List_Bind_Static(
            new DatabaseUUIDV7Factory(),
            $this->field,
            $is_rank_alpha,
            [],
            $default_values,
            $decorators
        );
    }

    public function testItReturnsNumericValuesFromListInChangesetValue(): void
    {
        $changeset_value = ChangesetValueListTestBuilder::aListOfValue(1, ChangesetTestBuilder::aChangeset(123)->build(), $this->field)
            ->withValues([$this->first_value])->build();

        self::assertSame(
            ['10'],
            $this->bind->getNumericValues($changeset_value)
        );
    }

    public function testItReturnsAnEmptyArrayFromListInChangesetValueIfSelectedValueIsNotANumericValue(): void
    {
        $changeset_value = ChangesetValueListTestBuilder::aListOfValue(1, ChangesetTestBuilder::aChangeset(123)->build(), $this->field)
            ->withValues([$this->second_value])->build();

        self::assertEmpty($this->bind->getNumericValues($changeset_value));
    }

    public function testGetBindValues(): void
    {
        $bv1    = ListStaticValueBuilder::aStaticValue('1')->build();
        $bv2    = ListStaticValueBuilder::aStaticValue('2')->build();
        $field  = $is_rank_alpha = $default_values = $decorators = '';
        $values = [101 => $bv1, 102 => $bv2];
        $static = new Tracker_FormElement_Field_List_Bind_Static(new DatabaseUUIDV7Factory(), $field, $is_rank_alpha, $values, $default_values, $decorators);
        self::assertEquals($values, $static->getBindValues());
        self::assertEquals([], $static->getBindValues([]), 'Dont give more than what we are asking');
        self::assertEquals([102 => $bv2], $static->getBindValues([102]));
        self::assertEquals([], $static->getBindValues([666]), 'What do we have to do with unknown value?');
    }

    public function testGetFieldData(): void
    {
        $bind = $this->getListFieldWIthBindValues([13564 => '1 - Ordinary', 13987 => '9 - Critical']);
        self::assertEquals('13564', $bind->getFieldData('1 - Ordinary', false));
    }

    public function testGetFieldDataMultiple(): void
    {
        $res  = ['13564', '125', '666'];
        $bind = $this->getListFieldWIthBindValues([13564 => 'Admin', 13987 => 'Tracker', 125 => 'User Interface', 666 => 'Docman']);
        self::assertEquals($res, $bind->getFieldData('Admin,User Interface,Docman', true));
    }

    public function testItAddsANewValue(): void
    {
        $value_dao = $this->createMock(BindStaticValueDao::class);
        $value_dao->method('propagateCreation');
        $bind_static = $this->getMockBuilder(Tracker_FormElement_Field_List_Bind_Static::class)
            ->setConstructorArgs([
                new DatabaseUUIDV7Factory(),
                SelectboxFieldBuilder::aSelectboxField(101)->build(),
                false,
                [],
                [],
                [],
            ])
            ->onlyMethods(['getValueDao'])
            ->getMock();
        $bind_static->method('getValueDao')->willReturn($value_dao);

        $value_dao->expects($this->once())->method(
            'create'
        )->with(
            101,
            'intermodulation',
            self::anything(),
            self::anything(),
            self::anything(),
        )->willReturn(321);

        $new_id = $bind_static->addValue(' intermodulation	');

        self::assertEquals(321, $new_id);
    }

    public function testItAddsZeroAsANewValue(): void
    {
        $value_dao = $this->createMock(BindStaticValueDao::class);
        $value_dao->method('propagateCreation');
        $bind_static = $this->getMockBuilder(Tracker_FormElement_Field_List_Bind_Static::class)
            ->setConstructorArgs([
                new DatabaseUUIDV7Factory(),
                SelectboxFieldBuilder::aSelectboxField(101)->build(),
                false,
                [],
                [],
                [],
            ])
            ->onlyMethods(['getValueDao'])
            ->getMock();
        $bind_static->method('getValueDao')->willReturn($value_dao);

        $value_dao->expects($this->once())->method(
            'create'
        )->with(
            101,
            '0',
            self::anything(),
            self::anything(),
            self::anything(),
        )->willReturn(321);

        $new_id = $bind_static->addValue('0');

        self::assertEquals(321, $new_id);
    }

    public function testItDoesntCrashWhenInvalidValueShouldBePrinted(): void
    {
        $field = SelectboxFieldBuilder::aSelectboxField(851)->build();
        $bind  = new Tracker_FormElement_Field_List_Bind_Static(new DatabaseUUIDV7Factory(), $field, 0, [], null, null);
        self::assertEquals('-', $bind->formatArtifactValue(0));
    }

    /**
     * @param array<int, string> $values
     */
    protected function getListFieldWIthBindValues(array $values): Tracker_FormElement_Field_List_Bind_Static
    {
        $field = SelectboxFieldBuilder::aSelectboxField(101)->build();
        return ListStaticBindBuilder::aStaticBind($field)->withStaticValues($values)->build();
    }

    public function testItExportBindWithRankEvenIfNoValue(): void
    {
        $expected_result = new SimpleXMLElement(
            '<?xml version="1.0"?>
                  <bind type="static" is_rank_alpha="0"/>'
        );

        $root        = new SimpleXMLElement('<bind type="static"/>');
        $xml_mapping = [1, 2, 3];
        $this->bind_without_values->exportBindToXml(
            $root,
            $xml_mapping,
            false,
            $this->createStub(UserXMLExporter::class)
        );
        self::assertEquals($expected_result, $root);
    }

    public function testItExportBindWithValues(): void
    {
        $root        = new SimpleXMLElement('<bind type="static"/>');
        $xml_mapping = [1, 2, 3];
        $this->bind->exportBindToXml(
            $root,
            $xml_mapping,
            false,
            $this->createStub(UserXMLExporter::class)
        );

        $items = $root->items->children();

        self::assertEquals($this->first_value->getLabel(), $items[0]['label']);
        self::assertEquals($this->first_value->getUuid(), $items[0]['ID']);

        self::assertEquals($this->second_value->getLabel(), $items[1]['label']);
        self::assertEquals($this->second_value->getUuid(), $items[1]['ID']);
    }
}
