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

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Tracker_FormElement_Field_List;
use Tracker_FormElement_Field_List_Bind_StaticValue_None;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;

#[DisableReturnValueGenerationForTestDoubles]
final class BindValuesAdderTest extends TestCase
{
    use GlobalLanguageMock;

    private Tracker_FormElement_Field_List $field;
    private BindValuesAdder $adder;

    protected function setUp(): void
    {
        $this->adder = new BindValuesAdder();
        $this->field = ListFieldBuilder::aListField(145345)->build();
    }

    public function testItAlwaysReturnsAllValuesWithNone(): void
    {
        $value = ListStaticValueBuilder::aStaticValue('chocolat')->build();

        $result = $this->adder->addNoneValue([$value]);

        $expected_values = [new Tracker_FormElement_Field_List_Bind_StaticValue_None(), $value];

        self::assertInstanceOf(Tracker_FormElement_Field_List_Bind_StaticValue_None::class, $expected_values[0]);
        self::assertSame($expected_values[1], $result[1]);
    }
}
