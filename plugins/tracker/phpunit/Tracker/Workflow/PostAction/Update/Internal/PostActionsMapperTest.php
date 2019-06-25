<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

namespace Tuleap\Tracker\Workflow\PostAction\Update\Internal;

require_once __DIR__ . '/../../../../../bootstrap.php';

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFields;
use Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets\HiddenFieldsets;
use Tuleap\Tracker\Workflow\PostAction\Update\CIBuildValue;
use Tuleap\Tracker\Workflow\PostAction\Update\HiddenFieldsetsValue;
use Tuleap\Tracker\Workflow\PostAction\Update\SetDateValue;
use Tuleap\Tracker\Workflow\PostAction\Update\SetFloatValue;
use Tuleap\Tracker\Workflow\PostAction\Update\SetIntValue;
use Tuleap\Tracker\Workflow\PostAction\Update\FrozenFieldsValue;

class PostActionsMapperTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var PostActionsMapper */
    private $mapper;

    protected function setUp(): void
    {
        $this->mapper = new PostActionsMapper();
    }

    public function testConvertToCIBuildWithNullId()
    {
        $first_ci_build = Mockery::mock(\Transition_PostAction_CIBuild::class);
        $first_ci_build->shouldReceive('getJobUrl')->andReturn('https://example.com/1');
        $second_ci_build = Mockery::mock(\Transition_PostAction_CIBuild::class);
        $second_ci_build->shouldReceive('getJobUrl')->andReturn('https://example.com/2');

        $result = $this->mapper->convertToCIBuildWithNullId($first_ci_build, $second_ci_build);

        $this->assertEquals(
            [
                new CIBuildValue('https://example.com/1'),
                new CIBuildValue('https://example.com/2')
            ],
            $result
        );
    }

    public function testConvertToSetDateValueWithNullId()
    {
        $first_date = Mockery::mock(\Transition_PostAction_Field_Date::class);
        $first_date->shouldReceive(
            ['getFieldId' => '104', 'getValueType' => \Transition_PostAction_Field_Date::FILL_CURRENT_TIME]
        );
        $second_date = Mockery::mock(\Transition_PostAction_Field_Date::class);
        $second_date->shouldReceive(
            ['getFieldId' => '108', 'getValueType' => \Transition_PostAction_Field_Date::CLEAR_DATE]
        );

        $result = $this->mapper->convertToSetDateValueWithNullId($first_date, $second_date);
        $this->assertEquals(
            [
                new SetDateValue(104, \Transition_PostAction_Field_Date::FILL_CURRENT_TIME),
                new SetDateValue(108, \Transition_PostAction_Field_Date::CLEAR_DATE)
            ],
            $result
        );
    }

    public function testConvertToSetFloatValueWithNullId()
    {
        $first_float = Mockery::mock(\Transition_PostAction_Field_Float::class);
        $first_float->shouldReceive(
            ['getFieldId' => '104', 'getValue' => 186.43]
        );
        $second_float = Mockery::mock(\Transition_PostAction_Field_Float::class);
        $second_float->shouldReceive(
            ['getFieldId' => '108', 'getValue' => -83]
        );

        $result = $this->mapper->convertToSetFloatValueWithNullId($first_float, $second_float);
        $this->assertEquals(
            [
                new SetFloatValue(104, 186.43),
                new SetFloatValue(108, -83)
            ],
            $result
        );
    }

    public function testConvertToSetIntValueWithNullId()
    {
        $first_int = Mockery::mock(\Transition_PostAction_Field_Int::class);
        $first_int->shouldReceive(
            ['getFieldId' => '104', 'getValue' => 42]
        );
        $second_int = Mockery::mock(\Transition_PostAction_Field_Int::class);
        $second_int->shouldReceive(
            ['getFieldId' => '108', 'getValue' => -18]
        );

        $result = $this->mapper->convertToSetIntValueWithNullId($first_int, $second_int);
        $this->assertEquals(
            [
                new SetIntValue(104, 42),
                new SetIntValue(108, -18)
            ],
            $result
        );
    }

    public function testConvertToFrozenFieldsValueValueWithNullId()
    {
        $frozen_fields = Mockery::mock(FrozenFields::class);
        $frozen_fields->shouldReceive('getFieldIds')->andReturn([999]);

        $result = $this->mapper->convertToFrozenFieldValueWithNullId($frozen_fields);
        $this->assertEquals(
            [
                new FrozenFieldsValue([999]),
            ],
            $result
        );
    }

    public function testConvertToHiddenFieldsetsValueValueWithNullId()
    {
        $fieldset_01 = Mockery::mock(\Tracker_FormElement_Container_Fieldset::class);
        $fieldset_02 = Mockery::mock(\Tracker_FormElement_Container_Fieldset::class);

        $fieldset_01->shouldReceive('getID')->andReturn('648');
        $fieldset_02->shouldReceive('getID')->andReturn('701');

        $hidden_fieldsets = Mockery::mock(HiddenFieldsets::class);
        $hidden_fieldsets->shouldReceive('getFieldsets')->andReturn([
            $fieldset_01,
            $fieldset_02
        ]);

        $result = $this->mapper->convertToHiddenFieldsetsValueWithNullId($hidden_fieldsets);
        $this->assertEquals(
            [
                new HiddenFieldsetsValue([648, 701]),
            ],
            $result
        );
    }
}
