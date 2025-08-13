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

declare(strict_types=1);

namespace Tuleap\Tracker\Workflow\PostAction\Update\Internal;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Tracker\Workflow\PostAction\Update\FrozenFieldsValue;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FrozenFieldsValidatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private FrozenFieldsValueValidator $frozen_fields_validator;

    private \Tracker_FormElementFactory&MockObject $form_element_factory;

    private \Tracker_RuleFactory&MockObject $tracker_rule_factory;

    protected function setUp(): void
    {
        $this->form_element_factory = $this->createMock(\Tracker_FormElementFactory::class);
        $this->tracker_rule_factory = $this->createMock(\Tracker_RuleFactory::class);

        $this->frozen_fields_validator = new FrozenFieldsValueValidator(
            $this->form_element_factory,
            $this->tracker_rule_factory
        );
    }

    public function testValidateDoesNotThrowWhenValid(): void
    {
        $integer_field = $this->createMock(\Tuleap\Tracker\FormElement\Field\Integer\IntegerField::class);
        $integer_field
            ->method('getId')
            ->willReturn(1);

        $string_field = $this->createMock(\Tuleap\Tracker\FormElement\Field\String\StringField::class);
        $string_field
            ->method('getId')
            ->willReturn(2);

        $selectbox_field = $this->createMock(\Tuleap\Tracker\FormElement\Field\List\SelectboxField::class);
        $selectbox_field
            ->method('getId')
            ->willReturn(3);

        $this->form_element_factory
            ->method('getUsedFields')
            ->willReturn([$integer_field, $string_field, $selectbox_field]);

        $this->tracker_rule_factory
            ->method('getInvolvedFieldsByTrackerId')
            ->with(101)
            ->willReturn([]);

        $frozen_fields_value = new FrozenFieldsValue([1, 2]);

        $tracker = $this->createMock(\Tuleap\Tracker\Tracker::class);
        $tracker->method('getId')->willReturn(101);

        $workflow = $this->createMock(\Workflow::class);

        $workflow->expects($this->once())->method('getFieldId')->willReturn(3);
        $tracker->expects($this->once())->method('getWorkflow')->willReturn($workflow);

        $this->frozen_fields_validator->validate(
            $tracker,
            $frozen_fields_value
        );

        $this->addToAssertionCount(1);
    }

    public function testValidateWrapsDuplicateFieldIdException(): void
    {
        $this->form_element_factory
            ->method('getUsedFields')
            ->willReturn([]);

        $this->tracker_rule_factory
            ->method('getInvolvedFieldsByTrackerId')
            ->with(101)
            ->willReturn([]);

        $frozen_fields_value = new FrozenFieldsValue([1, 1, 2]);

        $tracker = $this->createMock(\Tuleap\Tracker\Tracker::class);
        $tracker->method('getId')->willReturn(101);

        $this->expectException(InvalidPostActionException::class);

        $this->frozen_fields_validator->validate(
            $tracker,
            $frozen_fields_value
        );
    }

    public function testValidateThrowsWhenFieldIdDoesNotMatchAUsedField(): void
    {
        $integer_field = $this->createMock(\Tuleap\Tracker\FormElement\Field\Integer\IntegerField::class);
        $integer_field
            ->method('getId')
            ->willReturn(1);

        $selectbox_field = $this->createMock(\Tuleap\Tracker\FormElement\Field\List\SelectboxField::class);
        $selectbox_field
            ->method('getId')
            ->willReturn(2);

        $this->form_element_factory
            ->method('getUsedFields')
            ->willReturn([$integer_field, $selectbox_field]);

        $this->tracker_rule_factory
            ->method('getInvolvedFieldsByTrackerId')
            ->with(101)
            ->willReturn([]);

        $frozen_fields_value = new FrozenFieldsValue([1, 3]);

        $tracker  = $this->createMock(\Tuleap\Tracker\Tracker::class);
        $workflow = $this->createMock(\Workflow::class);

        $workflow->expects($this->once())->method('getFieldId')->willReturn(2);
        $tracker->method('getId')->willReturn(101);
        $tracker->expects($this->once())->method('getWorkflow')->willReturn($workflow);

        $this->expectException(InvalidPostActionException::class);

        $this->frozen_fields_validator->validate(
            $tracker,
            $frozen_fields_value
        );
    }

    public function testValidateThrowsAnExceptionWhenFieldIdIsTheFieldUsedToDefineToWorkflow(): void
    {
        $selectbox_field = $this->createMock(\Tuleap\Tracker\FormElement\Field\List\SelectboxField::class);
        $selectbox_field
            ->method('getId')
            ->willReturn(1);

        $string_field = $this->createMock(\Tuleap\Tracker\FormElement\Field\String\StringField::class);
        $string_field
            ->method('getId')
            ->willReturn(2);

        $this->form_element_factory
            ->method('getUsedFields')
            ->willReturn([$selectbox_field, $string_field]);

        $this->tracker_rule_factory
            ->method('getInvolvedFieldsByTrackerId')
            ->with(101)
            ->willReturn([]);

        $frozen_fields_value = new FrozenFieldsValue([1, 2]);

        $this->expectException(InvalidPostActionException::class);

        $tracker  = $this->createMock(\Tuleap\Tracker\Tracker::class);
        $workflow = $this->createMock(\Workflow::class);

        $workflow->expects($this->once())->method('getFieldId')->willReturn(1);
        $tracker->method('getId')->willReturn(101);
        $tracker->expects($this->once())->method('getWorkflow')->willReturn($workflow);

        $this->frozen_fields_validator->validate(
            $tracker,
            $frozen_fields_value
        );
    }

    public function testValidateThrowsAnExceptionWhenFieldIdIsUsedInFieldDependencies(): void
    {
        $selectbox_field_01 = $this->createMock(\Tuleap\Tracker\FormElement\Field\List\SelectboxField::class);
        $selectbox_field_01
            ->method('getId')
            ->willReturn(1);

        $selectbox_field_02 = $this->createMock(\Tuleap\Tracker\FormElement\Field\List\SelectboxField::class);
        $selectbox_field_02
            ->method('getId')
            ->willReturn(2);

        $string_field = $this->createMock(\Tuleap\Tracker\FormElement\Field\String\StringField::class);
        $string_field
            ->method('getId')
            ->willReturn(3);

        $this->form_element_factory
            ->method('getUsedFields')
            ->willReturn([$selectbox_field_01, $selectbox_field_02, $string_field]);

        $this->tracker_rule_factory
            ->method('getInvolvedFieldsByTrackerId')
            ->with(101)
            ->willReturn([
                0 => [
                    'source_field_id' => '1',
                    'target_field_id' => '2',
                ],
            ]);

        $frozen_fields_value = new FrozenFieldsValue([1, 2]);

        $this->expectException(InvalidPostActionException::class);

        $tracker  = $this->createMock(\Tuleap\Tracker\Tracker::class);
        $workflow = $this->createMock(\Workflow::class);

        $workflow->expects($this->once())->method('getFieldId')->willReturn(1);
        $tracker->method('getId')->willReturn(101);
        $tracker->expects($this->once())->method('getWorkflow')->willReturn($workflow);

        $this->frozen_fields_validator->validate(
            $tracker,
            $frozen_fields_value
        );
    }
}
