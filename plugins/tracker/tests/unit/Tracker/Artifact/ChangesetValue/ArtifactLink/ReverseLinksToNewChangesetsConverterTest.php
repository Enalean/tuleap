<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Artifact\ChangesetValue\ArtifactLink;

use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\NewChangeset;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\AddReverseLinksCommand;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ChangeReverseLinksCommand;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\CollectionOfReverseLinks;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ReverseLinksToNewChangesetsConverter;
use Tuleap\Tracker\Test\Builders\ArtifactLinkFieldBuilder;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveUsedArtifactLinkFieldsStub;
use Tuleap\Tracker\Test\Stub\RetrieveViewableArtifactStub;
use Tuleap\Tracker\Test\Stub\ReverseLinkStub;

final class ReverseLinksToNewChangesetsConverterTest extends TestCase
{
    private const ADDED_ARTIFACT_ID     = 245;
    private const ADDED_TYPE            = 'is_child';
    private const ADDED_ARTIFACT_ID_2   = 271;
    private const CHANGED_ARTIFACT_ID   = 857;
    private const CHANGED_TYPE          = 'custom';
    private const REMOVED_ARTIFACT_ID   = 269;
    private const SUBMISSION_TIMESTAMP  = 1826140922;
    private const ADDED_LINK_FIELD_ID   = 500;
    private const CHANGED_LINK_FIELD_ID = 285;
    private const REMOVED_LINK_FIELD_ID = 578;
    private const TARGET_ARTIFACT_ID    = 186;
    private RetrieveViewableArtifactStub $artifact_retriever;
    private RetrieveUsedArtifactLinkFieldsStub $field_retriever;
    private Artifact $target_artifact;
    private \PFUser $submitter;
    private AddReverseLinksCommand $add_command;
    private ChangeReverseLinksCommand $change_command;

    protected function setUp(): void
    {
        $this->artifact_retriever = RetrieveViewableArtifactStub::withNoArtifact();
        $this->field_retriever    = RetrieveUsedArtifactLinkFieldsStub::withNoField();

        $this->target_artifact = ArtifactTestBuilder::anArtifact(self::TARGET_ARTIFACT_ID)->build();
        $this->submitter       = UserTestBuilder::buildWithDefaults();

        $this->add_command = AddReverseLinksCommand::fromParts(
            $this->target_artifact,
            new CollectionOfReverseLinks([
                ReverseLinkStub::withType(self::ADDED_ARTIFACT_ID, self::ADDED_TYPE),
                ReverseLinkStub::withNoType(self::ADDED_ARTIFACT_ID_2),
            ])
        );

        $this->change_command = ChangeReverseLinksCommand::fromSubmittedAndExistingLinks(
            $this->target_artifact,
            new CollectionOfReverseLinks([
                ReverseLinkStub::withNoType(self::ADDED_ARTIFACT_ID),
                ReverseLinkStub::withType(self::CHANGED_ARTIFACT_ID, self::CHANGED_TYPE),
            ]),
            new CollectionOfReverseLinks([
                ReverseLinkStub::withNoType(self::CHANGED_ARTIFACT_ID),
                ReverseLinkStub::withType(self::REMOVED_ARTIFACT_ID, '_is_child'),
            ])
        );
    }

    /**
     * @return Ok<list<NewChangeset>> | Err<Fault>
     */
    private function convertAdd(): Ok|Err
    {
        $converter = new ReverseLinksToNewChangesetsConverter(
            $this->field_retriever,
            $this->artifact_retriever
        );
        return $converter->convertAddReverseLinks(
            $this->add_command,
            $this->submitter,
            new \DateTimeImmutable('@' . self::SUBMISSION_TIMESTAMP)
        );
    }

    public function testAddReturnsOkWithEmptyArrayWhenNoChange(): void
    {
        $this->add_command = AddReverseLinksCommand::fromParts(
            $this->target_artifact,
            new CollectionOfReverseLinks([])
        );

        $result = $this->convertAdd();
        self::assertTrue(Result::isOk($result));
        self::assertEmpty($result->value);
    }

    public function testAddReturnsErrWhenUserCannotReadSourceArtifactOfReverseLink(): void
    {
        $result = $this->convertAdd();
        self::assertTrue(Result::isErr($result));
    }

    public function testAddReturnsErrWhenNoArtifactLinkFieldInSourceOfReverseLink(): void
    {
        $this->artifact_retriever = RetrieveViewableArtifactStub::withSuccessiveArtifacts(
            ArtifactTestBuilder::anArtifact(self::ADDED_ARTIFACT_ID)->build(),
        );

        $result = $this->convertAdd();
        self::assertTrue(Result::isErr($result));
    }

    public function testAddReturnsANewChangesetForEachReverseLink(): void
    {
        $this->artifact_retriever = RetrieveViewableArtifactStub::withSuccessiveArtifacts(
            ArtifactTestBuilder::anArtifact(self::ADDED_ARTIFACT_ID)->build(),
            ArtifactTestBuilder::anArtifact(self::ADDED_ARTIFACT_ID_2)->build(),
        );
        $this->field_retriever    = RetrieveUsedArtifactLinkFieldsStub::withSuccessiveFields(
            ArtifactLinkFieldBuilder::anArtifactLinkField(self::ADDED_LINK_FIELD_ID)->build(),
            ArtifactLinkFieldBuilder::anArtifactLinkField(self::ADDED_LINK_FIELD_ID)->build(),
        );

        $result = $this->convertAdd();
        self::assertTrue(Result::isOk($result));
        self::assertCount(2, $result->value);
        /** @var list<NewChangeset> $new_changesets */
        $new_changesets = $result->value;

        [$first_added_changeset, $second_added_changeset] = $new_changesets;
        self::assertSame(self::ADDED_ARTIFACT_ID, $first_added_changeset->getArtifact()->getId());
        self::assertSame($this->submitter, $first_added_changeset->getSubmitter());
        self::assertSame('', $first_added_changeset->getComment()->getBody());
        self::assertSame(self::SUBMISSION_TIMESTAMP, $first_added_changeset->getSubmissionTimestamp());
        self::assertSame([
            self::ADDED_LINK_FIELD_ID => [
                'new_values'     => (string) self::TARGET_ARTIFACT_ID,
                'removed_values' => [],
                'types'          => [self::TARGET_ARTIFACT_ID => self::ADDED_TYPE],
            ],
        ], $first_added_changeset->getFieldsData());

        self::assertSame(self::ADDED_ARTIFACT_ID_2, $second_added_changeset->getArtifact()->getId());
        self::assertSame($this->submitter, $second_added_changeset->getSubmitter());
        self::assertSame('', $second_added_changeset->getComment()->getBody());
        self::assertSame(self::SUBMISSION_TIMESTAMP, $second_added_changeset->getSubmissionTimestamp());
        self::assertSame([
            self::ADDED_LINK_FIELD_ID => [
                'new_values'     => (string) self::TARGET_ARTIFACT_ID,
                'removed_values' => [],
                'types'          => [self::TARGET_ARTIFACT_ID => \Tracker_FormElement_Field_ArtifactLink::NO_TYPE],
            ],
        ], $second_added_changeset->getFieldsData());
    }

    /**
     * @return Ok<list<NewChangeset>> | Err<Fault>
     */
    private function convertChange(): Ok|Err
    {
        $converter = new ReverseLinksToNewChangesetsConverter(
            $this->field_retriever,
            $this->artifact_retriever
        );
        return $converter->convertChangeReverseLinks(
            $this->change_command,
            $this->submitter,
            new \DateTimeImmutable('@' . self::SUBMISSION_TIMESTAMP)
        );
    }

    public function testChangeReturnsOkWithEmptyArrayWhenNoChange(): void
    {
        $this->change_command = ChangeReverseLinksCommand::buildNoChange($this->target_artifact);

        $result = $this->convertChange();
        self::assertTrue(Result::isOk($result));
        self::assertEmpty($result->value);
    }

    public function testItReturnsErrWhenUserCannotReadSourceArtifactOfReverseLink(): void
    {
        $result = $this->convertChange();
        self::assertTrue(Result::isErr($result));
    }

    public function testItReturnsErrWhenNoArtifactLinkFieldInSourceOfReverseLink(): void
    {
        $this->artifact_retriever = RetrieveViewableArtifactStub::withSuccessiveArtifacts(
            ArtifactTestBuilder::anArtifact(self::ADDED_ARTIFACT_ID)->build(),
        );

        $result = $this->convertChange();
        self::assertTrue(Result::isErr($result));
    }

    public function testItReturnsANewChangesetForEachReverseLink(): void
    {
        $this->artifact_retriever = RetrieveViewableArtifactStub::withSuccessiveArtifacts(
            ArtifactTestBuilder::anArtifact(self::ADDED_ARTIFACT_ID)->build(),
            ArtifactTestBuilder::anArtifact(self::CHANGED_ARTIFACT_ID)->build(),
            ArtifactTestBuilder::anArtifact(self::REMOVED_ARTIFACT_ID)->build(),
        );
        $this->field_retriever    = RetrieveUsedArtifactLinkFieldsStub::withSuccessiveFields(
            ArtifactLinkFieldBuilder::anArtifactLinkField(self::ADDED_LINK_FIELD_ID)->build(),
            ArtifactLinkFieldBuilder::anArtifactLinkField(self::CHANGED_LINK_FIELD_ID)->build(),
            ArtifactLinkFieldBuilder::anArtifactLinkField(self::REMOVED_LINK_FIELD_ID)->build(),
        );

        $result = $this->convertChange();
        self::assertTrue(Result::isOk($result));
        self::assertCount(3, $result->value);
        /** @var list<NewChangeset> $new_changesets */
        $new_changesets = $result->value;

        [$new_changeset_add_link, $new_changeset_change_link, $new_changeset_remove_link] = $new_changesets;
        self::assertSame(self::ADDED_ARTIFACT_ID, $new_changeset_add_link->getArtifact()->getId());
        self::assertSame($this->submitter, $new_changeset_add_link->getSubmitter());
        self::assertSame('', $new_changeset_add_link->getComment()->getBody());
        self::assertSame(self::SUBMISSION_TIMESTAMP, $new_changeset_add_link->getSubmissionTimestamp());
        self::assertSame([
            self::ADDED_LINK_FIELD_ID => [
                'new_values'     => (string) self::TARGET_ARTIFACT_ID,
                'removed_values' => [],
                'types'          => [self::TARGET_ARTIFACT_ID => \Tracker_FormElement_Field_ArtifactLink::NO_TYPE],
            ],
        ], $new_changeset_add_link->getFieldsData());

        self::assertSame(self::CHANGED_ARTIFACT_ID, $new_changeset_change_link->getArtifact()->getId());
        self::assertSame($this->submitter, $new_changeset_change_link->getSubmitter());
        self::assertSame('', $new_changeset_change_link->getComment()->getBody());
        self::assertSame(self::SUBMISSION_TIMESTAMP, $new_changeset_change_link->getSubmissionTimestamp());
        self::assertSame([
            self::CHANGED_LINK_FIELD_ID => [
                'new_values'     => '',
                'removed_values' => [],
                'types'          => [self::TARGET_ARTIFACT_ID => self::CHANGED_TYPE],
            ],
        ], $new_changeset_change_link->getFieldsData());

        self::assertSame(self::REMOVED_ARTIFACT_ID, $new_changeset_remove_link->getArtifact()->getId());
        self::assertSame($this->submitter, $new_changeset_remove_link->getSubmitter());
        self::assertSame('', $new_changeset_remove_link->getComment()->getBody());
        self::assertSame(self::SUBMISSION_TIMESTAMP, $new_changeset_remove_link->getSubmissionTimestamp());
        self::assertSame([
            self::REMOVED_LINK_FIELD_ID => [
                'new_values'     => '',
                'removed_values' => [self::TARGET_ARTIFACT_ID => [self::TARGET_ARTIFACT_ID]],
            ],
        ], $new_changeset_remove_link->getFieldsData());
    }
}
