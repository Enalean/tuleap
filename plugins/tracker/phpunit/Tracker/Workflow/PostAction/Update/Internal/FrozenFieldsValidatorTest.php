<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Workflow\PostAction\Update\Internal;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Tracker\Workflow\PostAction\Update\FrozenFieldsValue;

require_once __DIR__ . '/../../../../../bootstrap.php';


class FrozenFieldsValidatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var FrozenFieldsValueValidator */
    private $frozen_fields_validator;

    /** @var \Tracker_FormElementFactory | Mockery\MockInterface */
    private $form_element_factory;

    /** @var \Tracker_RuleFactory | Mockery\MockInterface */
    private $tracker_rule_factory;

    protected function setUp(): void
    {
        $this->form_element_factory    = Mockery::mock(\Tracker_FormElementFactory::class);
        $this->tracker_rule_factory    = Mockery::mock(\Tracker_RuleFactory::class);

        $this->frozen_fields_validator = new FrozenFieldsValueValidator(
            $this->form_element_factory,
            $this->tracker_rule_factory
        );
    }

    public function testValidateDoesNotThrowWhenValid()
    {
        $integer_field = Mockery::mock(\Tracker_FormElement_Field_Integer::class)
            ->shouldReceive('getId')
            ->andReturn(1)
            ->getMock();

        $string_field = Mockery::mock(\Tracker_FormElement_Field_String::class)
            ->shouldReceive('getId')
            ->andReturn(2)
            ->getMock();

        $selectbox_field = Mockery::mock(\Tracker_FormElement_Field_Selectbox::class)
            ->shouldReceive('getId')
            ->andReturn(3)
            ->getMock();

        $this->form_element_factory
            ->shouldReceive('getUsedFields')
            ->andReturn([$integer_field, $string_field, $selectbox_field]);

        $this->tracker_rule_factory
            ->shouldReceive('getInvolvedFieldsByTrackerId')
            ->with(101)
            ->andReturn([]);

        $frozen_fields_value = new FrozenFieldsValue([1, 2]);

        $tracker  = Mockery::mock(\Tracker::class);
        $tracker->shouldReceive('getId')->andReturn(101);

        $workflow = Mockery::mock(\Workflow::class);

        $workflow->shouldReceive('getFieldId')->once()->andReturn(3);
        $tracker->shouldReceive('getWorkflow')->once()->andReturn($workflow);

        $this->frozen_fields_validator->validate(
            $tracker,
            $frozen_fields_value
        );

        $this->addToAssertionCount(1);
    }

    public function testValidateWrapsDuplicateFieldIdException()
    {
        $this->form_element_factory
            ->shouldReceive('getUsedFields')
            ->andReturn([]);

        $this->tracker_rule_factory
            ->shouldReceive('getInvolvedFieldsByTrackerId')
            ->with(101)
            ->andReturn([]);

        $frozen_fields_value = new FrozenFieldsValue([1, 1, 2]);

        $tracker = Mockery::mock(\Tracker::class);
        $tracker->shouldReceive('getId')->andReturn(101);

        $this->expectException(InvalidPostActionException::class);

        $this->frozen_fields_validator->validate(
            $tracker,
            $frozen_fields_value
        );
    }

    public function testValidateThrowsWhenFieldIdDoesNotMatchAUsedField()
    {
        $integer_field = Mockery::mock(\Tracker_FormElement_Field_Integer::class)
            ->shouldReceive('getId')
            ->andReturn(1)
            ->getMock();

        $selectbox_field = Mockery::mock(\Tracker_FormElement_Field_Selectbox::class)
            ->shouldReceive('getId')
            ->andReturn(2)
            ->getMock();

        $this->form_element_factory
            ->shouldReceive('getUsedFields')
            ->andReturn([$integer_field, $selectbox_field]);

        $this->tracker_rule_factory
            ->shouldReceive('getInvolvedFieldsByTrackerId')
            ->with(101)
            ->andReturn([]);

        $frozen_fields_value = new FrozenFieldsValue([1, 3]);

        $tracker  = Mockery::mock(\Tracker::class);
        $workflow = Mockery::mock(\Workflow::class);

        $workflow->shouldReceive('getFieldId')->once()->andReturn(2);
        $tracker->shouldReceive('getId')->andReturn(101);
        $tracker->shouldReceive('getWorkflow')->once()->andReturn($workflow);

        $this->expectException(InvalidPostActionException::class);

        $this->frozen_fields_validator->validate(
            $tracker,
            $frozen_fields_value
        );
    }

    public function testValidateThrowsAnExceptionWhenFieldIdIsTheFieldUsedToDefineToWorkflow()
    {
        $selectbox_field = Mockery::mock(\Tracker_FormElement_Field_Selectbox::class)
            ->shouldReceive('getId')
            ->andReturn(1)
            ->getMock();

        $string_field = Mockery::mock(\Tracker_FormElement_Field_String::class)
            ->shouldReceive('getId')
            ->andReturn(2)
            ->getMock();

        $this->form_element_factory
            ->shouldReceive('getUsedFields')
            ->andReturn([$selectbox_field, $string_field]);

        $this->tracker_rule_factory
            ->shouldReceive('getInvolvedFieldsByTrackerId')
            ->with(101)
            ->andReturn([]);

        $frozen_fields_value = new FrozenFieldsValue([1, 2]);

        $this->expectException(InvalidPostActionException::class);

        $tracker  = Mockery::mock(\Tracker::class);
        $workflow = Mockery::mock(\Workflow::class);

        $workflow->shouldReceive('getFieldId')->once()->andReturn(1);
        $tracker->shouldReceive('getId')->andReturn(101);
        $tracker->shouldReceive('getWorkflow')->once()->andReturn($workflow);

        $this->frozen_fields_validator->validate(
            $tracker,
            $frozen_fields_value
        );
    }

    public function testValidateThrowsAnExceptionWhenFieldIdIsUsedInFieldDependencies()
    {
        $selectbox_field_01 = Mockery::mock(\Tracker_FormElement_Field_Selectbox::class)
            ->shouldReceive('getId')
            ->andReturn(1)
            ->getMock();

        $selectbox_field_02 = Mockery::mock(\Tracker_FormElement_Field_Selectbox::class)
            ->shouldReceive('getId')
            ->andReturn(2)
            ->getMock();

        $string_field = Mockery::mock(\Tracker_FormElement_Field_String::class)
            ->shouldReceive('getId')
            ->andReturn(3)
            ->getMock();

        $this->form_element_factory
            ->shouldReceive('getUsedFields')
            ->andReturn([$selectbox_field_01, $selectbox_field_02, $string_field]);

        $this->tracker_rule_factory
            ->shouldReceive('getInvolvedFieldsByTrackerId')
            ->with(101)
            ->andReturn([
                0 => [
                    'source_field_id' => '1',
                    'target_field_id' => '2',
                ]
            ]);

        $frozen_fields_value = new FrozenFieldsValue([1, 2]);

        $this->expectException(InvalidPostActionException::class);

        $tracker  = Mockery::mock(\Tracker::class);
        $workflow = Mockery::mock(\Workflow::class);

        $workflow->shouldReceive('getFieldId')->once()->andReturn(1);
        $tracker->shouldReceive('getId')->andReturn(101);
        $tracker->shouldReceive('getWorkflow')->once()->andReturn($workflow);

        $this->frozen_fields_validator->validate(
            $tracker,
            $frozen_fields_value
        );
    }
}
