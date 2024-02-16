<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\Creation;

use Tracker_NoChangeException;
use Tuleap\NeverThrow\Fault;
use Tuleap\Option\Option;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\ArtifactDoesNotExistException;
use Tuleap\Tracker\Artifact\ArtifactDoesNotExistFault;
use Tuleap\Tracker\Artifact\Changeset\NewChangeset;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\CollectionOfForwardLinks;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\CollectionOfReverseLinks;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkInitialChangesetValue;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\NewParentLink;
use Tuleap\Tracker\Artifact\ChangesetValue\InitialChangesetValuesContainer;
use Tuleap\Tracker\FormElement\ArtifactLinkFieldDoesNotExistException;
use Tuleap\Tracker\FormElement\ArtifactLinkFieldDoesNotExistFault;
use Tuleap\Tracker\Semantic\SemanticNotSupportedException;
use Tuleap\Tracker\Semantic\SemanticNotSupportedFault;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Stub\CreateNewChangesetStub;
use Tuleap\Tracker\Test\Stub\ForwardLinkStub;
use Tuleap\Tracker\Test\Stub\NewParentLinkStub;
use Tuleap\Tracker\Test\Stub\ReverseLinkStub;
use Tuleap\Tracker\Test\Stub\Tracker\Artifact\ChangesetValue\ArtifactLink\ConvertAddReverseLinksStub;

final class ReverseLinksAdderTest extends TestCase
{
    private const CURRENT_ARTIFACT_ID  = 1;
    private const PARENT_ARTIFACT_ID   = 2;
    private const REQUEST_ID           = 101;
    private const TASK_ID              = 102;
    private const STORY_ID             = 103;
    private const SPRINT_ID            = 104;
    private const ARTLINK_FIELD_ID     = 1001;
    private const SUBMISSION_TIMESTAMP = 1234567890;

    public function testAddReverseLinksHappyPath(): void
    {
        $submitter = UserTestBuilder::buildWithDefaults();

        $forward_links = new CollectionOfForwardLinks([
            ForwardLinkStub::withNoType(self::STORY_ID),
            ForwardLinkStub::withNoType(self::SPRINT_ID),
        ]);

        $parent = Option::nothing(NewParentLink::class);

        $reverse_links = new CollectionOfReverseLinks([
            ReverseLinkStub::withNoType(self::REQUEST_ID),
            ReverseLinkStub::withNoType(self::TASK_ID),
        ]);

        $new_changesets_created = [
            NewChangeset::fromFieldsDataArrayWithEmptyComment(
                ArtifactTestBuilder::anArtifact(self::REQUEST_ID)->build(),
                [],
                $submitter,
                self::SUBMISSION_TIMESTAMP,
            ),
            NewChangeset::fromFieldsDataArrayWithEmptyComment(
                ArtifactTestBuilder::anArtifact(self::TASK_ID)->build(),
                [],
                $submitter,
                self::SUBMISSION_TIMESTAMP,
            ),
        ];

        $changeset_creator = CreateNewChangesetStub::withNullReturnChangeset();

        $changesets_converter = ConvertAddReverseLinksStub::willReturnListOfNewChangesets(
            $reverse_links,
            ...$new_changesets_created
        );


        (new ReverseLinksAdder(
            $changesets_converter,
            $changeset_creator,
        ))->addReverseLinks(
            $submitter,
            new InitialChangesetValuesContainer(
                [],
                Option::fromValue(
                    NewArtifactLinkInitialChangesetValue::fromParts(
                        self::ARTLINK_FIELD_ID,
                        $forward_links,
                        $parent,
                        $reverse_links,
                    ),
                ),
            ),
            ArtifactTestBuilder::anArtifact(self::CURRENT_ARTIFACT_ID)->build(),
        );

        self::assertSame(2, $changeset_creator->getCallsCount());
    }

    public function testDoesNotCreateChangesets(): void
    {
        $submitter = UserTestBuilder::buildWithDefaults();

        $forward_links = new CollectionOfForwardLinks([
            ForwardLinkStub::withNoType(self::STORY_ID),
            ForwardLinkStub::withNoType(self::SPRINT_ID),
        ]);

        $parent = Option::nothing(NewParentLink::class);

        $reverse_links = new CollectionOfReverseLinks([
            ReverseLinkStub::withNoType(self::REQUEST_ID),
            ReverseLinkStub::withNoType(self::TASK_ID),
        ]);

        $changeset_creator = CreateNewChangesetStub::withNullReturnChangeset();

        $changesets_converter = ConvertAddReverseLinksStub::willReturnEmptyListOfNewChangesets(
            $reverse_links,
        );


        (new ReverseLinksAdder(
            $changesets_converter,
            $changeset_creator,
        ))->addReverseLinks(
            $submitter,
            new InitialChangesetValuesContainer(
                [],
                Option::fromValue(
                    NewArtifactLinkInitialChangesetValue::fromParts(
                        self::ARTLINK_FIELD_ID,
                        $forward_links,
                        $parent,
                        $reverse_links,
                    ),
                ),
            ),
            ArtifactTestBuilder::anArtifact(self::CURRENT_ARTIFACT_ID)->build(),
        );

        self::assertSame(0, $changeset_creator->getCallsCount());
    }

    /**
     * @dataProvider provideFaults
     */
    public function testExceptionWhenChangesetConverterFaults(Fault $fault, \Exception $expected_exception): void
    {
        $submitter = UserTestBuilder::buildWithDefaults();

        $forward_links = new CollectionOfForwardLinks([
            ForwardLinkStub::withNoType(self::STORY_ID),
            ForwardLinkStub::withNoType(self::SPRINT_ID),
        ]);

        $parent = Option::nothing(NewParentLink::class);

        $reverse_links = new CollectionOfReverseLinks([
            ReverseLinkStub::withNoType(self::REQUEST_ID),
            ReverseLinkStub::withNoType(self::TASK_ID),
        ]);

        $changeset_creator = CreateNewChangesetStub::withNullReturnChangeset();

        $changesets_converter = ConvertAddReverseLinksStub::willFault(
            $reverse_links,
            $fault,
        );

        $this->expectException($expected_exception::class);
        $this->expectExceptionMessage($expected_exception->getMessage());

        (new ReverseLinksAdder(
            $changesets_converter,
            $changeset_creator,
        ))->addReverseLinks(
            $submitter,
            new InitialChangesetValuesContainer(
                [],
                Option::fromValue(
                    NewArtifactLinkInitialChangesetValue::fromParts(
                        self::ARTLINK_FIELD_ID,
                        $forward_links,
                        $parent,
                        $reverse_links,
                    ),
                ),
            ),
            ArtifactTestBuilder::anArtifact(self::CURRENT_ARTIFACT_ID)->build(),
        );

        self::assertSame(0, $changeset_creator->getCallsCount());
    }

    public function provideFaults(): array
    {
        return [
            [
                Fault::fromMessage("Something gone wrong"),
                new \Exception("Something gone wrong"),
            ],
            [
                ArtifactDoesNotExistFault::build(self::REQUEST_ID),
                new ArtifactDoesNotExistException("Artifact #101 does not exist"),
            ],
            [
                ArtifactLinkFieldDoesNotExistFault::build(self::REQUEST_ID),
                new ArtifactLinkFieldDoesNotExistException("Artifact link field does not exist for the artifact #101"),
            ],
            [
                SemanticNotSupportedFault::fromSemanticName("tooltip"),
                new SemanticNotSupportedException('Semantic "tooltip" not supported'),
            ],
        ];
    }

    public function testNoChangeExceptionIsSilentlyIgnoreToNotStopTheCreationOfTheArtifact(): void
    {
        $submitter = UserTestBuilder::buildWithDefaults();

        $forward_links = new CollectionOfForwardLinks([
            ForwardLinkStub::withNoType(self::STORY_ID),
            ForwardLinkStub::withNoType(self::SPRINT_ID),
        ]);

        $parent = Option::nothing(NewParentLink::class);

        $reverse_links = new CollectionOfReverseLinks([
            ReverseLinkStub::withNoType(self::REQUEST_ID),
            ReverseLinkStub::withNoType(self::TASK_ID),
        ]);

        $new_changesets_created = [
            NewChangeset::fromFieldsDataArrayWithEmptyComment(
                ArtifactTestBuilder::anArtifact(self::TASK_ID)->build(),
                [],
                $submitter,
                self::SUBMISSION_TIMESTAMP,
            ),
        ];

        $changeset_creator = CreateNewChangesetStub::withException(new Tracker_NoChangeException(self::REQUEST_ID, 'request xref'));

        $changesets_converter = ConvertAddReverseLinksStub::willReturnListOfNewChangesets(
            $reverse_links,
            ...$new_changesets_created
        );

        (new ReverseLinksAdder(
            $changesets_converter,
            $changeset_creator,
        ))->addReverseLinks(
            $submitter,
            new InitialChangesetValuesContainer(
                [],
                Option::fromValue(
                    NewArtifactLinkInitialChangesetValue::fromParts(
                        self::ARTLINK_FIELD_ID,
                        $forward_links,
                        $parent,
                        $reverse_links,
                    ),
                ),
            ),
            ArtifactTestBuilder::anArtifact(self::CURRENT_ARTIFACT_ID)->build(),
        );

        self::assertSame(1, $changeset_creator->getCallsCount());
    }

    public function testNothingHappenWhenThereIsAParent(): void
    {
        $submitter = UserTestBuilder::buildWithDefaults();

        $forward_links = new CollectionOfForwardLinks([
            ForwardLinkStub::withNoType(self::STORY_ID),
            ForwardLinkStub::withNoType(self::SPRINT_ID),
        ]);

        $parent = Option::fromValue(NewParentLinkStub::withId(self::PARENT_ARTIFACT_ID));

        $reverse_links = new CollectionOfReverseLinks([
            ReverseLinkStub::withNoType(self::REQUEST_ID),
            ReverseLinkStub::withNoType(self::TASK_ID),
        ]);

        $changeset_creator = CreateNewChangesetStub::withNullReturnChangeset();

        $new_changesets_created = [
            NewChangeset::fromFieldsDataArrayWithEmptyComment(
                ArtifactTestBuilder::anArtifact(self::REQUEST_ID)->build(),
                [],
                $submitter,
                self::SUBMISSION_TIMESTAMP,
            ),
            NewChangeset::fromFieldsDataArrayWithEmptyComment(
                ArtifactTestBuilder::anArtifact(self::TASK_ID)->build(),
                [],
                $submitter,
                self::SUBMISSION_TIMESTAMP,
            ),
        ];

        $changesets_converter = ConvertAddReverseLinksStub::willReturnListOfNewChangesets(
            $reverse_links,
            ...$new_changesets_created,
        );


        (new ReverseLinksAdder(
            $changesets_converter,
            $changeset_creator,
        ))->addReverseLinks(
            $submitter,
            new InitialChangesetValuesContainer(
                [],
                Option::fromValue(
                    NewArtifactLinkInitialChangesetValue::fromParts(
                        self::ARTLINK_FIELD_ID,
                        $forward_links,
                        $parent,
                        $reverse_links,
                    ),
                ),
            ),
            ArtifactTestBuilder::anArtifact(self::CURRENT_ARTIFACT_ID)->build(),
        );

        self::assertSame(0, $changeset_creator->getCallsCount());
    }

    public function testNothingHappenWhenThereIsNoArtifactLinkSubmitted(): void
    {
        $submitter = UserTestBuilder::buildWithDefaults();

        $changeset_creator = CreateNewChangesetStub::withNullReturnChangeset();

        $changesets_converter = ConvertAddReverseLinksStub::willReturnListOfNewChangesets(
            new CollectionOfReverseLinks([
                ReverseLinkStub::withNoType(self::REQUEST_ID),
                ReverseLinkStub::withNoType(self::TASK_ID),
            ]),
            NewChangeset::fromFieldsDataArrayWithEmptyComment(
                ArtifactTestBuilder::anArtifact(self::REQUEST_ID)->build(),
                [],
                $submitter,
                self::SUBMISSION_TIMESTAMP,
            ),
            NewChangeset::fromFieldsDataArrayWithEmptyComment(
                ArtifactTestBuilder::anArtifact(self::TASK_ID)->build(),
                [],
                $submitter,
                self::SUBMISSION_TIMESTAMP,
            ),
        );

        (new ReverseLinksAdder(
            $changesets_converter,
            $changeset_creator,
        ))->addReverseLinks(
            $submitter,
            new InitialChangesetValuesContainer(
                [],
                Option::nothing(NewArtifactLinkInitialChangesetValue::class),
            ),
            ArtifactTestBuilder::anArtifact(self::CURRENT_ARTIFACT_ID)->build(),
        );

        self::assertSame(0, $changeset_creator->getCallsCount());
    }
}
