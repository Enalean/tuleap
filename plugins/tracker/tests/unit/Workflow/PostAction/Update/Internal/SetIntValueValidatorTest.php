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
use Tuleap\Tracker\Workflow\PostAction\Update\SetIntValue;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SetIntValueValidatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private SetIntValueValidator $set_int_value_validator;
    private PostActionFieldIdValidator&MockObject $field_id_validator;
    private \Tracker_FormElementFactory&MockObject $form_element_factory;

    protected function setUp(): void
    {
        $this->field_id_validator = $this->createMock(PostActionFieldIdValidator::class);
        $this->field_id_validator->method('validate');

        $this->form_element_factory    = $this->createMock(\Tracker_FormElementFactory::class);
        $this->set_int_value_validator = new SetIntValueValidator(
            $this->field_id_validator,
            $this->form_element_factory
        );
    }

    public function testValidateDoesNotThrowWhenValid(): void
    {
        $this->expectNotToPerformAssertions();
        $integer_field = $this->createMock(\Tracker_FormElement_Field_Integer::class);
        $integer_field
            ->method('getId')
            ->willReturn(1);
        $other_int_field = $this->createMock(\Tracker_FormElement_Field_Integer::class);
        $other_int_field
            ->method('getId')
            ->willReturn(2);
        $this->form_element_factory
            ->method('getUsedIntFields')
            ->willReturn([$integer_field, $other_int_field]);

        $first_int_value  = new SetIntValue(1, 12);
        $second_int_value = new SetIntValue(2, 42);

        $this->set_int_value_validator->validate(
            $this->createMock(\Tuleap\Tracker\Tracker::class),
            $first_int_value,
            $second_int_value
        );
    }

    public function testValidateWrapsDuplicateFieldIdException(): void
    {
        $this->set_int_value_validator = new SetIntValueValidator(
            new PostActionFieldIdValidator(),
            $this->form_element_factory
        );

        $first_same_field_id  = new SetIntValue(1, 79);
        $second_same_field_id = new SetIntValue(1, 2);

        $this->expectException(InvalidPostActionException::class);
        $this->set_int_value_validator->validate(
            $this->createMock(\Tuleap\Tracker\Tracker::class),
            $first_same_field_id,
            $second_same_field_id
        );
    }

    public function testValidateThrowsWhenFieldIdDoesNotMatchAnIntegerField(): void
    {
        $integer_field = $this->createMock(\Tracker_FormElement_Field_Integer::class);
        $integer_field
            ->method('getId')
            ->willReturn(1);
        $this->form_element_factory
            ->method('getUsedIntFields')
            ->willReturn([$integer_field]);

        $invalid_field_id = new SetIntValue(8, 0);

        $this->expectException(InvalidPostActionException::class);
        $this->set_int_value_validator->validate(
            $this->createMock(\Tuleap\Tracker\Tracker::class),
            $invalid_field_id
        );
    }
}
