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

namespace Tuleap\Tracker\REST\Artifact\ChangesetValue;

use Tuleap\Tracker\Artifact\ChangesetValue\InitialChangesetValuesContainer;
use Tuleap\Tracker\FormElement\Field\RetrieveUsedFields;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkInitialChangesetValueBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerFormElementFloatFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerFormElementIntFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerFormElementStringFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerFormElementTextFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveUsedFieldsStub;

final class FieldsDataFromValuesByFieldBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const TRACKER_ID        = 101;
    private const INT_FIELD_ID      = 395;
    private const INT_VALUE         = 54;
    private const FLOAT_FIELD_ID    = 425;
    private const FLOAT_VALUE       = 14.03;
    private const STRING_FIELD_ID   = 40;
    private const STRING_VALUE      = 'untrampled';
    private const TEXT_FIELD_ID     = 283;
    private const TEXT_VALUE        = 'fluttery Azerbaijanese';
    private const TEXT_FORMAT       = 'text';
    private const INT_FIELD_NAME    = 'integer';
    private const FLOAT_FIELD_NAME  = 'floatibulle';
    private const STRING_FIELD_NAME = 'string';
    private const TEXT_FIELD_NAME   = 'text';
    private RetrieveUsedFields $fields_retriever;
    private \Tracker_FormElement_Field_Integer $int_field;
    private \Tracker_FormElement_Field_Float $float_field;
    private \Tracker_FormElement_Field_String $string_field;
    private \Tracker_FormElement_Field_Text $text_field;
    private \Tracker $tracker;

    protected function setUp(): void
    {
        $this->tracker      = TrackerTestBuilder::aTracker()->withId(self::TRACKER_ID)->build();
        $this->int_field    = TrackerFormElementIntFieldBuilder::anIntField(self::INT_FIELD_ID)
            ->withName(self::INT_FIELD_NAME)
            ->inTracker($this->tracker)
            ->build();
        $this->float_field  = TrackerFormElementFloatFieldBuilder::aFloatField(self::FLOAT_FIELD_ID)
            ->withName(self::FLOAT_FIELD_NAME)
            ->inTracker($this->tracker)
            ->build();
        $this->string_field = TrackerFormElementStringFieldBuilder::aStringField(self::STRING_FIELD_ID)
            ->withName(self::STRING_FIELD_NAME)
            ->inTracker($this->tracker)
            ->build();
        $this->text_field   = TrackerFormElementTextFieldBuilder::aTextField(self::TEXT_FIELD_ID)
            ->withName(self::TEXT_FIELD_NAME)
            ->inTracker($this->tracker)
            ->build();

        $this->fields_retriever = RetrieveUsedFieldsStub::withNoFields();
    }

    /**
     * @throws \Tracker_FormElement_InvalidFieldException
     * @throws \Tracker_FormElement_InvalidFieldValueException
     */
    private function buildFromValuesByField(array $payload): InitialChangesetValuesContainer
    {
        $builder = new FieldsDataFromValuesByFieldBuilder(
            $this->fields_retriever,
            new NewArtifactLinkInitialChangesetValueBuilder()
        );
        return $builder->getFieldsDataOnCreate($payload, $this->tracker);
    }

    public function testItGeneratesFieldDataFromRestValuesByField(): void
    {
        $payload = [
            self::INT_FIELD_NAME    => [
                'value' => self::INT_VALUE,
            ],
            self::FLOAT_FIELD_NAME  => [
                'value' => self::FLOAT_VALUE,
            ],
            self::STRING_FIELD_NAME => [
                'value' => self::STRING_VALUE,
            ],
            self::TEXT_FIELD_NAME   => [
                'value' => [
                    'format'  => self::TEXT_FORMAT,
                    'content' => self::TEXT_VALUE,
                ],
            ],
        ];

        $this->fields_retriever = RetrieveUsedFieldsStub::withFields(
            $this->int_field,
            $this->float_field,
            $this->string_field,
            $this->text_field,
        );

        $fields_data = $this->buildFromValuesByField($payload);
        $this->assertSame([
            self::INT_FIELD_ID    => self::INT_VALUE,
            self::FLOAT_FIELD_ID  => self::FLOAT_VALUE,
            self::STRING_FIELD_ID => self::STRING_VALUE,
            self::TEXT_FIELD_ID   => [
                'format'  => self::TEXT_FORMAT,
                'content' => self::TEXT_VALUE,
            ],
        ], $fields_data->getFieldsData());
    }

    public function testItThrowsAnExceptionIfFieldIsNotUsedInTracker(): void
    {
        $payload = [
            'integerV2'             => 42,
            self::FLOAT_FIELD_NAME  => self::FLOAT_VALUE,
            self::STRING_FIELD_NAME => self::STRING_VALUE,
            self::TEXT_FIELD_NAME   => [
                'format'  => self::TEXT_FORMAT,
                'content' => self::TEXT_VALUE,
            ],
        ];

        $this->fields_retriever = RetrieveUsedFieldsStub::withFields(
            $this->float_field,
            $this->string_field,
            $this->text_field,
        );

        $this->expectException(\Tracker_FormElement_InvalidFieldException::class);
        $this->buildFromValuesByField($payload);
    }

    public function testItThrowsAnExceptionIfFieldIsNotAlphaNumeric(): void
    {
        $msb_field = ListFieldBuilder::aListField(484)
            ->withName('msb')
            ->withMultipleValues()
            ->inTracker($this->tracker)
            ->build();

        $payload = [
            'msb' => ['whatever'],
        ];

        $this->fields_retriever = RetrieveUsedFieldsStub::withFields($msb_field);

        $this->expectException(\Tracker_FormElement_RESTValueByField_NotImplementedException::class);
        $this->buildFromValuesByField($payload);
    }
}
