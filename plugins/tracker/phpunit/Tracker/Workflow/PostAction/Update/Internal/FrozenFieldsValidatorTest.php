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

namespace Tuleap\Tracker\Workflow\PostAction\Update\Internal;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Tracker\Workflow\PostAction\Update\FrozenFields;
use Tuleap\Tracker\Workflow\PostAction\Update\SetIntValue;

require_once __DIR__ . '/../../../../../bootstrap.php';


class FrozenFieldsValidatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var FrozenFieldsValidator */
    private $frozen_fields_validator;

    /** @var \Tracker_FormElementFactory | Mockery\MockInterface */
    private $form_element_factory;

    protected function setUp() : void
    {
        $this->form_element_factory    = Mockery::mock(\Tracker_FormElementFactory::class);
        $this->frozen_fields_validator = new FrozenFieldsValidator(
            $this->form_element_factory
        );
    }

    public function testValidateDoesNotThrowWhenValid()
    {
        $integer_field = Mockery::mock(\Tracker_FormElement_Field_Integer::class)
            ->shouldReceive('getId')
            ->andReturn(1)
            ->getMock();

        $string_field = Mockery::mock(\Tracker_FormElement_Field_String::class)
            ->shouldReceive('getId')
            ->andReturn(2)
            ->getMock();

        $this->form_element_factory
            ->shouldReceive('getUsedFields')
            ->andReturn([$integer_field, $string_field]);

        $frozen_fields_value = new FrozenFields(null, [1,2]);

        $this->frozen_fields_validator->validate(
            Mockery::mock(\Tracker::class),
            $frozen_fields_value
        );

        $this->addToAssertionCount(1);
    }

    public function testValidateWrapsDuplicateFieldIdException()
    {
        $this->form_element_factory
            ->shouldReceive('getUsedFields')
            ->andReturn([]);

        $frozen_fields_value = new FrozenFields(null, [1,1,2]);

        $this->expectException(InvalidPostActionException::class);

        $this->frozen_fields_validator->validate(
            Mockery::mock(\Tracker::class),
            $frozen_fields_value
        );
    }

    public function testValidateThrowsWhenFieldIdDoesNotMatchAUsedField()
    {
        $integer_field = Mockery::mock(\Tracker_FormElement_Field_Integer::class)
            ->shouldReceive('getId')
            ->andReturn(1)
            ->getMock();

        $string_field = Mockery::mock(\Tracker_FormElement_Field_String::class)
            ->shouldReceive('getId')
            ->andReturn(2)
            ->getMock();

        $this->form_element_factory
            ->shouldReceive('getUsedFields')
            ->andReturn([$integer_field, $string_field]);

        $frozen_fields_value = new FrozenFields(null, [1,3]);

        $this->expectException(InvalidPostActionException::class);

        $this->frozen_fields_validator->validate(
            Mockery::mock(\Tracker::class),
            $frozen_fields_value
        );
    }
}
