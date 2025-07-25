<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Tracker\Workflow\PostAction\Update\SetFloatValue;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SetFloatValueValidatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private SetFloatValueValidator $set_float_value_validator;
    private PostActionFieldIdValidator&MockObject $field_id_validator;
    private \Tracker_FormElementFactory&MockObject $form_element_factory;

    protected function setUp(): void
    {
        $this->field_id_validator = $this->createMock(PostActionFieldIdValidator::class);
        $this->field_id_validator->method('validate');

        $this->form_element_factory      = $this->createMock(\Tracker_FormElementFactory::class);
        $this->set_float_value_validator = new SetFloatValueValidator(
            $this->field_id_validator,
            $this->form_element_factory
        );
    }

    public function testValidateDoesNotThrowWhenValid(): void
    {
        $this->expectNotToPerformAssertions();

        $float_field = $this->createMock(\Tuleap\Tracker\FormElement\Field\Float\FloatField::class);
        $float_field
            ->method('getId')
            ->willReturn(1);
        $other_float_field = $this->createMock(\Tuleap\Tracker\FormElement\Field\Float\FloatField::class);
        $other_float_field
            ->method('getId')
            ->willReturn(2);
        $this->form_element_factory
            ->method('getUsedFormElementsByType')
            ->with($this->anything(), 'float')
            ->willReturn([$float_field, $other_float_field]);

        $first_float_value  = new SetFloatValue(1, 12.0);
        $second_float_value = new SetFloatValue(2, 42.1);

        $this->set_float_value_validator->validate(
            $this->createMock(\Tuleap\Tracker\Tracker::class),
            $first_float_value,
            $second_float_value
        );
    }

    public function testValidateWrapsDuplicateFieldIdException(): void
    {
        $this->set_float_value_validator = new SetFloatValueValidator(
            new PostActionFieldIdValidator(),
            $this->form_element_factory
        );

        $first_same_field_id  = new SetFloatValue(1, 79.0);
        $second_same_field_id = new SetFloatValue(1, 2.0);

        $this->expectException(InvalidPostActionException::class);
        $this->set_float_value_validator->validate(
            $this->createMock(\Tuleap\Tracker\Tracker::class),
            $first_same_field_id,
            $second_same_field_id
        );
    }

    public function testValidateThrowsWhenFieldIdDoesNotMatchAUsedFloatField(): void
    {
        $float_field = $this->createMock(\Tuleap\Tracker\FormElement\Field\Float\FloatField::class);
        $float_field
            ->method('getId')
            ->willReturn(1);
        $this->form_element_factory
            ->method('getUsedFormElementsByType')
            ->with($this->anything(), 'float')
            ->willReturn([$float_field]);

        $invalid_field_id = new SetFloatValue(8, 0.0);

        $this->expectException(InvalidPostActionException::class);
        $this->set_float_value_validator->validate(
            $this->createMock(\Tuleap\Tracker\Tracker::class),
            $invalid_field_id
        );
    }
}
