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

namespace Tuleap\Tracker\Workflow\PostAction\Update\Internal;

require_once __DIR__ . '/../../../../../bootstrap.php';

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Tracker\Workflow\PostAction\Update\SetDateValue;

class SetDateValueValidatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var SetDateValueValidator */
    private $set_date_value_validator;
    /** @var PostActionFieldIdValidator | Mockery\MockInterface */
    private $field_id_validator;
    /** @var \Tracker_FormElementFactory | Mockery\MockInterface */
    private $form_element_factory;

    protected function setUp(): void
    {
        $this->field_id_validator = Mockery::mock(PostActionFieldIdValidator::class);
        $this->field_id_validator->shouldReceive('validate')->byDefault();

        $this->form_element_factory = Mockery::mock(\Tracker_FormElementFactory::class);
        $this->set_date_value_validator = new SetDateValueValidator(
            $this->field_id_validator,
            $this->form_element_factory
        );
    }

    public function testValidateDoesNotThrowWhenValid()
    {
        $date_field = Mockery::mock(\Tracker_FormElement_Field_Date::class)
            ->shouldReceive('getId')
            ->andReturn(1)
            ->getMock();
        $other_date_field = Mockery::mock(\Tracker_FormElement_Field_Date::class)
            ->shouldReceive('getId')
            ->andReturn(2)
            ->getMock();
        $this->form_element_factory
            ->shouldReceive('getUsedCustomDateFields')
            ->andReturn([$date_field, $other_date_field]);

        $first_date_value  = new SetDateValue(1, 0);
        $second_date_value = new SetDateValue(2, 0);

        $this->set_date_value_validator->validate(
            Mockery::mock(\Tracker::class),
            $first_date_value,
            $second_date_value
        );
    }

    public function testValidateWrapsDuplicateFieldIdException()
    {
        $first_same_field_id = new SetDateValue(1, 0);
        $second_same_field_id = new SetDateValue(1, 2);
        $this->field_id_validator->shouldReceive('validate')
            ->with($first_same_field_id, $second_same_field_id)
            ->andThrow(new DuplicateFieldIdException());

        $this->expectException(InvalidPostActionException::class);

        $this->set_date_value_validator->validate(
            Mockery::mock(\Tracker::class),
            $first_same_field_id,
            $second_same_field_id
        );
    }

    public function testValidateThrowsWhenFieldIdDoesNotMatchADateField()
    {
        $date_field = Mockery::mock(\Tracker_FormElement_Field_Date::class)
            ->shouldReceive('getId')
            ->andReturn(1)
            ->getMock();
        $this->form_element_factory
            ->shouldReceive('getUsedCustomDateFields')
            ->andReturn([$date_field]);

        $invalid_field_id = new SetDateValue(8, 0);

        $this->expectException(InvalidPostActionException::class);

        $this->set_date_value_validator->validate(Mockery::mock(\Tracker::class), $invalid_field_id);
    }
}
