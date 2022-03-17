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

use Tuleap\Tracker\REST\v1\ArtifactValuesRepresentation;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveUsedFieldsStub;

final class FieldsDataBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
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
    private \Tracker_FormElement_Field_Integer $int_field;
    private \Tracker_FormElement_Field_Float $float_field;
    private \Tracker_FormElement_Field_String $string_field;
    private \Tracker_FormElement_Field_Text $text_field;
    private array $values_by_field;
    private RetrieveUsedFieldsStub $fields_retriever;

    protected function setUp(): void
    {
        $this->int_field = new \Tracker_FormElement_Field_Integer(
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

        $this->float_field = new \Tracker_FormElement_Field_Float(
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

        $this->string_field = new \Tracker_FormElement_Field_String(
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

        $this->text_field = new \Tracker_FormElement_Field_Text(
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

        $this->fields_retriever = RetrieveUsedFieldsStub::withNoFields();
    }

    /**
     * @param ArtifactValuesRepresentation[] $payload
     */
    private function getFieldsDataOnUpdate(array $payload): array
    {
        $tracker  = TrackerTestBuilder::aTracker()->withId(self::TRACKER_ID)->build();
        $artifact = ArtifactTestBuilder::anArtifact(2)->inTracker($tracker)->build();

        $builder = new FieldsDataBuilder($this->fields_retriever);
        return $builder->getFieldsDataOnUpdate($payload, $artifact);
    }

    public function testItAsksEachFieldToBuildFieldsDataFromRESTUpdatePayload(): void
    {
        $int_representation              = new ArtifactValuesRepresentation();
        $int_representation->field_id    = self::INT_FIELD_ID;
        $int_representation->value       = self::INT_VALUE;
        $float_representation            = new ArtifactValuesRepresentation();
        $float_representation->field_id  = self::FLOAT_FIELD_ID;
        $float_representation->value     = self::FLOAT_VALUE;
        $string_representation           = new ArtifactValuesRepresentation();
        $string_representation->field_id = self::STRING_FIELD_ID;
        $string_representation->value    = self::STRING_VALUE;
        $text_representation             = new ArtifactValuesRepresentation();
        $text_representation->field_id   = self::TEXT_FIELD_ID;
        $text_representation->value      = ['format' => self::TEXT_FORMAT, 'content' => self::TEXT_VALUE];
        $this->fields_retriever          = RetrieveUsedFieldsStub::withFields(
            $this->int_field,
            $this->float_field,
            $this->string_field,
            $this->text_field,
        );

        $fields_data = $this->getFieldsDataOnUpdate([
            $int_representation,
            $float_representation,
            $string_representation,
            $text_representation,
        ]);
        self::assertSame([
            self::INT_FIELD_ID    => self::INT_VALUE,
            self::FLOAT_FIELD_ID  => self::FLOAT_VALUE,
            self::STRING_FIELD_ID => self::STRING_VALUE,
            self::TEXT_FIELD_ID   => ['format' => self::TEXT_FORMAT, 'content' => self::TEXT_VALUE],
        ], $fields_data);
    }

    public function testItThrowsWhenUpdateRepresentationDoesNotHaveAFieldID(): void
    {
        $representation = new ArtifactValuesRepresentation();
        $this->expectException(\Tracker_FormElement_InvalidFieldException::class);
        $this->getFieldsDataOnUpdate([$representation]);
    }

    public function testItThrowsWhenUpdateRepresentationFieldIDIsNotInt(): void
    {
        $representation           = new ArtifactValuesRepresentation();
        $representation->field_id = null;

        $this->expectException(\Tracker_FormElement_InvalidFieldException::class);
        $this->getFieldsDataOnUpdate([$representation]);
    }

    public function testItThrowsAtUpdateWhenFieldIDCantBeFoundInTracker(): void
    {
        $representation           = new ArtifactValuesRepresentation();
        $representation->field_id = 404;

        $this->expectException(\Tracker_FormElement_InvalidFieldException::class);
        $this->getFieldsDataOnUpdate([$representation]);
    }

    public function testItThrowsAtUpdateWhenIntFieldHasNoValue(): void
    {
        $representation           = new ArtifactValuesRepresentation();
        $representation->field_id = self::INT_FIELD_ID;
        $this->fields_retriever   = RetrieveUsedFieldsStub::withFields($this->int_field);

        $this->expectException(\Tracker_FormElement_InvalidFieldValueException::class);
        $this->getFieldsDataOnUpdate([$representation]);
    }

    public function testWhenRESTUpdatePayloadIsEmptyItReturnsEmptyArray(): void
    {
        $fields_data = $this->getFieldsDataOnUpdate([]);
        self::assertEmpty($fields_data);
    }

    /**
     * @param ArtifactValuesRepresentation[] $payload
     */
    private function getFieldsDataOnCreate(array $payload): array
    {
        $tracker = TrackerTestBuilder::aTracker()->withId(self::TRACKER_ID)->build();

        $builder = new FieldsDataBuilder($this->fields_retriever);
        return $builder->getFieldsDataOnCreate($payload, $tracker);
    }

    public function testItAsksEachFieldToBuildFieldsDataFromRESTCreatePayload(): void
    {
        $int_representation              = new ArtifactValuesRepresentation();
        $int_representation->field_id    = self::INT_FIELD_ID;
        $int_representation->value       = self::INT_VALUE;
        $float_representation            = new ArtifactValuesRepresentation();
        $float_representation->field_id  = self::FLOAT_FIELD_ID;
        $float_representation->value     = self::FLOAT_VALUE;
        $string_representation           = new ArtifactValuesRepresentation();
        $string_representation->field_id = self::STRING_FIELD_ID;
        $string_representation->value    = self::STRING_VALUE;
        $text_representation             = new ArtifactValuesRepresentation();
        $text_representation->field_id   = self::TEXT_FIELD_ID;
        $text_representation->value      = ['format' => self::TEXT_FORMAT, 'content' => self::TEXT_VALUE];
        $this->fields_retriever          = RetrieveUsedFieldsStub::withFields(
            $this->int_field,
            $this->float_field,
            $this->string_field,
            $this->text_field,
        );

        $fields_data = $this->getFieldsDataOnCreate([
            $int_representation,
            $float_representation,
            $string_representation,
            $text_representation,
        ]);
        self::assertSame([
            self::INT_FIELD_ID    => self::INT_VALUE,
            self::FLOAT_FIELD_ID  => self::FLOAT_VALUE,
            self::STRING_FIELD_ID => self::STRING_VALUE,
            self::TEXT_FIELD_ID   => ['format' => self::TEXT_FORMAT, 'content' => self::TEXT_VALUE],
        ], $fields_data);
    }

    public function testItThrowsWhenCreateRepresentationDoesNotHaveAFieldID(): void
    {
        $representation = new ArtifactValuesRepresentation();
        $this->expectException(\Tracker_FormElement_InvalidFieldException::class);
        $this->getFieldsDataOnCreate([$representation]);
    }

    public function testItThrowsWhenCreateRepresentationFieldIDIsNotInt(): void
    {
        $representation           = new ArtifactValuesRepresentation();
        $representation->field_id = null;

        $this->expectException(\Tracker_FormElement_InvalidFieldException::class);
        $this->getFieldsDataOnCreate([$representation]);
    }

    public function testItThrowsAtCreateWhenFieldIDCantBeFoundInTracker(): void
    {
        $representation           = new ArtifactValuesRepresentation();
        $representation->field_id = 404;

        $this->expectException(\Tracker_FormElement_InvalidFieldException::class);
        $this->getFieldsDataOnCreate([$representation]);
    }

    public function testItThrowsAtCreateWhenIntFieldHasNoValue(): void
    {
        $representation           = new ArtifactValuesRepresentation();
        $representation->field_id = self::INT_FIELD_ID;
        $this->fields_retriever   = RetrieveUsedFieldsStub::withFields($this->int_field);

        $this->expectException(\Tracker_FormElement_InvalidFieldValueException::class);
        $this->getFieldsDataOnCreate([$representation]);
    }

    public function testWhenRESTCreatePayloadIsEmptyItReturnsEmptyArray(): void
    {
        $fields_data = $this->getFieldsDataOnCreate([]);
        self::assertEmpty($fields_data);
    }
}
