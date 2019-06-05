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

namespace Tuleap\Tracker\FormElement;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker_FormElement_Field_List_Bind;
use Tracker_FormElement_Field_List_Bind_StaticValue_None;
use Tracker_FormElement_Field_Selectbox;
use Tuleap\GlobalLanguageMock;

// phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
final class Tracker_FormElement_Field_SelectboxTest extends TestCase
{
    use MockeryPHPUnitIntegration, GlobalLanguageMock;

    public function testEmptyCSVStringIsRecognizedAsTheNoneValue() : void
    {
        $field = new Tracker_FormElement_Field_Selectbox(
            1147,
            111,
            1,
            'name',
            'label',
            'description',
            true,
            'S',
            false,
            false,
            1
        );

        $value = $field->getFieldDataFromCSVValue('');
        $this->assertEquals(Tracker_FormElement_Field_List_Bind_StaticValue_None::VALUE_ID, $value);
    }

    public function testCSVString100CanBeUsedAsACSVValue() : void
    {
        $field = new Tracker_FormElement_Field_Selectbox(
            1147,
            111,
            1,
            'name',
            'label',
            'description',
            true,
            'S',
            false,
            false,
            1
        );
        $bind = Mockery::mock(Tracker_FormElement_Field_List_Bind::class);
        $bind->shouldReceive('getFieldData')->andReturn('bind_value');
        $field->setBind($bind);

        $value = $field->getFieldDataFromCSVValue('100');
        $this->assertEquals('bind_value', $value);
    }
}
