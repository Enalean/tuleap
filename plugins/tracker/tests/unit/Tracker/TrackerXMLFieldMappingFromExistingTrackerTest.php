<?php
/**
 * Copyright (c) Enalean, 2018-2019. All Rights Reserved.
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

namespace Tuleap\Tracker;

use Mockery;
use PHPUnit\Framework\TestCase;
use Tracker_FormElement_Container_Column;
use Tracker_FormElement_Field_List_Bind_Static;
use Tracker_FormElement_Field_List_Bind_StaticValue;
use Tracker_FormElement_Field_Selectbox;
use Tracker_FormElement_Field_Text;

require_once __DIR__ . '/../bootstrap.php';

class TrackerXMLFieldMappingFromExistingTrackerTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var array
     */
    private $fields = [];
    /**
     * @var TrackerXMLFieldMappingFromExistingTracker
     */
    private $xml_mapping;
    /**
     * @var \SimpleXMLElement
     */
    private $xml_input;
    /**
     * @var Tracker_FormElement_Field_List_Bind_StaticValue
     */
    private $bind_value_1;
    /**
     * @var Tracker_FormElement_Field_List_Bind_StaticValue
     */
    private $bind_value_2;
    /**
     * @var Tracker_FormElement_Field_List_Bind_StaticValue
     */
    private $bind_value_3;
    /**
     * @var Tracker_FormElement_Field_List_Bind_StaticValue
     */
    private $bind_value_4;
    /**
     * @var Tracker_FormElement_Field_List_Bind_StaticValue
     */
    private $bind_value_5;
    /**
     * @var Tracker_FormElement_Field_List_Bind_StaticValue
     */
    private $bind_value_6;
    /**
     * @var Tracker_FormElement_Field_Selectbox
     */
    private $select_box;
    /**
     * @var Tracker_FormElement_Container_Column
     */
    private $column_1;
    /**
     * @var Tracker_FormElement_Container_Column
     */
    private $column_2;
    /**
     * @var Tracker_FormElement_Container_Column
     */
    private $column_3;
    /**
     * @var Tracker_FormElement_Container_Column
     */
    private $column_4;
    /**
     * @var Tracker_FormElement_Field_Text
     */
    private $text_value_1;
    /**
     * @var Tracker_FormElement_Field_Text
     */
    private $text_value_2;
    /**
     * @var Tracker_FormElement_Field_Text
     */
    private $text_value_3;
    /**
     * @var Tracker_FormElement_Field_Text
     */
    private $text_value_4;
    /**
     * @var Tracker_FormElement_Field_Text
     */
    private $text_value_5;

    public function setUp(): void
    {
        $xml_field_mapping = file_get_contents(dirname(__FILE__) . '/_fixtures/TestFieldMapping.xml');
        $this->xml_input = simplexml_load_string($xml_field_mapping);

        $this->column_1 = $this->mockAColumn('stepA');
        $this->column_2 = $this->mockAColumn('stepB');
        $this->column_3 = $this->mockAColumn('stepC');
        $this->column_4 = $this->mockAColumn('stepD');

        $this->bind_value_1 = $this->mockAListStaticValue('To be done');
        $this->bind_value_2 = $this->mockAListStaticValue('On going');
        $this->bind_value_3 = $this->mockAListStaticValue('Done');
        $this->bind_value_4 = $this->mockAListStaticValue('Canceled');
        $this->bind_value_5 = $this->mockAListStaticValue('Functional review');
        $this->bind_value_6 = $this->mockAListStaticValue('Code review');

        $bind_values = Mockery::mock(Tracker_FormElement_Field_List_Bind_Static::class);
        $bind_values->shouldReceive('getAllValues')->andReturn(
            [
                $this->bind_value_1,
                $this->bind_value_2,
                $this->bind_value_3,
                $this->bind_value_4,
                $this->bind_value_5,
                $this->bind_value_6
            ]
        );

        $this->select_box = Mockery::mock(Tracker_FormElement_Field_Selectbox::class);
        $this->select_box->shouldReceive('getName')->andReturn('stepE');
        $this->select_box->shouldReceive('getBind')->andReturn($bind_values);
        $this->fields[] = $this->select_box;

        $this->text_value_1 = $this->mockAText('stepF');
        $this->text_value_2 = $this->mockAText('stepG');
        $this->text_value_3 = $this->mockAText('stepH');
        $this->text_value_4 = $this->mockAText('stepI');
        $this->text_value_5 = $this->mockAText('stepJ');

        $this->xml_mapping = new TrackerXMLFieldMappingFromExistingTracker();
    }

    public function testGetsAllMappingField()
    {
        $expected =
            [
                'F1' => $this->column_1,
                'F2' => $this->column_2,
                'F3' => $this->column_3,
                'F4' => $this->column_4,
                'F5' => $this->select_box,
                'V1' => $this->bind_value_1,
                'V2' => $this->bind_value_2,
                'V3' => $this->bind_value_3,
                'V4' => $this->bind_value_4,
                'V5' => $this->bind_value_5,
                'V6' => $this->bind_value_6,
                'F6' => $this->text_value_1,
                'F7' => $this->text_value_2,
                'F8' => $this->text_value_3,
                'F9' => $this->text_value_4,
                'F10' => $this->text_value_5,
            ];

        $this->assertEquals($expected, $this->xml_mapping->getXmlFieldsMapping($this->xml_input, $this->fields));
    }

    public function testGetsFieldMappingButOnlyMatchingFormElement()
    {
        unset($this->fields);

        $this->mockAColumn('stuffA');
        $this->mockAColumn('stuffB');
        $this->mockAColumn('stuffC');
        $this->mockAColumn('stuffD');

        $this->mockAListStaticValue('stuff1');
        $this->mockAListStaticValue('stuff2');
        $this->mockAListStaticValue('stuff3');
        $this->mockAListStaticValue('stuff4');
        $this->mockAListStaticValue('stuff5');
        $this->mockAListStaticValue('stuff5');

        $bind_values = Mockery::mock(Tracker_FormElement_Field_List_Bind_Static::class);
        $bind_values->shouldReceive('getAllValues')->andReturn(
            [
                $this->bind_value_1,
                $this->bind_value_2,
                $this->bind_value_3,
                $this->bind_value_4,
                $this->bind_value_5,
                $this->bind_value_6
            ]
        );

        $select_box = Mockery::mock(Tracker_FormElement_Field_Selectbox::class);
        $select_box->shouldReceive('getName')->andReturn('stuffE');
        $select_box->shouldReceive('getBind')->andReturn($bind_values);
        $this->fields[] = $select_box;

        $this->fields[] = $this->text_value_1;

        $this->mockAText('stuffG');
        $this->mockAText('stuffH');
        $this->mockAText('stuffI');
        $this->mockAText('stuffJ');

        $expected = ['F6' => $this->text_value_1];

        $this->assertEquals($expected, $this->xml_mapping->getXmlFieldsMapping($this->xml_input, $this->fields));
    }

    public function testGetsNothingIfNoMatchingFormElement()
    {
        unset($this->fields);

        $this->mockAColumn('stuffA');
        $this->mockAColumn('stuffB');
        $this->mockAColumn('stuffC');
        $this->mockAColumn('stuffD');

        $this->mockAListStaticValue('stuff1');
        $this->mockAListStaticValue('stuff2');
        $this->mockAListStaticValue('stuff3');
        $this->mockAListStaticValue('stuff4');
        $this->mockAListStaticValue('stuff5');
        $this->mockAListStaticValue('stuff5');

        $bind_values = Mockery::mock(Tracker_FormElement_Field_List_Bind_Static::class);
        $bind_values->shouldReceive('getAllValues')->andReturn(
            [
                $this->bind_value_1,
                $this->bind_value_2,
                $this->bind_value_3,
                $this->bind_value_4,
                $this->bind_value_5,
                $this->bind_value_6
            ]
        );

        $select_box = Mockery::mock(Tracker_FormElement_Field_Selectbox::class);
        $select_box->shouldReceive('getName')->andReturn('stuffE');
        $select_box->shouldReceive('getBind')->andReturn($bind_values);
        $this->fields[] = $select_box;

        $this->mockAText('stuffF');
        $this->mockAText('stuffG');
        $this->mockAText('stuffH');
        $this->mockAText('stuffI');
        $this->mockAText('stuffJ');

        $this->assertEquals([], $this->xml_mapping->getXmlFieldsMapping($this->xml_input, $this->fields));
    }

    /**
     * @param $name
     * @return Mockery\MockInterface
     */
    private function mockAColumn($name)
    {
        $column = Mockery::mock(Tracker_FormElement_Container_Column::class);
        $column->shouldReceive('getName')->andReturn($name);
        $this->fields[] = $column;
        return $column;
    }

    /**
     * @param $label
     * @return Mockery\MockInterface
     */
    private function mockAListStaticValue($label)
    {
        $bind_value = Mockery::mock(Tracker_FormElement_Field_List_Bind_StaticValue::class);
        $bind_value->shouldReceive('getLabel')->andReturn($label);
        return $bind_value;
    }

    /**
     * @param $name
     * @return Mockery\MockInterface
     */
    private function mockAText($name)
    {
        $text_value = Mockery::mock(Tracker_FormElement_Field_Text::class);
        $text_value->shouldReceive('getName')->andReturn($name);
        $this->fields[] = $text_value;
        return $text_value;
    }
}
