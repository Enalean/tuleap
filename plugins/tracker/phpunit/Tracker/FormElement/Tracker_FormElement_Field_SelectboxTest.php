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
use Tracker_FormElement_Field_List;
use Tracker_FormElement_Field_List_Bind;
use Tracker_FormElement_Field_List_Bind_StaticValue_None;
use Tracker_FormElement_Field_Selectbox;
use Tracker_FormElement_RESTValueByField_NotImplementedException;
use Tuleap\GlobalLanguageMock;

// phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
final class Tracker_FormElement_Field_SelectboxTest extends TestCase
{
    use MockeryPHPUnitIntegration, GlobalLanguageMock;

    /**
     * @var Tracker_FormElement_Field_Selectbox
     */
    private $field;

    protected function setUp(): void
    {
        $this->field = new Tracker_FormElement_Field_Selectbox(
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
    }

    public function testEmptyCSVStringIsRecognizedAsTheNoneValue() : void
    {
        $value = $this->field->getFieldDataFromCSVValue('');
        $this->assertEquals(Tracker_FormElement_Field_List_Bind_StaticValue_None::VALUE_ID, $value);
    }

    public function testCSVString100CanBeUsedAsACSVValue() : void
    {
        $bind = Mockery::mock(Tracker_FormElement_Field_List_Bind::class);
        $bind->shouldReceive('getFieldData')->andReturn('bind_value');
        $this->field->setBind($bind);

        $value = $this->field->getFieldDataFromCSVValue('100');
        $this->assertEquals('bind_value', $value);
    }

    public function testGetFieldDataFromRESTValueThrowsExceptionIfBindValueIdsAreNotPresent(): void
    {
        $this->expectException(\Tracker_FormElement_InvalidFieldValueException::class);
        $this->field->getFieldDataFromRESTValue([]);
    }

    public function testGetFieldDataFromRESTValueThrowsExceptionIfBindValueIdsIsAString(): void
    {
        $this->expectException(\Tracker_FormElement_InvalidFieldValueException::class);
        $this->field->getFieldDataFromRESTValue(['bind_value_ids' => '']);
    }

    public function testGetFieldDataFromRESTValueThrowsExceptionIfBindValueIdsAreMultiple(): void
    {
        $this->expectException(\Tracker_FormElement_InvalidFieldValueException::class);
        $this->field->getFieldDataFromRESTValue(['bind_value_ids' => [123, 124]]);
    }

    public function testGetFieldDataFromRESTValueReturns100IfBindValueIdsIsEmpty(): void
    {
        $this->assertEquals(
            Tracker_FormElement_Field_List::NONE_VALUE,
            $this->field->getFieldDataFromRESTValue(['bind_value_ids' => []])
        );
    }

    public function testGetFieldDataFromRESTValueReturns100IfValueIs100(): void
    {
        $this->assertEquals(
            Tracker_FormElement_Field_List::NONE_VALUE,
            $this->field->getFieldDataFromRESTValue(['bind_value_ids' => [100]])
        );
    }

    public function testGetFieldDataFromRESTValueThrowsExceptionIfValueIsUnknown(): void
    {
        $this->field->setBind(
            Mockery::mock(Tracker_FormElement_Field_List_Bind::class)
                ->shouldReceive(['getFieldDataFromRESTValue' => 0])
                ->getMock()
        );

        $this->expectException(\Tracker_FormElement_InvalidFieldValueException::class);
        $this->field->getFieldDataFromRESTValue(['bind_value_ids' => [112]]);
    }

    public function testGetFieldDataFromRESTValueReturnsValue(): void
    {
        $this->field->setBind(
            Mockery::mock(Tracker_FormElement_Field_List_Bind::class)
                ->shouldReceive(['getFieldDataFromRESTValue' => 112])
                ->getMock()
        );

        $this->assertEquals(
            112,
            $this->field->getFieldDataFromRESTValue(['bind_value_ids' => [112]])
        );
    }

    public function testGetFieldDataFromRESTValueReturnsValueForDynamicGroup(): void
    {
        $this->field->setBind(
            Mockery::mock(Tracker_FormElement_Field_List_Bind::class)
                ->shouldReceive(['getFieldDataFromRESTValue' => 3])
                ->getMock()
        );

        $this->assertEquals(
            3,
            $this->field->getFieldDataFromRESTValue(['bind_value_ids' => ['103_3']])
        );
    }

    public function testItThrowsAnExceptionWhenReturningValueIndexedByFieldName(): void
    {
        $field = new Tracker_FormElement_Field_Selectbox(
            1,
            101,
            null,
            'field_sb',
            'Field SB',
            '',
            1,
            'P',
            true,
            '',
            1
        );

        $this->expectException(Tracker_FormElement_RESTValueByField_NotImplementedException::class);

        $value = ['some_value'];

        $field->getFieldDataFromRESTValueByField($value);
    }
}
