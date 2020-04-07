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
use Tuleap\Tracker\Workflow\PostAction\Update\SetFloatValue;

class SetFloatValueValidatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var SetFloatValueValidator */
    private $set_float_value_validator;
    /** @var PostActionFieldIdValidator | Mockery\MockInterface */
    private $field_id_validator;
    /** @var \Tracker_FormElementFactory | Mockery\MockInterface */
    private $form_element_factory;

    protected function setUp(): void
    {
        $this->field_id_validator = Mockery::mock(PostActionFieldIdValidator::class);
        $this->field_id_validator->shouldReceive('validate')->byDefault();

        $this->form_element_factory      = Mockery::mock(\Tracker_FormElementFactory::class);
        $this->set_float_value_validator = new SetFloatValueValidator(
            $this->field_id_validator,
            $this->form_element_factory
        );
    }

    public function testValidateDoesNotThrowWhenValid()
    {
        $float_field = Mockery::mock(\Tracker_FormElement_Field_Float::class)
            ->shouldReceive('getId')
            ->andReturn(1)
            ->getMock();
        $other_float_field = Mockery::mock(\Tracker_FormElement_Field_Float::class)
            ->shouldReceive('getId')
            ->andReturn(2)
            ->getMock();
        $this->form_element_factory
            ->shouldReceive('getUsedFormElementsByType')
            ->with(Mockery::any(), 'float')
            ->andReturn([$float_field, $other_float_field]);

        $first_float_value  = new SetFloatValue(1, 12.0);
        $second_float_value = new SetFloatValue(2, 42.1);

        $this->set_float_value_validator->validate(
            Mockery::mock(\Tracker::class),
            $first_float_value,
            $second_float_value
        );
    }

    public function testValidateWrapsDuplicateFieldIdException()
    {
        $this->set_float_value_validator = new SetFloatValueValidator(
            new PostActionFieldIdValidator(),
            $this->form_element_factory
        );

        $first_same_field_id  = new SetFloatValue(1, 79.0);
        $second_same_field_id = new SetFloatValue(1, 2.0);

        $this->expectException(InvalidPostActionException::class);
        $this->set_float_value_validator->validate(
            Mockery::mock(\Tracker::class),
            $first_same_field_id,
            $second_same_field_id
        );
    }

    public function testValidateThrowsWhenFieldIdDoesNotMatchAUsedFloatField()
    {
        $float_field = Mockery::mock(\Tracker_FormElement_Field_Float::class)
            ->shouldReceive('getId')
            ->andReturn(1)
            ->getMock();
        $this->form_element_factory
            ->shouldReceive('getUsedFormElementsByType')
            ->with(Mockery::any(), 'float')
            ->andReturn([$float_field]);

        $invalid_field_id = new SetFloatValue(8, 0.0);

        $this->expectException(InvalidPostActionException::class);
        $this->set_float_value_validator->validate(
            Mockery::mock(\Tracker::class),
            $invalid_field_id
        );
    }
}
