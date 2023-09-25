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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use SimpleXMLElement;
use Tracker_Artifact_ChangesetValue_List;
use Tracker_FormElement_Field_List_Bind_Static;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindStaticValueDao;
use Tracker_FormElement_Field_List_Bind_StaticValue;
use Tracker_FormElement_Field_Selectbox;
use UserXMLExporter;

// phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
final class Tracker_FormElement_Field_List_Bind_StaticTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Tracker_FormElement_Field_List_Bind_Static
     */
    private $bind;
    private $bind_without_values;

    protected function setUp(): void
    {
        parent::setUp();

        $first_value = new Tracker_FormElement_Field_List_Bind_StaticValue(
            431,
            '10',
            'int value',
            1,
            0
        );

        $second_value = new Tracker_FormElement_Field_List_Bind_StaticValue(
            432,
            '123abc',
            'string value',
            2,
            0
        );

        $field         = Mockery::mock(Tracker_FormElement_Field_Selectbox::class);
        $is_rank_alpha = 0;
        $values        = [
            431 => $first_value,
            432 => $second_value,
        ];

        $default_values = [];
        $decorators     = [];

        $this->bind = new Tracker_FormElement_Field_List_Bind_Static(
            $field,
            $is_rank_alpha,
            $values,
            $default_values,
            $decorators
        );

        $this->bind_without_values = new Tracker_FormElement_Field_List_Bind_Static(
            $field,
            $is_rank_alpha,
            [],
            $default_values,
            $decorators
        );
    }

    public function testItReturnsNumericValuesFromListInChangesetValue()
    {
        $changeset_value = Mockery::mock(Tracker_Artifact_ChangesetValue_List::class);
        $changeset_value->shouldReceive('getValue')
            ->once()
            ->andReturn(['431']);

        $this->assertSame(
            ['10'],
            $this->bind->getNumericValues($changeset_value)
        );
    }

    public function testItReturnsAnEmptyArrayFromListInChangesetValueIfSelectedValueIsNotANumericValue()
    {
        $changeset_value = Mockery::mock(Tracker_Artifact_ChangesetValue_List::class);
        $changeset_value->shouldReceive('getValue')
            ->once()
            ->andReturn(['432']);

        $this->assertEmpty($this->bind->getNumericValues($changeset_value));
    }

    public function testGetBindValues(): void
    {
        $bv1    = Mockery::mock(Tracker_FormElement_Field_List_Bind_StaticValue::class);
        $bv2    = Mockery::mock(Tracker_FormElement_Field_List_Bind_StaticValue::class);
        $field  = $is_rank_alpha = $default_values = $decorators = '';
        $values = [101 => $bv1, 102 => $bv2];
        $static = new Tracker_FormElement_Field_List_Bind_Static($field, $is_rank_alpha, $values, $default_values, $decorators);
        $this->assertEquals($values, $static->getBindValues());
        $this->assertEquals([], $static->getBindValues([]), 'Dont give more than what we are asking');
        $this->assertEquals([102 => $bv2], $static->getBindValues([102]));
        $this->assertEquals([], $static->getBindValues([666]), 'What do we have to do with unknown value?');
    }

    public function testGetFieldData(): void
    {
        $bv1    = $this->getFieldValueListWithLabel('1 - Ordinary');
        $bv2    = $this->getFieldValueListWithLabel('9 - Critical');
        $values = [13564 => $bv1, 13987 => $bv2];
        $f      = $this->getListFieldWIthBindValues($values);
        $this->assertEquals('13564', $f->getFieldData('1 - Ordinary', false));
    }

    public function testGetFieldDataMultiple(): void
    {
        $bv1    = $this->getFieldValueListWithLabel('Admin');
        $bv2    = $this->getFieldValueListWithLabel('Tracker');
        $bv3    = $this->getFieldValueListWithLabel('User Interface');
        $bv4    = $this->getFieldValueListWithLabel('Docman');
        $values = [13564 => $bv1, 13987 => $bv2, 125 => $bv3, 666 => $bv4];

        $res = ['13564', '125', '666'];
        $f   = $this->getListFieldWIthBindValues($values);
        $this->assertEquals($res, $f->getFieldData('Admin,User Interface,Docman', true));
    }

    public function testItAddsANewValue(): void
    {
        $value_dao = $this->createMock(BindStaticValueDao::class);
        $value_dao->method('propagateCreation');
        $bind_static = $this->getListFieldWIthBindValues([]);

        $bind_static->shouldReceive('getValueDao')->andReturn($value_dao);

        $value_dao->expects(self::once())->method(
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
        $bind_static = $this->getListFieldWIthBindValues([]);

        $bind_static->shouldReceive('getValueDao')->andReturn($value_dao);

        $value_dao->expects(self::once())->method(
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
        $field = Mockery::mock(\Tracker_FormElement_Field_List::class);
        $bind  = new Tracker_FormElement_Field_List_Bind_Static($field, 0, [], null, null);
        $this->assertEquals('-', $bind->formatArtifactValue(0));
    }

    protected function getFieldValueListWithLabel(string $label): \Tracker_FormElement_Field_List_BindValue
    {
        $value = Mockery::mock(Tracker_FormElement_Field_List_Bind_StaticValue::class);
        $value->shouldReceive('getLabel')->andReturn($label);

        return $value;
    }

    /**
     * @return Mockery\Mock|Tracker_FormElement_Field_List_Bind_Static
     */
    protected function getListFieldWIthBindValues(array $values)
    {
        $field = Mockery::mock(\Tracker_FormElement_Field_List::class);
        $field->shouldReceive('getId')->andReturn(101);

        return Mockery::mock(Tracker_FormElement_Field_List_Bind_Static::class, [$field, true, $values, [], []])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
    }

    public function testItExportBindWithRankEvenIfNoValue()
    {
        $expected_result = new SimpleXMLElement(
            '<?xml version="1.0"?>
                  <bind type="static" is_rank_alpha="0"/>'
        );

        $user_xml_exporter      = Mockery::mock(UserXMLExporter::class);
        $root                   = new SimpleXMLElement('<bind type="static"/>');
        $xml_mapping            = [1, 2, 3];
        $project_export_context = "false";
        $this->bind_without_values->exportToXml(
            $root,
            $xml_mapping,
            $project_export_context,
            $user_xml_exporter
        );
        $this->assertEquals($expected_result, $root);
    }

    public function testItExportBindWithValues()
    {
        $expected_result = new SimpleXMLElement(
            '<?xml version="1.0"?>
                 <bind type="static" is_rank_alpha="0">
                     <items>
                         <item ID="V431" label="10" is_hidden="0">
                            <description><![CDATA[int value]]></description>
                         </item>
                         <item ID="V432" label="123abc" is_hidden="0">
                             <description><![CDATA[string value]]></description>
                         </item>
                     </items>
                 </bind>'
        );

        $user_xml_exporter      = Mockery::mock(UserXMLExporter::class);
        $root                   = new SimpleXMLElement('<bind type="static"/>');
        $xml_mapping            = [1, 2, 3];
        $project_export_context = "false";
        $this->bind->exportToXml(
            $root,
            $xml_mapping,
            $project_export_context,
            $user_xml_exporter
        );

        $this->assertEquals($expected_result, $root);
    }
}
