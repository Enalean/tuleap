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

class SetDateValueValidatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var SetDateValueValidator */
    private $set_date_value_validator;
    /** @var PostActionIdValidator | Mockery\MockInterface */
    private $ids_validator;
    /** @var \Tracker_FormElementFactory | Mockery\MockInterface */
    private $form_element_factory;

    protected function setUp()
    {
        $this->ids_validator = Mockery::mock(PostActionIdValidator::class);
        $this->ids_validator->shouldReceive('validate')->byDefault();

        $this->form_element_factory     = Mockery::mock(\Tracker_FormElementFactory::class);
        $this->set_date_value_validator = new SetDateValueValidator($this->ids_validator, $this->form_element_factory);
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

        $first_date_value  = new SetDateValue(null, 1, 0);
        $second_date_value = new SetDateValue(null, 2, 0);

        $this->set_date_value_validator->validate(
            Mockery::mock(\Tracker::class),
            $first_date_value,
            $second_date_value
        );
    }

    /**
     * @@expectedException \Tuleap\Tracker\Workflow\PostAction\Update\Internal\InvalidPostActionException
     */
    public function testValidateWrapsDuplicatePostActionException()
    {
        $ci_build = new SetDateValue(null, 1, 0);
        $this->ids_validator
            ->shouldReceive('validate')
            ->andThrow(new DuplicatePostActionException());

        $this->set_date_value_validator->validate(Mockery::mock(\Tracker::class), $ci_build);
    }

    /**
     * @expectedException \Tuleap\Tracker\Workflow\PostAction\Update\Internal\InvalidPostActionException
     */
    public function testValidateThrowsWhenDuplicateFieldIds()
    {
        $date_field = Mockery::mock(\Tracker_FormElement_Field_Date::class)
            ->shouldReceive('getId')
            ->andReturn(3)
            ->getMock();
        $this->form_element_factory
            ->shouldReceive('getUsedCustomDateFields')
            ->andReturn([$date_field]);

        $first_identical_field_id  = new SetDateValue(null, 3, 0);
        $second_identical_field_id = new SetDateValue(null, 3, 1);

        $this->set_date_value_validator->validate(
            Mockery::mock(\Tracker::class),
            $first_identical_field_id,
            $second_identical_field_id
        );
    }

    /**
     * @expectedException \Tuleap\Tracker\Workflow\PostAction\Update\Internal\InvalidPostActionException
     */
    public function testValidateThrowsWhenFieldIdDoesNotMatchADateField()
    {
        $date_field = Mockery::mock(\Tracker_FormElement_Field_Date::class)
            ->shouldReceive('getId')
            ->andReturn(1)
            ->getMock();
        $this->form_element_factory
            ->shouldReceive('getUsedCustomDateFields')
            ->andReturn([$date_field]);

        $invalid_field_id = new SetDateValue(null, 8, 0);

        $this->set_date_value_validator->validate(Mockery::mock(\Tracker::class), $invalid_field_id);
    }
}
