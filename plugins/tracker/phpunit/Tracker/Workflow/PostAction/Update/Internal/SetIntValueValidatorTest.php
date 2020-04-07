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
use Tuleap\Tracker\Workflow\PostAction\Update\SetIntValue;

class SetIntValueValidatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var SetIntValueValidator */
    private $set_int_value_validator;
    /** @var PostActionFieldIdValidator | Mockery\MockInterface */
    private $field_id_validator;
    /** @var \Tracker_FormElementFactory | Mockery\MockInterface */
    private $form_element_factory;

    protected function setUp(): void
    {
        $this->field_id_validator = Mockery::mock(PostActionFieldIdValidator::class);
        $this->field_id_validator->shouldReceive('validate')->byDefault();

        $this->form_element_factory    = Mockery::mock(\Tracker_FormElementFactory::class);
        $this->set_int_value_validator = new SetIntValueValidator(
            $this->field_id_validator,
            $this->form_element_factory
        );
    }

    public function testValidateDoesNotThrowWhenValid()
    {
        $integer_field   = Mockery::mock(\Tracker_FormElement_Field_Integer::class)
            ->shouldReceive('getId')
            ->andReturn(1)
            ->getMock();
        $other_int_field = Mockery::mock(\Tracker_FormElement_Field_Integer::class)
            ->shouldReceive('getId')
            ->andReturn(2)
            ->getMock();
        $this->form_element_factory
            ->shouldReceive('getUsedIntFields')
            ->andReturn([$integer_field, $other_int_field]);

        $first_int_value  = new SetIntValue(1, 12);
        $second_int_value = new SetIntValue(2, 42);

        $this->set_int_value_validator->validate(
            Mockery::mock(\Tracker::class),
            $first_int_value,
            $second_int_value
        );
    }

    public function testValidateWrapsDuplicateFieldIdException()
    {
        $this->set_int_value_validator = new SetIntValueValidator(
            new PostActionFieldIdValidator(),
            $this->form_element_factory
        );

        $first_same_field_id  = new SetIntValue(1, 79);
        $second_same_field_id = new SetIntValue(1, 2);

        $this->expectException(InvalidPostActionException::class);
        $this->set_int_value_validator->validate(
            Mockery::mock(\Tracker::class),
            $first_same_field_id,
            $second_same_field_id
        );
    }

    public function testValidateThrowsWhenFieldIdDoesNotMatchAnIntegerField()
    {
        $integer_field = Mockery::mock(\Tracker_FormElement_Field_Integer::class)
            ->shouldReceive('getId')
            ->andReturn(1)
            ->getMock();
        $this->form_element_factory
            ->shouldReceive('getUsedIntFields')
            ->andReturn([$integer_field]);

        $invalid_field_id = new SetIntValue(8, 0);

        $this->expectException(InvalidPostActionException::class);
        $this->set_int_value_validator->validate(
            Mockery::mock(\Tracker::class),
            $invalid_field_id
        );
    }
}
