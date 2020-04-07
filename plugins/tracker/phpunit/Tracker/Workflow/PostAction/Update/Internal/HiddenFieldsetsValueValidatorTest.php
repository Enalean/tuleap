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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Tracker\Workflow\PostAction\Update\HiddenFieldsetsValue;

final class HiddenFieldsetsValueValidatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var HiddenFieldsetsValueValidator
     */
    private $hidden_fieldsets_value_validator;

    private $form_element_factory;

    protected function setUp(): void
    {
        $this->form_element_factory = Mockery::mock(\Tracker_FormElementFactory::class);

        $this->hidden_fieldsets_value_validator = new HiddenFieldsetsValueValidator(
            $this->form_element_factory
        );
    }

    public function testValidateDoesNotThrowWhenValid()
    {
        $fieldset_01 = Mockery::mock(\Tracker_FormElement_Container_Fieldset::class);
        $fieldset_02 = Mockery::mock(\Tracker_FormElement_Container_Fieldset::class);

        $fieldset_01->shouldReceive('getID')->andReturn('648');
        $fieldset_02->shouldReceive('getID')->andReturn('701');

        $this->form_element_factory
            ->shouldReceive('getUsedFieldsets')
            ->andReturn([$fieldset_01, $fieldset_02]);

        $hidden_fieldsets_values = new HiddenFieldsetsValue([648, 701]);

        $tracker  = Mockery::mock(\Tracker::class);
        $tracker->shouldReceive('getId')->andReturn(101);

        $workflow = Mockery::mock(\Workflow::class);
        $tracker->shouldReceive('getWorkflow')->once()->andReturn($workflow);

        $this->hidden_fieldsets_value_validator->validate(
            $tracker,
            $hidden_fieldsets_values
        );

        $this->addToAssertionCount(1);
    }

    public function testValidateWrapsDuplicateFieldIdException()
    {
        $this->form_element_factory
            ->shouldReceive('getUsedFieldsets')
            ->andReturn([]);

        $hidden_fieldsets_values = new HiddenFieldsetsValue([648, 648, 701]);

        $tracker = Mockery::mock(\Tracker::class);
        $tracker->shouldReceive('getId')->andReturn(101);

        $this->expectException(InvalidPostActionException::class);

        $this->hidden_fieldsets_value_validator->validate(
            $tracker,
            $hidden_fieldsets_values
        );
    }

    public function testValidateThrowsWhenFieldIdDoesNotMatchAUsedFieldset()
    {
        $fieldset_01 = Mockery::mock(\Tracker_FormElement_Container_Fieldset::class);
        $fieldset_02 = Mockery::mock(\Tracker_FormElement_Container_Fieldset::class);

        $fieldset_01->shouldReceive('getID')->andReturn('648');
        $fieldset_02->shouldReceive('getID')->andReturn('701');

        $this->form_element_factory
            ->shouldReceive('getUsedFieldsets')
            ->andReturn([$fieldset_01, $fieldset_02]);

        $hidden_fieldsets_values = new HiddenFieldsetsValue([648, 702]);

        $tracker  = Mockery::mock(\Tracker::class);
        $tracker->shouldReceive('getId')->andReturn(101);

        $workflow = Mockery::mock(\Workflow::class);
        $tracker->shouldReceive('getWorkflow')->once()->andReturn($workflow);

        $this->expectException(InvalidPostActionException::class);

        $this->hidden_fieldsets_value_validator->validate(
            $tracker,
            $hidden_fieldsets_values
        );
    }
}
