<?php
/**
 * Copyright (c) Enalean, 2015 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\Artifact\Changeset\Value;

use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class FieldsDataBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const TRACKER_ID = 101;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub & \Tracker_FormElementFactory
     */
    private $form_element_factory;
    private array $values;
    private \Tracker_FormElement_Field_MultiSelectbox $field_msb;

    protected function setUp(): void
    {
        $this->field_msb = new \Tracker_FormElement_Field_MultiSelectbox(
            5,
            self::TRACKER_ID,
            null,
            'field_msb',
            'Field MSB',
            '',
            1,
            'P',
            true,
            '',
            1
        );

        $this->form_element_factory = $this->createStub(\Tracker_FormElementFactory::class);
    }

    public function returnFields(int $tracker_id, string $field_shortname)
    {
        $int_field = new \Tracker_FormElement_Field_Integer(
            1,
            self::TRACKER_ID,
            null,
            'field_int',
            'Field Integer',
            '',
            1,
            'P',
            true,
            '',
            1
        );

        $float_field = new \Tracker_FormElement_Field_Float(
            2,
            self::TRACKER_ID,
            null,
            'field_float',
            'Field Float',
            '',
            1,
            'P',
            true,
            '',
            1
        );

        $string_field = new \Tracker_FormElement_Field_String(
            3,
            self::TRACKER_ID,
            null,
            'field_string',
            'Field String',
            '',
            1,
            'P',
            true,
            '',
            1
        );

        $text_field = new \Tracker_FormElement_Field_Text(
            4,
            self::TRACKER_ID,
            null,
            'field_text',
            'Field Text',
            '',
            1,
            'P',
            true,
            '',
            1
        );

        if ($field_shortname === 'integer') {
            return $int_field;
        }
        if ($field_shortname === 'floatibulle') {
            return $float_field;
        }
        if ($field_shortname === 'string') {
            return $string_field;
        }
        if ($field_shortname === 'text') {
            return $text_field;
        }
        return null;
    }

    private function buildFromValuesByField(): array
    {
        $tracker = TrackerTestBuilder::aTracker()->withId(self::TRACKER_ID)->build();

        $builder = new FieldsDataBuilder($this->form_element_factory);
        return $builder->getFieldsDataOnCreateFromValuesByField($this->values, $tracker);
    }

    public function testItGeneratesFieldDataFromRestValuesByField(): void
    {
        $this->values = [
            'integer'     => [
                'value' => 42,
            ],
            'floatibulle' => [
                'value' => 3.14,
            ],
            'string'      => [
                'value' => 'My text',
            ],
            'text'        => [
                'value' => [
                    'format'  => 'text',
                    'content' => 'My awesome text',
                ],
            ],
        ];

        $this->form_element_factory->method('getUsedFieldByName')
            ->willReturnCallback([$this, 'returnFields']);

        $fields_data = $this->buildFromValuesByField();

        $expected = [
            1 => 42,
            2 => 3.14,
            3 => 'My text',
            4 => [
                'format'  => 'text',
                'content' => 'My awesome text',
            ],
        ];

        $this->assertSame($expected, $fields_data);
    }

    public function testItThrowsAnExceptionIfFieldIsNotUsedInTracker(): void
    {
        $this->values = [
            'integerV2'   => 42,
            'floatibulle' => 3.14,
            'string'      => 'My text',
            'text'        => [
                'format'  => 'text',
                'content' => 'My awesome text',
            ],
        ];

        $this->form_element_factory->method('getUsedFieldByName')
            ->willReturnCallback([$this, 'returnFields']);

        $this->expectException(\Tracker_FormElement_InvalidFieldException::class);
        $this->buildFromValuesByField();
    }

    public function testItThrowsAnExceptionIfFieldIsNotAlphaNumeric(): void
    {
        $this->values = [
            'msb' => ['whatever'],
        ];

        $this->form_element_factory->method('getUsedFieldByName')
            ->with(self::TRACKER_ID, 'msb')
            ->willReturn($this->field_msb);

        $this->expectException(\Tracker_FormElement_RESTValueByField_NotImplementedException::class);
        $this->buildFromValuesByField();
    }
}
