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

namespace Tuleap\Tracker\REST\Artifact\ChangesetValue;

use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\CollectionOfForwardLinks;
use Tuleap\Tracker\Artifact\ChangesetValue\ChangesetValuesContainer;
use Tuleap\Tracker\Artifact\ChangesetValue\InitialChangesetValuesContainer;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkChangesetValueBuilder;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkInitialChangesetValueBuilder;
use Tuleap\Tracker\REST\v1\ArtifactValuesRepresentation;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ArtifactValuesRepresentationBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ArtifactLinkFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\FloatFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\IntFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\StringFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\TextFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveForwardLinksStub;
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
    private const LINK_FIELD_ID   = 514;
    private \Tracker_FormElement_Field_Integer $int_field;
    private \Tracker_FormElement_Field_Float $float_field;
    private \Tracker_FormElement_Field_String $string_field;
    private \Tracker_FormElement_Field_Text $text_field;
    private RetrieveUsedFieldsStub $fields_retriever;
    private \Tracker $tracker;

    protected function setUp(): void
    {
        $this->tracker      = TrackerTestBuilder::aTracker()->withId(self::TRACKER_ID)->build();
        $this->int_field    = IntFieldBuilder::anIntField(self::INT_FIELD_ID)
            ->inTracker($this->tracker)
            ->build();
        $this->float_field  = FloatFieldBuilder::aFloatField(self::FLOAT_FIELD_ID)
            ->inTracker($this->tracker)
            ->build();
        $this->string_field = StringFieldBuilder::aStringField(self::STRING_FIELD_ID)
            ->inTracker($this->tracker)
            ->build();
        $this->text_field   = TextFieldBuilder::aTextField(self::TEXT_FIELD_ID)
            ->inTracker($this->tracker)
            ->build();

        $this->fields_retriever = RetrieveUsedFieldsStub::withNoFields();
    }

    /**
     * @param ArtifactValuesRepresentation[] $payload
     */
    private function getFieldsDataOnUpdate(array $payload): ChangesetValuesContainer
    {
        $artifact = ArtifactTestBuilder::anArtifact(2)->inTracker($this->tracker)->build();
        $user     = UserTestBuilder::buildWithDefaults();

        $builder = new FieldsDataBuilder(
            $this->fields_retriever,
            new NewArtifactLinkChangesetValueBuilder(
                RetrieveForwardLinksStub::withLinks(new CollectionOfForwardLinks([]))
            ),
            new NewArtifactLinkInitialChangesetValueBuilder()
        );
        return $builder->getFieldsDataOnUpdate($payload, $artifact, $user);
    }

    public function testItTellsEachFieldToBuildFieldsDataFromRESTUpdatePayload(): void
    {
        $int_representation    = ArtifactValuesRepresentationBuilder::aRepresentation(self::INT_FIELD_ID)
            ->withValue(self::INT_VALUE)
            ->build();
        $float_representation  = ArtifactValuesRepresentationBuilder::aRepresentation(self::FLOAT_FIELD_ID)
            ->withValue(self::FLOAT_VALUE)
            ->build();
        $string_representation = ArtifactValuesRepresentationBuilder::aRepresentation(self::STRING_FIELD_ID)
            ->withValue(self::STRING_VALUE)
            ->build();
        $text_representation   = ArtifactValuesRepresentationBuilder::aRepresentation(self::TEXT_FIELD_ID)
            ->withValue(['format' => self::TEXT_FORMAT, 'content' => self::TEXT_VALUE])
            ->build();

        $this->fields_retriever = RetrieveUsedFieldsStub::withFields(
            $this->int_field,
            $this->float_field,
            $this->string_field,
            $this->text_field,
        );

        $changeset_values = $this->getFieldsDataOnUpdate([
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
        ], $changeset_values->getFieldsData());
        self::assertTrue($changeset_values->getArtifactLinkValue()->isNothing());
    }

    public function testItBuildsArtifactLinkChangesetValueSeparatelyFromRESTUpdatePayload(): void
    {
        $this->fields_retriever = RetrieveUsedFieldsStub::withFields(
            ArtifactLinkFieldBuilder::anArtifactLinkField(self::LINK_FIELD_ID)
                ->withTrackerId(self::TRACKER_ID)
                ->build()
        );

        $first_linked_artifact_id  = 40;
        $second_linked_artifact_id = 87;
        $link_representation       = ArtifactValuesRepresentationBuilder::aRepresentation(self::LINK_FIELD_ID)
            ->withLinks(
                ['id' => $first_linked_artifact_id, 'type' => null],
                ['id' => $second_linked_artifact_id, 'type' => 'custom_type'],
            )->build();

        $changeset_values = $this->getFieldsDataOnUpdate([$link_representation]);
        $artifact_link    = $changeset_values->getArtifactLinkValue();
        $new_links        = $artifact_link->unwrapOr(null)
            ?->getChangeForwardLinksCommand()
            ->getLinksToAdd()
            ->getTargetArtifactIds();
        self::assertCount(2, $new_links);
        self::assertContains($first_linked_artifact_id, $new_links);
        self::assertContains($second_linked_artifact_id, $new_links);
    }

    public function testItThrowsWhenUpdateRepresentationDoesNotHaveAFieldID(): void
    {
        $representation = new ArtifactValuesRepresentation();
        $this->expectException(\Tracker_FormElement_InvalidFieldException::class);
        $this->getFieldsDataOnUpdate([$representation]);
    }

    public function testItThrowsAtUpdateWhenFieldIDCantBeFoundInTracker(): void
    {
        $representation = ArtifactValuesRepresentationBuilder::aRepresentation(404)->build();

        $this->expectException(\Tracker_FormElement_InvalidFieldException::class);
        $this->getFieldsDataOnUpdate([$representation]);
    }

    public function testItThrowsAtUpdateWhenIntFieldHasNoValue(): void
    {
        $representation         = ArtifactValuesRepresentationBuilder::aRepresentation(self::INT_FIELD_ID)->build();
        $this->fields_retriever = RetrieveUsedFieldsStub::withFields($this->int_field);

        $this->expectException(\Tracker_FormElement_InvalidFieldValueException::class);
        $this->getFieldsDataOnUpdate([$representation]);
    }

    public function testWhenRESTUpdatePayloadIsEmptyItReturnsEmptyArray(): void
    {
        $changeset_values = $this->getFieldsDataOnUpdate([]);
        self::assertEmpty($changeset_values->getFieldsData());
    }

    /**
     * @param ArtifactValuesRepresentation[] $payload
     */
    private function getFieldsDataOnCreate(array $payload): InitialChangesetValuesContainer
    {
        $tracker = TrackerTestBuilder::aTracker()->withId(self::TRACKER_ID)->build();

        $builder = new FieldsDataBuilder(
            $this->fields_retriever,
            new NewArtifactLinkChangesetValueBuilder(RetrieveForwardLinksStub::withoutLinks()),
            new NewArtifactLinkInitialChangesetValueBuilder()
        );
        return $builder->getFieldsDataOnCreate($payload, $tracker);
    }

    public function testItTellsEachFieldToBuildFieldsDataFromRESTCreatePayload(): void
    {
        $int_representation    = ArtifactValuesRepresentationBuilder::aRepresentation(self::INT_FIELD_ID)
            ->withValue(self::INT_VALUE)
            ->build();
        $float_representation  = ArtifactValuesRepresentationBuilder::aRepresentation(self::FLOAT_FIELD_ID)
            ->withValue(self::FLOAT_VALUE)
            ->build();
        $string_representation = ArtifactValuesRepresentationBuilder::aRepresentation(self::STRING_FIELD_ID)
            ->withValue(self::STRING_VALUE)
            ->build();
        $text_representation   = ArtifactValuesRepresentationBuilder::aRepresentation(self::TEXT_FIELD_ID)
            ->withValue(['format' => self::TEXT_FORMAT, 'content' => self::TEXT_VALUE])
            ->build();

        $this->fields_retriever = RetrieveUsedFieldsStub::withFields(
            $this->int_field,
            $this->float_field,
            $this->string_field,
            $this->text_field,
        );

        $changeset_values = $this->getFieldsDataOnCreate([
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
        ], $changeset_values->getFieldsData());
        self::assertTrue($changeset_values->getArtifactLinkValue()->isNothing());
    }

    public function testItBuildsArtifactLinkChangesetValueSeparatelyFromRESTCreatePayload(): void
    {
        $this->fields_retriever = RetrieveUsedFieldsStub::withFields(
            ArtifactLinkFieldBuilder::anArtifactLinkField(self::LINK_FIELD_ID)
                ->withTrackerId(self::TRACKER_ID)
                ->build()
        );

        $first_linked_artifact_id  = 41;
        $second_linked_artifact_id = 78;
        $link_representation       = ArtifactValuesRepresentationBuilder::aRepresentation(self::LINK_FIELD_ID)
            ->withLinks(
                ['id' => $first_linked_artifact_id, 'type' => null],
                ['id' => $second_linked_artifact_id, 'type' => 'custom_type'],
            )->build();

        $changeset_values = $this->getFieldsDataOnCreate([$link_representation]);
        $artifact_link    = $changeset_values->getArtifactLinkValue();
        $new_links        = $artifact_link->unwrapOr(null)?->getNewLinks()->getTargetArtifactIds();
        self::assertCount(2, $new_links);
        self::assertContains($first_linked_artifact_id, $new_links);
        self::assertContains($second_linked_artifact_id, $new_links);
    }

    public function testItThrowsWhenCreateRepresentationDoesNotHaveAFieldID(): void
    {
        $representation = new ArtifactValuesRepresentation();
        $this->expectException(\Tracker_FormElement_InvalidFieldException::class);
        $this->getFieldsDataOnCreate([$representation]);
    }

    public function testItThrowsAtCreateWhenFieldIDCantBeFoundInTracker(): void
    {
        $representation = ArtifactValuesRepresentationBuilder::aRepresentation(404)->build();

        $this->expectException(\Tracker_FormElement_InvalidFieldException::class);
        $this->getFieldsDataOnCreate([$representation]);
    }

    public function testItThrowsAtCreateWhenIntFieldHasNoValue(): void
    {
        $representation         = ArtifactValuesRepresentationBuilder::aRepresentation(self::INT_FIELD_ID)->build();
        $this->fields_retriever = RetrieveUsedFieldsStub::withFields($this->int_field);

        $this->expectException(\Tracker_FormElement_InvalidFieldValueException::class);
        $this->getFieldsDataOnCreate([$representation]);
    }

    public function testWhenRESTCreatePayloadIsEmptyItReturnsEmptyArray(): void
    {
        $changeset_values = $this->getFieldsDataOnCreate([]);
        self::assertEmpty($changeset_values->getFieldsData());
    }
}
