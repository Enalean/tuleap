<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker;

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use SimpleXMLElement;
use Tracker_FormElement_Container_Column;
use Tracker_FormElement_Field;
use Tracker_FormElement_Field_List_Bind_StaticValue;
use Tracker_FormElement_Field_Selectbox;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Field\Text\TextField;
use Tuleap\Tracker\Test\Builders\Fields\ColumnContainerBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\TextFieldBuilder;

#[DisableReturnValueGenerationForTestDoubles]
final class TrackerXMLFieldMappingFromExistingTrackerTest extends TestCase
{
    /** @var Tracker_FormElement_Field[] */
    private array $fields = [];
    private TrackerXMLFieldMappingFromExistingTracker $xml_mapping;
    private SimpleXMLElement $xml_input;
    private Tracker_FormElement_Field_List_Bind_StaticValue $bind_value_1;
    private Tracker_FormElement_Field_List_Bind_StaticValue $bind_value_2;
    private Tracker_FormElement_Field_List_Bind_StaticValue $bind_value_3;
    private Tracker_FormElement_Field_List_Bind_StaticValue $bind_value_4;
    private Tracker_FormElement_Field_List_Bind_StaticValue $bind_value_5;
    private Tracker_FormElement_Field_List_Bind_StaticValue $bind_value_6;
    private Tracker_FormElement_Field_Selectbox $select_box;
    private Tracker_FormElement_Container_Column $column_1;
    private Tracker_FormElement_Container_Column $column_2;
    private Tracker_FormElement_Container_Column $column_3;
    private Tracker_FormElement_Container_Column $column_4;
    private TextField $text_value_1;
    private TextField $text_value_2;
    private TextField $text_value_3;
    private TextField $text_value_4;
    private TextField $text_value_5;

    public function setUp(): void
    {
        $xml_field_mapping = file_get_contents(dirname(__FILE__) . '/_fixtures/TestFieldMapping.xml');
        $this->xml_input   = simplexml_load_string($xml_field_mapping);

        $this->column_1 = $this->buildAColumn('stepA');
        $this->column_2 = $this->buildAColumn('stepB');
        $this->column_3 = $this->buildAColumn('stepC');
        $this->column_4 = $this->buildAColumn('stepD');

        $this->bind_value_1 = ListStaticValueBuilder::aStaticValue('To be done')->withId(1)->build();
        $this->bind_value_2 = ListStaticValueBuilder::aStaticValue('On going')->withId(2)->build();
        $this->bind_value_3 = ListStaticValueBuilder::aStaticValue('Done')->withId(3)->build();
        $this->bind_value_4 = ListStaticValueBuilder::aStaticValue('Canceled')->withId(4)->build();
        $this->bind_value_5 = ListStaticValueBuilder::aStaticValue('Functional review')->withId(5)->build();
        $this->bind_value_6 = ListStaticValueBuilder::aStaticValue('Code review')->withId(6)->build();

        $list_field = ListStaticBindBuilder::aStaticBind(ListFieldBuilder::aListField(65)->withName('stepE')->build())
            ->withBuildStaticValues([
                $this->bind_value_1,
                $this->bind_value_2,
                $this->bind_value_3,
                $this->bind_value_4,
                $this->bind_value_5,
                $this->bind_value_6,
            ])->build()->getField();
        self::assertInstanceOf(Tracker_FormElement_Field_Selectbox::class, $list_field);
        $this->select_box = $list_field;
        $this->fields[]   = $this->select_box;

        $this->text_value_1 = $this->buildATextField('stepF');
        $this->text_value_2 = $this->buildATextField('stepG');
        $this->text_value_3 = $this->buildATextField('stepH');
        $this->text_value_4 = $this->buildATextField('stepI');
        $this->text_value_5 = $this->buildATextField('stepJ');

        $this->xml_mapping = new TrackerXMLFieldMappingFromExistingTracker();
    }

    public function testGetsAllMappingField(): void
    {
        $expected = [
            'F1'  => $this->column_1,
            'F2'  => $this->column_2,
            'F3'  => $this->column_3,
            'F4'  => $this->column_4,
            'F5'  => $this->select_box,
            'V1'  => $this->bind_value_1,
            'V2'  => $this->bind_value_2,
            'V3'  => $this->bind_value_3,
            'V4'  => $this->bind_value_4,
            'V5'  => $this->bind_value_5,
            'V6'  => $this->bind_value_6,
            'F6'  => $this->text_value_1,
            'F7'  => $this->text_value_2,
            'F8'  => $this->text_value_3,
            'F9'  => $this->text_value_4,
            'F10' => $this->text_value_5,
        ];

        self::assertEquals($expected, $this->xml_mapping->getXmlFieldsMapping($this->xml_input, $this->fields));
    }

    public function testGetsFieldMappingButOnlyMatchingFormElement(): void
    {
        unset($this->fields);

        $this->buildAColumn('stuffA');
        $this->buildAColumn('stuffB');
        $this->buildAColumn('stuffC');
        $this->buildAColumn('stuffD');

        $select_box     = ListStaticBindBuilder::aStaticBind(ListFieldBuilder::aListField(74)->withName('stuffE')->build())
            ->withBuildStaticValues([
                $this->bind_value_1,
                $this->bind_value_2,
                $this->bind_value_3,
                $this->bind_value_4,
                $this->bind_value_5,
                $this->bind_value_6,
            ])->build()->getField();
        $this->fields[] = $select_box;

        $this->fields[] = $this->text_value_1;

        $this->buildATextField('stuffG');
        $this->buildATextField('stuffH');
        $this->buildATextField('stuffI');
        $this->buildATextField('stuffJ');

        $expected = ['F6' => $this->text_value_1];

        self::assertEquals($expected, $this->xml_mapping->getXmlFieldsMapping($this->xml_input, $this->fields));
    }

    public function testGetsNothingIfNoMatchingFormElement(): void
    {
        unset($this->fields);

        $this->buildAColumn('stuffA');
        $this->buildAColumn('stuffB');
        $this->buildAColumn('stuffC');
        $this->buildAColumn('stuffD');

        $select_box     = ListStaticBindBuilder::aStaticBind(ListFieldBuilder::aListField(74)->withName('stuffE')->build())
            ->withBuildStaticValues([
                $this->bind_value_1,
                $this->bind_value_2,
                $this->bind_value_3,
                $this->bind_value_4,
                $this->bind_value_5,
                $this->bind_value_6,
            ])->build()->getField();
        $this->fields[] = $select_box;

        $this->buildATextField('stuffF');
        $this->buildATextField('stuffG');
        $this->buildATextField('stuffH');
        $this->buildATextField('stuffI');
        $this->buildATextField('stuffJ');

        self::assertEquals([], $this->xml_mapping->getXmlFieldsMapping($this->xml_input, $this->fields));
    }

    private function buildAColumn(string $name): Tracker_FormElement_Container_Column
    {
        $column         = ColumnContainerBuilder::aColumn(15)->withName($name)->build();
        $this->fields[] = $column;
        return $column;
    }

    private function buildATextField(string $name): TextField
    {
        $text_value     = TextFieldBuilder::aTextField(85)->withName($name)->build();
        $this->fields[] = $text_value;
        return $text_value;
    }
}
