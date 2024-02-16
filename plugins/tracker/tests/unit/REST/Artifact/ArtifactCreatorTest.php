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
use PFUser;
use Tracker;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\ArtifactDoesNotExistException;
use Tuleap\Tracker\Artifact\ChangesetValue\AddDefaultValuesToFieldsData;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\CollectionOfForwardLinks;
use Tuleap\Tracker\Artifact\ChangesetValue\InitialChangesetValuesContainer;
use Tuleap\Tracker\Artifact\Creation\TrackerArtifactCreator;
use Tuleap\Tracker\Artifact\RetrieveTracker;
use Tuleap\Tracker\Changeset\Validation\ChangesetValidationContext;
use Tuleap\Tracker\FormElement\ArtifactLinkFieldDoesNotExistException;
use Tuleap\Tracker\FormElement\Field\RetrieveUsedFields;
use Tuleap\Tracker\Permission\VerifySubmissionPermissions;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkChangesetValueBuilder;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkInitialChangesetValueBuilder;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\FieldsDataBuilder;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\FieldsDataFromValuesByFieldBuilder;
use Tuleap\Tracker\REST\TrackerReference;
use Tuleap\Tracker\Semantic\SemanticNotSupportedException;
use Tuleap\Tracker\Test\Builders\ArtifactLinkFieldBuilder;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ArtifactValuesRepresentationBuilder;
use Tuleap\Tracker\Test\Builders\LinkWithDirectionRepresentationBuilder;
use Tuleap\Tracker\Test\Builders\TrackerFormElementStringFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveForwardLinksStub;
use Tuleap\Tracker\Test\Stub\RetrieveTrackerStub;
use Tuleap\Tracker\Test\Stub\RetrieveUsedFieldsStub;
use Tuleap\Tracker\Test\Stub\VerifySubmissionPermissionStub;

final class ArtifactCreatorTest extends TestCase
{
    use GlobalResponseMock;

    private const FORMAT_NOT_BY_FIELD = '';
    private const FORMAT_BY_FIELD     = 'by_field';

    private const ARTIFACT_LINK_FIELD_ID   = 496;
    private const ARTIFACT_LINK_FIELD_NAME = 'artlink';

    private const STRING_FIELD_ID   = 497;
    private const STRING_FIELD_NAME = 'stringfield';

    /**
     * @dataProvider provideCreateCallback
     * @param \Closure(VerifySubmissionPermissions $submission_permission_verifier, RetrieveUsedFields $all_fields_retriever, TrackerArtifactCreator $artifact_creator, RetrieveTracker $tracker_factory, array $values): ArtifactReference $create
     */
    public function test404WhenTrackerDoesNotExist(\Closure $create): void
    {
        $this->expectException(RestException::class);
        $this->expectExceptionCode(404);

        $create(
            VerifySubmissionPermissionStub::withoutSubmitPermission(),
            RetrieveUsedFieldsStub::withNoFields(),
            $this->createStub(TrackerArtifactCreator::class),
            RetrieveTrackerStub::withoutTracker(),
            [],
        );
    }

    /**
     * @dataProvider provideCreateCallback
     * @param \Closure(VerifySubmissionPermissions $submission_permission_verifier, RetrieveUsedFields $all_fields_retriever, TrackerArtifactCreator $artifact_creator, RetrieveTracker $tracker_factory, array $values): ArtifactReference $create
     */
    public function test403WhenUserCannotSubmit(\Closure $create): void
    {
        $this->expectException(RestException::class);
        $this->expectExceptionCode(403);

        $create(
            VerifySubmissionPermissionStub::withoutSubmitPermission(),
            RetrieveUsedFieldsStub::withNoFields(),
            $this->createStub(TrackerArtifactCreator::class),
            RetrieveTrackerStub::withTracker(TrackerTestBuilder::aTracker()->build()),
            [],
        );
    }

    /**
     * @dataProvider provideCreateCallback
     * @param \Closure(VerifySubmissionPermissions $submission_permission_verifier, RetrieveUsedFields $all_fields_retriever, TrackerArtifactCreator $artifact_creator, RetrieveTracker $tracker_factory, array $values): ArtifactReference $create
     */
    public function test400WhenCreatorRaisesArtifactDoesNotExistException(\Closure $create): void
    {
        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $creator = $this->createMock(TrackerArtifactCreator::class);
        $creator->method('create')->willThrowException(new ArtifactDoesNotExistException());

        $create(
            VerifySubmissionPermissionStub::withSubmitPermission(),
            RetrieveUsedFieldsStub::withNoFields(),
            $creator,
            RetrieveTrackerStub::withTracker(TrackerTestBuilder::aTracker()->build()),
            [],
        );
    }

    /**
     * @dataProvider provideCreateCallback
     * @param \Closure(VerifySubmissionPermissions $submission_permission_verifier, RetrieveUsedFields $all_fields_retriever, TrackerArtifactCreator $artifact_creator, RetrieveTracker $tracker_factory, array $values): ArtifactReference $create
     */
    public function test400WhenCreatorRaisesArtifactLinkFieldDoesNotExistException(\Closure $create): void
    {
        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $creator = $this->createMock(TrackerArtifactCreator::class);
        $creator->method('create')->willThrowException(new ArtifactLinkFieldDoesNotExistException());

        $create(
            VerifySubmissionPermissionStub::withSubmitPermission(),
            RetrieveUsedFieldsStub::withNoFields(),
            $creator,
            RetrieveTrackerStub::withTracker(TrackerTestBuilder::aTracker()->build()),
            [],
        );
    }

    /**
     * @dataProvider provideCreateCallback
     * @param \Closure(VerifySubmissionPermissions $submission_permission_verifier, RetrieveUsedFields $all_fields_retriever, TrackerArtifactCreator $artifact_creator, RetrieveTracker $tracker_factory, array $values): ArtifactReference $create
     */
    public function test400WhenCreatorRaisesSemanticNotSupportedException(\Closure $create): void
    {
        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $creator = $this->createMock(TrackerArtifactCreator::class);
        $creator->method('create')->willThrowException(new SemanticNotSupportedException());

        $create(
            VerifySubmissionPermissionStub::withSubmitPermission(),
            RetrieveUsedFieldsStub::withNoFields(),
            $creator,
            RetrieveTrackerStub::withTracker(TrackerTestBuilder::aTracker()->build()),
            [],
        );
    }

    /**
     * @dataProvider provideCreateCallback
     * @param \Closure(VerifySubmissionPermissions $submission_permission_verifier, RetrieveUsedFields $all_fields_retriever, TrackerArtifactCreator $artifact_creator, RetrieveTracker $tracker_factory, array $values): ArtifactReference $create
     */
    public function test400WhenCreatorCannotCreateArtifactAndEmitsFeedback(\Closure $create): void
    {
        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $creator = $this->createMock(TrackerArtifactCreator::class);
        $creator->method('create')->willReturnCallback(function (): ?Artifact {
            $GLOBALS['Response']->method('feedbackHasErrors')->willReturn(true);

            return null;
        });

        $create(
            VerifySubmissionPermissionStub::withSubmitPermission(),
            RetrieveUsedFieldsStub::withNoFields(),
            $creator,
            RetrieveTrackerStub::withTracker(TrackerTestBuilder::aTracker()->build()),
            [],
        );
    }

    /**
     * @dataProvider provideCreateCallback
     * @param \Closure(VerifySubmissionPermissions $submission_permission_verifier, RetrieveUsedFields $all_fields_retriever, TrackerArtifactCreator $artifact_creator, RetrieveTracker $tracker_factory, array $values): ArtifactReference $create
     */
    public function test500WhenCreatorCannotCreateButDoesNotEmitFeedback(\Closure $create): void
    {
        $this->expectException(RestException::class);
        $this->expectExceptionCode(500);

        $creator = $this->createMock(TrackerArtifactCreator::class);
        $creator->method('create')->willReturnCallback(function (): ?Artifact {
            return null;
        });

        $create(
            VerifySubmissionPermissionStub::withSubmitPermission(),
            RetrieveUsedFieldsStub::withNoFields(),
            $creator,
            RetrieveTrackerStub::withTracker(TrackerTestBuilder::aTracker()->build()),
            [],
        );
    }

    /**
     * @dataProvider provideCreateCallback
     * @param \Closure(VerifySubmissionPermissions $submission_permission_verifier, RetrieveUsedFields $all_fields_retriever, TrackerArtifactCreator $artifact_creator, RetrieveTracker $tracker_factory, array $values): ArtifactReference $create
     */
    public function testItReturnsArtifactReference(\Closure $create, string $format): void
    {
        $tracker  = TrackerTestBuilder::aTracker()->withProject(ProjectTestBuilder::aProject()->build())->build();
        $artifact = ArtifactTestBuilder::anArtifact(101)->inTracker($tracker)->build();

        $creator = $this->createMock(TrackerArtifactCreator::class);
        $creator->method('create')->willReturn($artifact);

        $reference = $create(
            VerifySubmissionPermissionStub::withSubmitPermission(),
            RetrieveUsedFieldsStub::withNoFields(),
            $creator,
            RetrieveTrackerStub::withTracker($tracker),
            [],
        );

        self::assertSame($artifact->getId(), $reference->id);
        if ($format === self::FORMAT_BY_FIELD) {
            self::assertSame('artifacts/101?values_format=by_field', $reference->uri);
        } else {
            self::assertSame('artifacts/101', $reference->uri);
        }
    }

    /**
     * @dataProvider provideCreateCallback
     * @param \Closure(VerifySubmissionPermissions $submission_permission_verifier, RetrieveUsedFields $all_fields_retriever, TrackerArtifactCreator $artifact_creator, RetrieveTracker $tracker_factory, array $values): ArtifactReference $create
     */
    public function testItAsksToAddReverseLinksIfThereIsNoLinksPropertyInValues(\Closure $create, string $format): void
    {
        $tracker  = TrackerTestBuilder::aTracker()->withProject(ProjectTestBuilder::aProject()->build())->build();
        $artifact = ArtifactTestBuilder::anArtifact(101)->inTracker($tracker)->build();

        $creator = $this->createMock(TrackerArtifactCreator::class);
        $creator->method('create')
            ->willReturnCallback(
                fn (
                    Tracker $tracker,
                    InitialChangesetValuesContainer $changeset_values,
                    PFUser $user,
                    int $submitted_on,
                    bool $send_notification,
                    bool $should_visit_be_recorded,
                    ChangesetValidationContext $context,
                    bool $should_add_reverse_links,
                ): ?Artifact => match (true) {
                    $should_add_reverse_links => $artifact
                }
            );

        $reference = $create(
            VerifySubmissionPermissionStub::withSubmitPermission(),
            RetrieveUsedFieldsStub::withFields(
                ArtifactLinkFieldBuilder::anArtifactLinkField(self::ARTIFACT_LINK_FIELD_ID)
                    ->inTracker($tracker)
                    ->withName(self::ARTIFACT_LINK_FIELD_NAME)
                    ->build(),
            ),
            $creator,
            RetrieveTrackerStub::withTracker($tracker),
            match ($format) {
                self::FORMAT_BY_FIELD => [
                    self::ARTIFACT_LINK_FIELD_NAME => ArtifactValuesRepresentationBuilder::aRepresentation(self::ARTIFACT_LINK_FIELD_ID)
                        ->withAllLinks(
                            LinkWithDirectionRepresentationBuilder::aReverseLink(1)->build(),
                            LinkWithDirectionRepresentationBuilder::aReverseLink(2)->withType('custom')->build(),
                        )
                        ->build(),
                ],
                default => [
                    ArtifactValuesRepresentationBuilder::aRepresentation(self::ARTIFACT_LINK_FIELD_ID)
                        ->withAllLinks(
                            LinkWithDirectionRepresentationBuilder::aReverseLink(1)->build(),
                            LinkWithDirectionRepresentationBuilder::aReverseLink(2)->withType('custom')->build()
                        )
                        ->build(),
                ],
            },
        );

        self::assertSame($artifact->getId(), $reference->id);
    }

    /**
     * @dataProvider provideCreateCallback
     * @param \Closure(VerifySubmissionPermissions $submission_permission_verifier, RetrieveUsedFields $all_fields_retriever, TrackerArtifactCreator $artifact_creator, RetrieveTracker $tracker_factory, array $values): ArtifactReference $create
     */
    public function testItDoesNotAsksToAddReverseLinksIfThereIsLinksPropertyInValues(\Closure $create, string $format): void
    {
        $tracker  = TrackerTestBuilder::aTracker()->withProject(ProjectTestBuilder::aProject()->build())->build();
        $artifact = ArtifactTestBuilder::anArtifact(101)->inTracker($tracker)->build();

        $creator = $this->createMock(TrackerArtifactCreator::class);
        $creator->method('create')
            ->willReturnCallback(
                fn(
                    Tracker $tracker,
                    InitialChangesetValuesContainer $changeset_values,
                    PFUser $user,
                    int $submitted_on,
                    bool $send_notification,
                    bool $should_visit_be_recorded,
                    ChangesetValidationContext $context,
                    bool $should_add_reverse_links,
                ): ?Artifact => match (false) {
                    $should_add_reverse_links => $artifact
                }
            );

        $reference = $create(
            VerifySubmissionPermissionStub::withSubmitPermission(),
            RetrieveUsedFieldsStub::withFields(
                ArtifactLinkFieldBuilder::anArtifactLinkField(self::ARTIFACT_LINK_FIELD_ID)
                    ->inTracker($tracker)
                    ->withName(self::ARTIFACT_LINK_FIELD_NAME)
                    ->build(),
            ),
            $creator,
            RetrieveTrackerStub::withTracker($tracker),
            match ($format) {
                self::FORMAT_BY_FIELD => [
                    self::ARTIFACT_LINK_FIELD_NAME => ArtifactValuesRepresentationBuilder::aRepresentation(self::ARTIFACT_LINK_FIELD_ID)
                        ->withLinks(
                            ['id' => 1],
                            ['id' => 2, 'type' => 'custom'],
                        )
                        ->build(),
                ],
                default => [
                    ArtifactValuesRepresentationBuilder::aRepresentation(self::ARTIFACT_LINK_FIELD_ID)
                        ->withLinks(
                            ['id' => 1],
                            ['id' => 2, 'type' => 'custom'],
                        )
                        ->build(),
                ],
            },
        );

        self::assertSame($artifact->getId(), $reference->id);
    }

    /**
     * @dataProvider provideCreateCallback
     * @param \Closure(VerifySubmissionPermissions $submission_permission_verifier, RetrieveUsedFields $all_fields_retriever, TrackerArtifactCreator $artifact_creator, RetrieveTracker $tracker_factory, array $values): ArtifactReference $create
     */
    public function testItEncapsulateValuesInAnInitialChangesetValuesContainer(\Closure $create, string $format): void
    {
        $tracker  = TrackerTestBuilder::aTracker()->withProject(ProjectTestBuilder::aProject()->build())->build();
        $artifact = ArtifactTestBuilder::anArtifact(101)->inTracker($tracker)->build();

        $creator = $this->createMock(TrackerArtifactCreator::class);
        $creator->method('create')
            ->willReturnCallback(
                fn(
                    Tracker $tracker,
                    InitialChangesetValuesContainer $changeset_values,
                    PFUser $user,
                    int $submitted_on,
                    bool $send_notification,
                    bool $should_visit_be_recorded,
                    ChangesetValidationContext $context,
                    bool $should_add_reverse_links,
                ): ?Artifact => match (true) {
                    $changeset_values->getFieldsData()[self::STRING_FIELD_ID] === 'Tango Uniform' &&
                    $changeset_values->getArtifactLinkValue()->unwrapOr(null)->getFieldId() === self::ARTIFACT_LINK_FIELD_ID => $artifact
                }
            );

        $reference = $create(
            VerifySubmissionPermissionStub::withSubmitPermission(),
            RetrieveUsedFieldsStub::withFields(
                TrackerFormElementStringFieldBuilder::aStringField(self::STRING_FIELD_ID)
                    ->inTracker($tracker)
                    ->withName(self::STRING_FIELD_NAME)
                    ->build(),
                ArtifactLinkFieldBuilder::anArtifactLinkField(self::ARTIFACT_LINK_FIELD_ID)
                    ->inTracker($tracker)
                    ->withName(self::ARTIFACT_LINK_FIELD_NAME)
                    ->build(),
            ),
            $creator,
            RetrieveTrackerStub::withTracker($tracker),
            match ($format) {
                self::FORMAT_BY_FIELD => [
                    self::STRING_FIELD_NAME => ArtifactValuesRepresentationBuilder::aRepresentation(self::STRING_FIELD_ID)
                        ->withValue('Tango Uniform')
                        ->build()->toArray(),
                    self::ARTIFACT_LINK_FIELD_NAME => ArtifactValuesRepresentationBuilder::aRepresentation(self::ARTIFACT_LINK_FIELD_ID)
                        ->withAllLinks(
                            LinkWithDirectionRepresentationBuilder::aReverseLink(1)->build(),
                            LinkWithDirectionRepresentationBuilder::aReverseLink(2)->withType('custom')->build()
                        )
                        ->build(),
                ],
                default => [
                    ArtifactValuesRepresentationBuilder::aRepresentation(self::STRING_FIELD_ID)
                        ->withValue('Tango Uniform')
                        ->build(),
                    ArtifactValuesRepresentationBuilder::aRepresentation(self::ARTIFACT_LINK_FIELD_ID)
                        ->withAllLinks(
                            LinkWithDirectionRepresentationBuilder::aReverseLink(1)->build(),
                            LinkWithDirectionRepresentationBuilder::aReverseLink(2)->withType('custom')->build()
                        )->build(),
                ],
            },
        );

        self::assertSame($artifact->getId(), $reference->id);
    }

    /**
     * @return list<array{0: \Closure(VerifySubmissionPermissions $submission_permission_verifier, RetrieveUsedFields $all_fields_retriever, TrackerArtifactCreator $artifact_creator, RetrieveTracker $tracker_factory, array $values): ArtifactReference, 1: string}>
     */
    public function provideCreateCallback(): array
    {
        $tracker = TrackerTestBuilder::aTracker()
            ->withProject(ProjectTestBuilder::aProject()->build())
            ->build();

        $tracker_reference = TrackerReference::build($tracker);

        return [
            [
                fn (
                    VerifySubmissionPermissions $submission_permission_verifier,
                    RetrieveUsedFields $all_fields_retriever,
                    TrackerArtifactCreator $artifact_creator,
                    RetrieveTracker $tracker_factory,
                    array $values,
                ) => $this->getCreator(
                    $submission_permission_verifier,
                    $all_fields_retriever,
                    $artifact_creator,
                    $tracker_factory,
                )->create(UserTestBuilder::buildWithDefaults(), $tracker_reference, $values, true),
                self::FORMAT_NOT_BY_FIELD,
            ],
            [
                fn (
                    VerifySubmissionPermissions $submission_permission_verifier,
                    RetrieveUsedFields $all_fields_retriever,
                    TrackerArtifactCreator $artifact_creator,
                    RetrieveTracker $tracker_factory,
                    array $values,
                ) => $this->getCreator(
                    $submission_permission_verifier,
                    $all_fields_retriever,
                    $artifact_creator,
                    $tracker_factory,
                )->createWithValuesIndexedByFieldName(UserTestBuilder::buildWithDefaults(), $tracker_reference, $values),
                self::FORMAT_BY_FIELD,
            ],
        ];
    }

    private function getCreator(
        VerifySubmissionPermissions $submission_permission_verifier,
        RetrieveUsedFields $all_fields_retriever,
        TrackerArtifactCreator $artifact_creator,
        RetrieveTracker $tracker_factory,
    ): ArtifactCreator {
        $default_values_adder = $this->createMock(AddDefaultValuesToFieldsData::class);
        $default_values_adder->method("getUsedFieldsWithDefaultValue")
            ->willReturnCallback(static fn (Tracker $tracker, array $fields_data, PFUser $user): array => $fields_data);

        $artifact_link_initial_builder = new NewArtifactLinkInitialChangesetValueBuilder();

        return new ArtifactCreator(
            new FieldsDataBuilder(
                $all_fields_retriever,
                new NewArtifactLinkChangesetValueBuilder(
                    RetrieveForwardLinksStub::withLinks(new CollectionOfForwardLinks([])),
                ),
                $artifact_link_initial_builder,
            ),
            $artifact_creator,
            $tracker_factory,
            new FieldsDataFromValuesByFieldBuilder($all_fields_retriever, $artifact_link_initial_builder),
            $default_values_adder,
            $submission_permission_verifier,
        );
    }
}
