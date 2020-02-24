<?php
/**
 * Copyright (c) Enalean, 2015 - present. All Rights Reserved.
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

final class Tracker_REST_Artifact_ArtifactValidator_Test extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private $field_int;
    private $field_float;
    private $field_string;
    private $field_text;
    private $form_element_factory;
    private $validator;
    private $tracker;

    protected function setUp(): void
    {
        $this->tracker = Mockery::spy(Tracker::class);
        $this->tracker->shouldReceive('getId')->andReturn(101);

        $this->field_int = new Tracker_FormElement_Field_Integer(
            1,
            101,
            null,
            'field_int',
            'Field Integer',
            '',
            1,
            'P',
            true,
            '',
            1
        );

        $this->field_float = new Tracker_FormElement_Field_Float(
            2,
            101,
            null,
            'field_float',
            'Field Float',
            '',
            1,
            'P',
            true,
            '',
            1
        );

        $this->field_string = new Tracker_FormElement_Field_String(
            3,
            101,
            null,
            'field_string',
            'Field String',
            '',
            1,
            'P',
            true,
            '',
            1
        );

        $this->field_text = new Tracker_FormElement_Field_Text(
            4,
            101,
            null,
            'field_text',
            'Field Text',
            '',
            1,
            'P',
            true,
            '',
            1
        );

        $this->field_msb = new Tracker_FormElement_Field_MultiSelectbox(
            5,
            101,
            null,
            'field_msb',
            'Field MSB',
            '',
            1,
            'P',
            true,
            '',
            1
        );

        $this->form_element_factory = \Mockery::spy(\Tracker_FormElementFactory::class);

        $this->validator = new Tracker_REST_Artifact_ArtifactValidator($this->form_element_factory);
    }

    public function testItGeneratesFieldDataFromRestValuesByField(): void
    {
        $values = array(
            'integer' => array(
               'value' => 42
            ),
            'floatibulle' => array(
                'value' => 3.14
            ),
            'string' => array(
                'value' => 'My text'
            ),
            'text' => array(
                'value' => array(
                    'format'  => 'text',
                    'content' => 'My awesome text'
                )
            )
        );

        $this->form_element_factory->shouldReceive('getUsedFieldByName')->with(101, 'integer')->andReturns($this->field_int);
        $this->form_element_factory->shouldReceive('getUsedFieldByName')->with(101, 'floatibulle')->andReturns($this->field_float);
        $this->form_element_factory->shouldReceive('getUsedFieldByName')->with(101, 'string')->andReturns($this->field_string);
        $this->form_element_factory->shouldReceive('getUsedFieldByName')->with(101, 'text')->andReturns($this->field_text);

        $fields_data = $this->validator->getFieldsDataOnCreateFromValuesByField($values, $this->tracker);

        $expected = array(
            1 => 42,
            2 => 3.14,
            3 => 'My text',
            4 => array(
                'format'  => 'text',
                'content' => 'My awesome text'
            ),
        );

        $this->assertEquals($expected, $fields_data);
    }

    public function testItThrowsAnExceptionIfFieldIsNotUsedInTracker(): void
    {
        $values = array(
            'integerV2'   => 42,
            'floatibulle' => 3.14,
            'string'      => 'My text',
            'text'        => array(
                'format'  => 'text',
                'content' => 'My awesome text'
            )
        );

        $this->form_element_factory->shouldReceive('getUsedFieldByName')->with(101, 'integer')->andReturns($this->field_int);
        $this->form_element_factory->shouldReceive('getUsedFieldByName')->with(101, 'floatibulle')->andReturns($this->field_float);
        $this->form_element_factory->shouldReceive('getUsedFieldByName')->with(101, 'string')->andReturns($this->field_string);
        $this->form_element_factory->shouldReceive('getUsedFieldByName')->with(101, 'text')->andReturns($this->field_text);

        $this->expectException(\Tracker_FormElement_InvalidFieldException::class);

        $this->validator->getFieldsDataOnCreateFromValuesByField($values, $this->tracker);
    }

    public function testItThrowsAnExceptionIfFieldIsNotAlphaNumeric(): void
    {
        $values = array(
            'msb' => ['whatever']
        );

        $this->form_element_factory->shouldReceive('getUsedFieldByName')->with(101, 'msb')->andReturns($this->field_msb);

        $this->expectException(\Tracker_FormElement_RESTValueByField_NotImplementedException::class);

        $this->validator->getFieldsDataOnCreateFromValuesByField($values, $this->tracker);
    }
}
