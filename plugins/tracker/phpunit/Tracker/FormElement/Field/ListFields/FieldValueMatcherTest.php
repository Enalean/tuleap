<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\ListFields;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker_FormElement_Field_List;
use Tracker_FormElement_Field_List_Bind_Static;
use Tracker_FormElement_Field_List_Bind_StaticValue;

require_once __DIR__ . '/../../../../bootstrap.php';

class FieldValueMatcherTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var FieldValueMatcher
     */
    private $matcher;

    public function setUp()
    {
        parent::setUp();

        $this->source_field      = Mockery::mock(Tracker_FormElement_Field_List::class);
        $this->target_field      = Mockery::mock(Tracker_FormElement_Field_List::class);
        $this->source_field_bind = Mockery::mock(Tracker_FormElement_Field_List_Bind_Static::class);
        $this->target_field_bind = Mockery::mock(Tracker_FormElement_Field_List_Bind_Static::class);

        $this->source_field->shouldReceive('getBind')->andReturn($this->source_field_bind);
        $this->target_field->shouldReceive('getBind')->andReturn($this->target_field_bind);

        $this->matcher = new FieldValueMatcher();
    }

    public function testItRetrievesMatchingValueByName()
    {
        $source_value = new Tracker_FormElement_Field_List_Bind_StaticValue(101, '2', '', 0, 0);
        $this->source_field_bind->shouldReceive('getValue')->with(101)->andReturn($source_value);

        $target_value_01 = new Tracker_FormElement_Field_List_Bind_StaticValue(200, '1', '', 0, 0);
        $target_value_02 = new Tracker_FormElement_Field_List_Bind_StaticValue(201, '2', '', 1, 0);
        $this->target_field_bind->shouldReceive('getAllValues')->andReturn([
            $target_value_01,
            $target_value_02,
        ]);

        $matching_value = $this->matcher->getMatchingValueByDuckTyping($this->source_field, $this->target_field, 101);

        $this->assertEquals($matching_value, 201);
    }

    public function testItRetrievesMatchingValueByNameWithDifferentCases()
    {
        $source_value = new Tracker_FormElement_Field_List_Bind_StaticValue(101, 'a', '', 0, 0);
        $this->source_field_bind->shouldReceive('getValue')->with(101)->andReturn($source_value);

        $target_value_01 = new Tracker_FormElement_Field_List_Bind_StaticValue(200, 'A', '', 0, 0);
        $target_value_02 = new Tracker_FormElement_Field_List_Bind_StaticValue(201, 'b', '', 1, 0);
        $this->target_field_bind->shouldReceive('getAllValues')->andReturn([
            $target_value_01,
            $target_value_02,
        ]);

        $matching_value = $this->matcher->getMatchingValueByDuckTyping($this->source_field, $this->target_field, 101);

        $this->assertEquals($matching_value, 200);
    }

    public function testItRetrievesMatchingValueByNameEvenIfTargerValueIsHidden()
    {
        $source_value = new Tracker_FormElement_Field_List_Bind_StaticValue(101, '2', '', 0, 0);
        $this->source_field_bind->shouldReceive('getValue')->with(101)->andReturn($source_value);

        $target_value_01 = new Tracker_FormElement_Field_List_Bind_StaticValue(200, '1', '', 0, 0);
        $target_value_02 = new Tracker_FormElement_Field_List_Bind_StaticValue(201, '2', '', 1, 1);
        $this->target_field_bind->shouldReceive('getAllValues')->andReturn([
            $target_value_01,
            $target_value_02,
        ]);

        $matching_value = $this->matcher->getMatchingValueByDuckTyping($this->source_field, $this->target_field, 101);

        $this->assertEquals($matching_value, 201);
    }

    public function testItRetrievesFirstMatchingValueByNameIfMultipleValuesHaveTheSameLabel()
    {
        $source_value = new Tracker_FormElement_Field_List_Bind_StaticValue(101, '1', '', 0, 0);
        $this->source_field_bind->shouldReceive('getValue')->with(101)->andReturn($source_value);

        $target_value_01 = new Tracker_FormElement_Field_List_Bind_StaticValue(200, '1', '', 0, 0);
        $target_value_02 = new Tracker_FormElement_Field_List_Bind_StaticValue(201, '1', '', 1, 0);
        $this->target_field_bind->shouldReceive('getAllValues')->andReturn([
            $target_value_01,
            $target_value_02,
        ]);

        $matching_value = $this->matcher->getMatchingValueByDuckTyping($this->source_field, $this->target_field, 101);

        $this->assertEquals($matching_value, 200);
    }

    public function testItReturnsDefaultValueIfNoMatchingValue()
    {
        $source_value = new Tracker_FormElement_Field_List_Bind_StaticValue(101, '3', '', 0, 0);
        $this->source_field_bind->shouldReceive('getValue')->with(101)->andReturn($source_value);

        $target_value_01 = new Tracker_FormElement_Field_List_Bind_StaticValue(200, '1', '', 0, 0);
        $target_value_02 = new Tracker_FormElement_Field_List_Bind_StaticValue(201, '2', '', 1, 0);
        $target_value_00 = new Tracker_FormElement_Field_List_Bind_StaticValue(202, '0', '', 1, 0);
        $this->target_field_bind->shouldReceive('getAllValues')->andReturn([
            $target_value_01,
            $target_value_02,
            $target_value_00,
        ]);

        $this->target_field->shouldReceive('getDefaultValue')->andReturn(202);

        $matching_value = $this->matcher->getMatchingValueByDuckTyping($this->source_field, $this->target_field, 101);

        $this->assertEquals($matching_value, 202);
    }

    public function testItReturnsNoneValueIfSourceValueIsAlsoNoneAndTargetValueNotRequired()
    {
        $matching_value = $this->matcher->getMatchingValueByDuckTyping($this->source_field, $this->target_field, 100);

        $this->assertEquals($matching_value, 100);
    }

    public function testItReturnsNoneValueIfSourceValueIsNotProvided()
    {
        $matching_value = $this->matcher->getMatchingValueByDuckTyping($this->source_field, $this->target_field, 0);

        $this->assertEquals($matching_value, 100);
    }
}
