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
use Tuleap\Tracker\Workflow\PostAction\Update\HiddenFieldsetsValue;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class HiddenFieldsetsValueValidatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private HiddenFieldsetsValueValidator $hidden_fieldsets_value_validator;

    private \Tracker_FormElementFactory&MockObject $form_element_factory;

    protected function setUp(): void
    {
        $this->form_element_factory = $this->createMock(\Tracker_FormElementFactory::class);

        $this->hidden_fieldsets_value_validator = new HiddenFieldsetsValueValidator(
            $this->form_element_factory
        );
    }

    public function testValidateDoesNotThrowWhenValid(): void
    {
        $fieldset_01 = $this->createMock(\Tracker_FormElement_Container_Fieldset::class);
        $fieldset_02 = $this->createMock(\Tracker_FormElement_Container_Fieldset::class);

        $fieldset_01->method('getID')->willReturn('648');
        $fieldset_02->method('getID')->willReturn('701');

        $this->form_element_factory
            ->method('getUsedFieldsets')
            ->willReturn([$fieldset_01, $fieldset_02]);

        $hidden_fieldsets_values = new HiddenFieldsetsValue([648, 701]);

        $tracker = $this->createMock(\Tuleap\Tracker\Tracker::class);
        $tracker->method('getId')->willReturn(101);

        $workflow = $this->createMock(\Workflow::class);
        $tracker->expects($this->once())->method('getWorkflow')->willReturn($workflow);

        $this->hidden_fieldsets_value_validator->validate(
            $tracker,
            $hidden_fieldsets_values
        );

        $this->addToAssertionCount(1);
    }

    public function testValidateWrapsDuplicateFieldIdException(): void
    {
        $this->form_element_factory
            ->method('getUsedFieldsets')
            ->willReturn([]);

        $hidden_fieldsets_values = new HiddenFieldsetsValue([648, 648, 701]);

        $tracker = $this->createMock(\Tuleap\Tracker\Tracker::class);
        $tracker->method('getId')->willReturn(101);

        $this->expectException(InvalidPostActionException::class);

        $this->hidden_fieldsets_value_validator->validate(
            $tracker,
            $hidden_fieldsets_values
        );
    }

    public function testValidateThrowsWhenFieldIdDoesNotMatchAUsedFieldset(): void
    {
        $fieldset_01 = $this->createMock(\Tracker_FormElement_Container_Fieldset::class);
        $fieldset_02 = $this->createMock(\Tracker_FormElement_Container_Fieldset::class);

        $fieldset_01->method('getID')->willReturn('648');
        $fieldset_02->method('getID')->willReturn('701');

        $this->form_element_factory
            ->method('getUsedFieldsets')
            ->willReturn([$fieldset_01, $fieldset_02]);

        $hidden_fieldsets_values = new HiddenFieldsetsValue([648, 702]);

        $tracker = $this->createMock(\Tuleap\Tracker\Tracker::class);
        $tracker->method('getId')->willReturn(101);

        $workflow = $this->createMock(\Workflow::class);
        $tracker->expects($this->once())->method('getWorkflow')->willReturn($workflow);

        $this->expectException(InvalidPostActionException::class);

        $this->hidden_fieldsets_value_validator->validate(
            $tracker,
            $hidden_fieldsets_values
        );
    }
}
