<?php
/**
 * Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\Artifact;

use Luracast\Restler\RestException;
use PHPUnit\Framework\MockObject\Stub;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Changeset\NewChangeset;
use Tuleap\Tracker\Artifact\ChangesetValue\AddDefaultValuesToFieldsData;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\CollectionOfForwardLinks;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ReverseLinksToNewChangesetsConverter;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkChangesetValueBuilder;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkInitialChangesetValueBuilder;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\FieldsDataBuilder;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\FieldsDataFromValuesByFieldBuilder;
use Tuleap\Tracker\REST\TrackerReference;
use Tuleap\Tracker\Test\Builders\ArtifactLinkFieldBuilder;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ArtifactValuesRepresentationBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\LinkWithDirectionRepresentationBuilder;
use Tuleap\Tracker\Test\Builders\TrackerFormElementStringFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\CreateNewChangesetStub;
use Tuleap\Tracker\Test\Stub\RetrieveForwardLinksStub;
use Tuleap\Tracker\Test\Stub\RetrieveTrackerStub;
use Tuleap\Tracker\Test\Stub\RetrieveUsedArtifactLinkFieldsStub;
use Tuleap\Tracker\Test\Stub\RetrieveUsedFieldsStub;
use Tuleap\Tracker\Test\Stub\RetrieveViewableArtifactStub;
use Tuleap\Tracker\Test\Stub\VerifySubmissionPermissionStub;

final class ArtifactCreatorTest extends TestCase
{
    use GlobalResponseMock;

    private const ARTIFACT_LINK_FIELD_ID = 496;

    private \Tracker_ArtifactFactory & Stub $artifact_factory;
    private RetrieveUsedFieldsStub $all_fields_retriever;
    private RetrieveViewableArtifactStub $artifact_retriever;
    private RetrieveUsedArtifactLinkFieldsStub $link_field_retriever;
    private CreateNewChangesetStub $changeset_creator;
    private array $payload;
    private \PFUser $submitter;
    private \Tracker $tracker;
    private TrackerReference $tracker_reference;

    protected function setUp(): void
    {
        $this->tracker = TrackerTestBuilder::aTracker()
            ->withProject(ProjectTestBuilder::aProject()->build())
            ->build();

        $this->artifact_factory = $this->createStub(\Tracker_ArtifactFactory::class);

        $this->all_fields_retriever = RetrieveUsedFieldsStub::withNoFields();
        $this->artifact_retriever   = RetrieveViewableArtifactStub::withNoArtifact();
        $this->link_field_retriever = RetrieveUsedArtifactLinkFieldsStub::withNoField();
        $this->changeset_creator    = CreateNewChangesetStub::withNullReturnChangeset();

        $this->submitter         = UserTestBuilder::buildWithDefaults();
        $this->tracker_reference = TrackerReference::build($this->tracker);
    }

    private function getCreator(): ArtifactCreator
    {
        $default_values_adder = $this->createStub(AddDefaultValuesToFieldsData::class);
        $default_values_adder->method("getUsedFieldsWithDefaultValue")->willReturn([]);

        $artifact_link_initial_builder = new NewArtifactLinkInitialChangesetValueBuilder();
        return new ArtifactCreator(
            new FieldsDataBuilder(
                $this->all_fields_retriever,
                new NewArtifactLinkChangesetValueBuilder(
                    RetrieveForwardLinksStub::withLinks(new CollectionOfForwardLinks([])),
                ),
                $artifact_link_initial_builder,
            ),
            $this->artifact_factory,
            RetrieveTrackerStub::withTracker($this->tracker),
            new FieldsDataFromValuesByFieldBuilder($this->all_fields_retriever, $artifact_link_initial_builder),
            $default_values_adder,
            VerifySubmissionPermissionStub::withSubmitPermission(),
            new ReverseLinksToNewChangesetsConverter($this->link_field_retriever, $this->artifact_retriever),
            $this->changeset_creator
        );
    }

    /**
     * @throws RestException
     */
    private function create(): void
    {
        $this->getCreator()->create($this->submitter, $this->tracker_reference, $this->payload, true);
    }

    public function testItCreatesArtifactWithoutArtifactLink(): void
    {
        $string_field_id = 641;
        $this->payload   = [
            ArtifactValuesRepresentationBuilder::aRepresentation($string_field_id)->withValue('wake')->build(),
        ];

        $this->all_fields_retriever = RetrieveUsedFieldsStub::withFields(
            TrackerFormElementStringFieldBuilder::aStringField($string_field_id)->build()
        );

        $newly_created_artifact = ArtifactTestBuilder::anArtifact(27)->inTracker($this->tracker)->build();
        $this->artifact_factory->method('createArtifact')->willReturn($newly_created_artifact);

        $this->create();
        self::assertSame(0, $this->changeset_creator->getCallsCount());
    }

    public function testItCreatesArtifactAndAddReverseLinks(): void
    {
        $first_artifact_id  = 649;
        $second_artifact_id = 860;
        $this->payload      = [
            ArtifactValuesRepresentationBuilder::aRepresentation(self::ARTIFACT_LINK_FIELD_ID)
                ->withAllLinks(
                    LinkWithDirectionRepresentationBuilder::aReverseLink($first_artifact_id)->build(),
                    LinkWithDirectionRepresentationBuilder::aReverseLink($second_artifact_id)
                        ->withType('custom')
                        ->build()
                )
                ->build(),
        ];

        $this->all_fields_retriever = RetrieveUsedFieldsStub::withFields(
            ArtifactLinkFieldBuilder::anArtifactLinkField(self::ARTIFACT_LINK_FIELD_ID)->build(),
        );

        $newly_created_artifact = ArtifactTestBuilder::anArtifact(1)->inTracker($this->tracker)->build();
        $this->artifact_factory->method('createArtifact')->willReturn($newly_created_artifact);

        $this->artifact_retriever = RetrieveViewableArtifactStub::withSuccessiveArtifacts(
            ArtifactTestBuilder::anArtifact($first_artifact_id)->build(),
            ArtifactTestBuilder::anArtifact($second_artifact_id)->build(),
        );

        $this->link_field_retriever = RetrieveUsedArtifactLinkFieldsStub::withSuccessiveFields(
            ArtifactLinkFieldBuilder::anArtifactLinkField(144)->build(),
            ArtifactLinkFieldBuilder::anArtifactLinkField(975)->build(),
        );

        $this->create();
        self::assertSame(2, $this->changeset_creator->getCallsCount());
    }

    public function testItContinuesUpdatingReverseLinksWhenASourceArtifactIsNotModified(): void
    {
        $first_artifact_id       = 186;
        $second_artifact_id      = 907;
        $this->changeset_creator = CreateNewChangesetStub::withCallback(
            static function (NewChangeset $new_changeset) use ($first_artifact_id) {
                if ($new_changeset->getArtifact()->getId() === $first_artifact_id) {
                    throw new \Tracker_NoChangeException($first_artifact_id, sprintf('art #%d', $first_artifact_id));
                }
                return ChangesetTestBuilder::aChangeset('840')->build();
            }
        );

        $this->payload = [
            ArtifactValuesRepresentationBuilder::aRepresentation(self::ARTIFACT_LINK_FIELD_ID)
                ->withAllLinks(
                    LinkWithDirectionRepresentationBuilder::aReverseLink($first_artifact_id)->build(),
                    LinkWithDirectionRepresentationBuilder::aReverseLink($second_artifact_id)
                        ->withType('custom')
                        ->build()
                )
                ->build(),
        ];

        $this->all_fields_retriever = RetrieveUsedFieldsStub::withFields(
            ArtifactLinkFieldBuilder::anArtifactLinkField(self::ARTIFACT_LINK_FIELD_ID)->build(),
        );

        $newly_created_artifact = ArtifactTestBuilder::anArtifact(1)->inTracker($this->tracker)->build();
        $this->artifact_factory->method('createArtifact')->willReturn($newly_created_artifact);

        $this->artifact_retriever = RetrieveViewableArtifactStub::withSuccessiveArtifacts(
            ArtifactTestBuilder::anArtifact($first_artifact_id)->build(),
            ArtifactTestBuilder::anArtifact($second_artifact_id)->build(),
        );

        $this->link_field_retriever = RetrieveUsedArtifactLinkFieldsStub::withSuccessiveFields(
            ArtifactLinkFieldBuilder::anArtifactLinkField(144)->build(),
            ArtifactLinkFieldBuilder::anArtifactLinkField(975)->build(),
        );

        $this->create();
        self::assertSame(2, $this->changeset_creator->getCallsCount());
    }

    /**
     * @throws RestException
     */
    private function createValuesByField(): void
    {
        $this->getCreator()->createWithValuesIndexedByFieldName(
            $this->submitter,
            $this->tracker_reference,
            $this->payload
        );
    }

    public function testItCreatesArtifactWithValuesByFieldNameWithoutArtifactLink(): void
    {
        $string_field_name = 'title';
        $string_field_id   = 616;
        $this->payload     = [
            $string_field_name => ArtifactValuesRepresentationBuilder::aRepresentation($string_field_id)
                ->withValue('Tango Uniform')
                ->build()
                ->toArray(),
        ];

        $this->all_fields_retriever = RetrieveUsedFieldsStub::withFields(
            TrackerFormElementStringFieldBuilder::aStringField($string_field_id)->withName($string_field_name)->build()
        );

        $newly_created_artifact = ArtifactTestBuilder::anArtifact(42)->inTracker($this->tracker)->build();
        $this->artifact_factory->method('createArtifact')->willReturn($newly_created_artifact);

        $this->createValuesByField();
        self::assertSame(0, $this->changeset_creator->getCallsCount());
    }

    public function testItCreatesArtifactWithValuesByFieldNameAndAddReverseLinks(): void
    {
        $link_field_name    = 'links';
        $first_artifact_id  = 649;
        $second_artifact_id = 860;
        $this->payload      = [
            $link_field_name => ArtifactValuesRepresentationBuilder::aRepresentation(self::ARTIFACT_LINK_FIELD_ID)
                ->withAllLinks(
                    LinkWithDirectionRepresentationBuilder::aReverseLink($first_artifact_id)->build(),
                    LinkWithDirectionRepresentationBuilder::aReverseLink($second_artifact_id)
                        ->withType('custom')
                        ->build()
                )
                ->build(),
        ];

        $this->all_fields_retriever = RetrieveUsedFieldsStub::withFields(
            ArtifactLinkFieldBuilder::anArtifactLinkField(self::ARTIFACT_LINK_FIELD_ID)
                ->withName($link_field_name)
                ->build(),
        );

        $newly_created_artifact = ArtifactTestBuilder::anArtifact(1)->inTracker($this->tracker)->build();
        $this->artifact_factory->method('createArtifact')->willReturn($newly_created_artifact);

        $this->artifact_retriever = RetrieveViewableArtifactStub::withSuccessiveArtifacts(
            ArtifactTestBuilder::anArtifact($first_artifact_id)->build(),
            ArtifactTestBuilder::anArtifact($second_artifact_id)->build(),
        );

        $this->link_field_retriever = RetrieveUsedArtifactLinkFieldsStub::withSuccessiveFields(
            ArtifactLinkFieldBuilder::anArtifactLinkField(144)->build(),
            ArtifactLinkFieldBuilder::anArtifactLinkField(975)->build(),
        );

        $this->createValuesByField();
        self::assertSame(2, $this->changeset_creator->getCallsCount());
    }
}
