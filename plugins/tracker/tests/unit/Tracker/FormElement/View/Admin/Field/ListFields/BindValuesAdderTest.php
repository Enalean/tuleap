<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\View\Admin\Field\ListFields;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker_FormElement_Field_List_Bind_StaticValue_None;
use Tuleap\GlobalLanguageMock;

final class BindValuesAdderTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tracker_FormElement_Field_List
     */
    private $field;
    /**
     * @var BindValuesAdder
     */
    private $adder;

    protected function setUp(): void
    {
        $this->adder = new BindValuesAdder();
        $this->field = \Mockery::mock(\Tracker_FormElement_Field_List::class);
    }

    public function testItAlwaysReturnsAllValuesWithNone(): void
    {
        $this->field->shouldReceive('isRequired')->andReturnFalse();

        $value = \Mockery::mock(\Tracker_FormElement_Field_List_Bind_StaticValue::class);

        $result = $this->adder->addNoneValue([$value]);

        $expected_values = [new Tracker_FormElement_Field_List_Bind_StaticValue_None(), $value];

        $this->assertInstanceOf(Tracker_FormElement_Field_List_Bind_StaticValue_None::class, $expected_values[0]);
        $this->assertSame($expected_values[1], $result[1]);
    }
}
