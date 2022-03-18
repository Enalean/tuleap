<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\Artifact\Changeset\Value;

use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class FieldsDataFromValuesByFieldBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const TRACKER_ID      = 101;
    private const INT_FIELD_ID    = 395;
    private const INT_VALUE       = 54;
    private const FLOAT_FIELD_ID  = 425;
    private const FLOAT_VALUE     = 14.03;
    private const STRING_FIELD_ID = 40;
    private const STRING_VALUE    = 'untrampled';
    private const TEXT_FIELD_ID   = 283;
    private const TEXT_VALUE      = 'fluttery Azerbaijanese';
    private const TEXT_FORMAT     = 'text';
    /**
     * @var \PHPUnit\Framework\MockObject\Stub & \Tracker_FormElementFactory
     */
    private $form_element_factory;

    protected function setUp(): void
    {
        $this->form_element_factory = $this->createStub(\Tracker_FormElementFactory::class);
    }

    public function returnFields(int $tracker_id, string $field_shortname)
    {
        $int_field = new \Tracker_FormElement_Field_Integer(
            self::INT_FIELD_ID,
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
            self::FLOAT_FIELD_ID,
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
            self::STRING_FIELD_ID,
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
            self::TEXT_FIELD_ID,
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

    private function buildFromValuesByField(array $payload): array
    {
        $tracker = TrackerTestBuilder::aTracker()->withId(self::TRACKER_ID)->build();

        $builder = new FieldsDataFromValuesByFieldBuilder($this->form_element_factory);
        return $builder->getFieldsDataOnCreate($payload, $tracker);
    }

    public function testItGeneratesFieldDataFromRestValuesByField(): void
    {
        $payload = [
            'integer'     => [
                'value' => self::INT_VALUE,
            ],
            'floatibulle' => [
                'value' => self::FLOAT_VALUE,
            ],
            'string'      => [
                'value' => self::STRING_VALUE,
            ],
            'text'        => [
                'value' => [
                    'format'  => self::TEXT_FORMAT,
                    'content' => self::TEXT_VALUE,
                ],
            ],
        ];

        $this->form_element_factory->method('getUsedFieldByName')
            ->willReturnCallback([$this, 'returnFields']);

        $fields_data = $this->buildFromValuesByField($payload);
        $this->assertSame([
            self::INT_FIELD_ID    => self::INT_VALUE,
            self::FLOAT_FIELD_ID  => self::FLOAT_VALUE,
            self::STRING_FIELD_ID => self::STRING_VALUE,
            self::TEXT_FIELD_ID   => [
                'format'  => self::TEXT_FORMAT,
                'content' => self::TEXT_VALUE,
            ],
        ], $fields_data);
    }

    public function testItThrowsAnExceptionIfFieldIsNotUsedInTracker(): void
    {
        $payload = [
            'integerV2'   => 42,
            'floatibulle' => self::FLOAT_VALUE,
            'string'      => self::STRING_VALUE,
            'text'        => [
                'format'  => self::TEXT_FORMAT,
                'content' => self::TEXT_VALUE,
            ],
        ];

        $this->form_element_factory->method('getUsedFieldByName')
            ->willReturnCallback([$this, 'returnFields']);

        $this->expectException(\Tracker_FormElement_InvalidFieldException::class);
        $this->buildFromValuesByField($payload);
    }

    public function testItThrowsAnExceptionIfFieldIsNotAlphaNumeric(): void
    {
        $msb_field = new \Tracker_FormElement_Field_MultiSelectbox(
            484,
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

        $payload = [
            'msb' => ['whatever'],
        ];

        $this->form_element_factory->method('getUsedFieldByName')
            ->with(self::TRACKER_ID, 'msb')
            ->willReturn($msb_field);

        $this->expectException(\Tracker_FormElement_RESTValueByField_NotImplementedException::class);
        $this->buildFromValuesByField($payload);
    }
}
