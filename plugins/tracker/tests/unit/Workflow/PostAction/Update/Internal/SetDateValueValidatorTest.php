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
use Tuleap\Tracker\Workflow\PostAction\Update\SetDateValue;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SetDateValueValidatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private SetDateValueValidator $set_date_value_validator;
    private PostActionFieldIdValidator&MockObject $field_id_validator;
    private \Tracker_FormElementFactory&MockObject $form_element_factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->field_id_validator = $this->createMock(PostActionFieldIdValidator::class);
        $this->field_id_validator->method('validate');

        $this->form_element_factory     = $this->createMock(\Tracker_FormElementFactory::class);
        $this->set_date_value_validator = new SetDateValueValidator(
            $this->field_id_validator,
            $this->form_element_factory
        );
    }

    public function testValidateDoesNotThrowWhenValid(): void
    {
        $this->expectNotToPerformAssertions();

        $date_field = $this->createMock(\Tuleap\Tracker\FormElement\Field\Date\DateField::class);
        $date_field
            ->method('getId')
            ->willReturn(1);
        $other_date_field = $this->createMock(\Tuleap\Tracker\FormElement\Field\Date\DateField::class);
        $other_date_field
            ->method('getId')
            ->willReturn(2);
        $this->form_element_factory
            ->method('getUsedCustomDateFields')
            ->willReturn([$date_field, $other_date_field]);

        $first_date_value  = new SetDateValue(1, 0);
        $second_date_value = new SetDateValue(2, 0);

        $this->set_date_value_validator->validate(
            $this->createMock(\Tuleap\Tracker\Tracker::class),
            $first_date_value,
            $second_date_value
        );
    }

    public function testValidateWrapsDuplicateFieldIdException(): void
    {
        $first_same_field_id  = new SetDateValue(1, 0);
        $second_same_field_id = new SetDateValue(1, 2);
        $this->field_id_validator->method('validate')
            ->with($first_same_field_id, $second_same_field_id)
            ->willThrowException(new DuplicateFieldIdException());

        $this->expectException(InvalidPostActionException::class);

        $this->set_date_value_validator->validate(
            $this->createMock(\Tuleap\Tracker\Tracker::class),
            $first_same_field_id,
            $second_same_field_id
        );
    }

    public function testValidateThrowsWhenFieldIdDoesNotMatchADateField(): void
    {
        $date_field = $this->createMock(\Tuleap\Tracker\FormElement\Field\Date\DateField::class);
        $date_field
            ->method('getId')
            ->willReturn(1);
        $this->form_element_factory
            ->method('getUsedCustomDateFields')
            ->willReturn([$date_field]);

        $invalid_field_id = new SetDateValue(8, 0);

        $this->expectException(InvalidPostActionException::class);

        $this->set_date_value_validator->validate($this->createMock(\Tuleap\Tracker\Tracker::class), $invalid_field_id);
    }
}
