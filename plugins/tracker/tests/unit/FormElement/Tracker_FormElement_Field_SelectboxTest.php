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

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Tracker_FormElement_Field_List;
use Tracker_FormElement_Field_List_Bind;
use Tracker_FormElement_Field_List_Bind_StaticValue_None;
use Tracker_FormElement_Field_Selectbox;
use Tracker_FormElement_InvalidFieldValueException;
use Tracker_FormElement_RESTValueByField_NotImplementedException;
use Tuleap\GlobalLanguageMock;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticBindBuilder;

#[DisableReturnValueGenerationForTestDoubles]
final class Tracker_FormElement_Field_SelectboxTest extends TestCase // phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    use GlobalLanguageMock;
    use GlobalResponseMock;

    private Tracker_FormElement_Field_Selectbox $field;

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

    public function testEmptyCSVStringIsRecognizedAsTheNoneValue(): void
    {
        $value = $this->field->getFieldDataFromCSVValue('');
        self::assertEquals(Tracker_FormElement_Field_List_Bind_StaticValue_None::VALUE_ID, $value);
    }

    public function testCSVString100CanBeUsedAsACSVValue(): void
    {
        ListStaticBindBuilder::aStaticBind($this->field)->withStaticValues([153 => '100'])->build();
        $value = $this->field->getFieldDataFromCSVValue('100');
        self::assertEquals(153, $value);
    }

    public function testGetFieldDataFromRESTValueThrowsExceptionIfBindValueIdsAreNotPresent(): void
    {
        $this->expectException(Tracker_FormElement_InvalidFieldValueException::class);
        $this->field->getFieldDataFromRESTValue([]);
    }

    public function testGetFieldDataFromRESTValueThrowsExceptionIfBindValueIdsIsAString(): void
    {
        $this->expectException(Tracker_FormElement_InvalidFieldValueException::class);
        $this->field->getFieldDataFromRESTValue(['bind_value_ids' => '']);
    }

    public function testGetFieldDataFromRESTValueThrowsExceptionIfBindValueIdsAreMultiple(): void
    {
        $this->expectException(Tracker_FormElement_InvalidFieldValueException::class);
        $this->field->getFieldDataFromRESTValue(['bind_value_ids' => [123, 124]]);
    }

    public function testGetFieldDataFromRESTValueReturns100IfBindValueIdsIsEmpty(): void
    {
        self::assertEquals(
            Tracker_FormElement_Field_List::NONE_VALUE,
            $this->field->getFieldDataFromRESTValue(['bind_value_ids' => []])
        );
    }

    public function testGetFieldDataFromRESTValueReturns100IfValueIs100(): void
    {
        self::assertEquals(
            Tracker_FormElement_Field_List::NONE_VALUE,
            $this->field->getFieldDataFromRESTValue(['bind_value_ids' => [100]])
        );
    }

    public function testGetFieldDataFromRESTValueThrowsExceptionIfValueIsUnknown(): void
    {
        $bind = $this->createMock(Tracker_FormElement_Field_List_Bind::class);
        $bind->method('getFieldDataFromRESTValue')->willReturn(0);
        $this->field->setBind($bind);

        $this->expectException(Tracker_FormElement_InvalidFieldValueException::class);
        $this->field->getFieldDataFromRESTValue(['bind_value_ids' => [112]]);
    }

    public function testGetFieldDataFromRESTValueReturnsValue(): void
    {
        $bind = $this->createMock(Tracker_FormElement_Field_List_Bind::class);
        $bind->method('getFieldDataFromRESTValue')->willReturn(112);
        $this->field->setBind($bind);

        self::assertEquals(
            112,
            $this->field->getFieldDataFromRESTValue(['bind_value_ids' => [112]])
        );
    }

    public function testGetFieldDataFromRESTValueReturnsValueForDynamicGroup(): void
    {
        $bind = $this->createMock(Tracker_FormElement_Field_List_Bind::class);
        $bind->method('getFieldDataFromRESTValue')->willReturn(3);
        $this->field->setBind($bind);

        self::assertEquals(
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

    /**
     * @testWith [100]
     *  ["100"]
     *  [""]
     *  [null]
     *  [100]
     *  [["100"]]
     *  [[100]]
     */
    public function testItIsInvalidWhenIsRequiredAndEmpty($value): void
    {
        $artifact = $this->createStub(Artifact::class);
        $field    = new Tracker_FormElement_Field_Selectbox(
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

        self::assertFalse($field->isValidRegardingRequiredProperty($artifact, $value));
    }
}
