<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Artifact\Link;

use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Option\Option;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Changeset\Comment\NewComment;
use Tuleap\Tracker\Artifact\Changeset\NewChangeset;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ChangeForwardLinksCommand;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\CollectionOfReverseLinks;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkChangesetValue;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\NewParentLink;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ReverseLinksToNewChangesetsConverter;
use Tuleap\Tracker\Artifact\ChangesetValue\ChangesetValuesContainer;
use Tuleap\Tracker\Artifact\Exception\FieldValidationException;
use Tuleap\Tracker\Test\Builders\ArtifactLinkFieldBuilder;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Stub\CreateNewChangesetStub;
use Tuleap\Tracker\Test\Stub\RetrieveReverseLinksStub;
use Tuleap\Tracker\Test\Stub\RetrieveUsedArtifactLinkFieldsStub;
use Tuleap\Tracker\Test\Stub\RetrieveViewableArtifactStub;
use Tuleap\Tracker\Test\Stub\ReverseLinkStub;

final class ArtifactReverseLinksUpdaterTest extends TestCase
{
    private const CURRENT_ARTIFACT_ID  = 10;
    private const SOURCE_ARTIFACT_ID   = 18;
    private const SOURCE_ARTIFACT_ID_2 = 36;
    private const CURRENT_FIELD_ID     = 833;

    private CreateNewChangesetStub $changeset_creator;
    private RetrieveReverseLinksStub $reverse_links_retriever;
    /** @var Option<NewArtifactLinkChangesetValue> */
    private Option $link_value;

    protected function setUp(): void
    {
        $this->changeset_creator       = CreateNewChangesetStub::withNullReturnChangeset();
        $this->reverse_links_retriever = RetrieveReverseLinksStub::withoutLinks();

        $this->link_value = Option::nothing(NewArtifactLinkChangesetValue::class);
    }

    /**
     * @return Ok<null> | Err<Fault>
     * @throws \Tracker_Exception
     * @throws FieldValidationException
     */
    private function update(): Ok|Err
    {
        $artifact        = ArtifactTestBuilder::anArtifact(self::CURRENT_ARTIFACT_ID)->build();
        $submitter       = UserTestBuilder::buildWithDefaults();
        $submission_date = new \DateTimeImmutable();

        $artifact_retriever = RetrieveViewableArtifactStub::withSuccessiveArtifacts(
            ArtifactTestBuilder::anArtifact(self::SOURCE_ARTIFACT_ID)->build(),
            ArtifactTestBuilder::anArtifact(self::SOURCE_ARTIFACT_ID_2)->build(),
        );
        $field_retriever    = RetrieveUsedArtifactLinkFieldsStub::withSuccessiveFields(
            ArtifactLinkFieldBuilder::anArtifactLinkField(417)->build(),
            ArtifactLinkFieldBuilder::anArtifactLinkField(169)->build(),
        );

        $handler = new ArtifactReverseLinksUpdater(
            $this->reverse_links_retriever,
            new ReverseLinksToNewChangesetsConverter($field_retriever, $artifact_retriever),
            $this->changeset_creator,
        );
        return $handler->updateArtifactAndItsLinks(
            $artifact,
            new ChangesetValuesContainer([], $this->link_value),
            $submitter,
            $submission_date,
            NewComment::buildEmpty($submitter, $submission_date->getTimestamp())
        );
    }

    public function testWhenThereIsNoValueForArtifactLinkItOnlyUpdatesGivenArtifact(): void
    {
        $result = $this->update();
        self::assertTrue(Result::isOk($result));
        self::assertNull($result->value);
        self::assertSame(1, $this->changeset_creator->getCallsCount());
    }

    public function testWhenThereAreNoReverseLinksItOnlyUpdatesGivenArtifact(): void
    {
        $this->link_value = Option::fromValue(
            NewArtifactLinkChangesetValue::fromOnlyForwardLinks(
                ChangeForwardLinksCommand::buildNoChange(self::CURRENT_FIELD_ID)
            )
        );

        $result = $this->update();
        self::assertTrue(Result::isOk($result));
        self::assertNull($result->value);
        self::assertSame(1, $this->changeset_creator->getCallsCount());
    }

    public function testWhenReverseLinksDidNotChangeItOnlyUpdatesGivenArtifact(): void
    {
        $reverse_links                 = new CollectionOfReverseLinks([
            ReverseLinkStub::withType(self::SOURCE_ARTIFACT_ID, '_is_child'),
            ReverseLinkStub::withNoType(self::SOURCE_ARTIFACT_ID_2),
        ]);
        $this->reverse_links_retriever = RetrieveReverseLinksStub::withLinks($reverse_links);

        $this->link_value = Option::fromValue(
            NewArtifactLinkChangesetValue::fromParts(
                ChangeForwardLinksCommand::buildNoChange(self::CURRENT_FIELD_ID),
                Option::nothing(NewParentLink::class),
                Option::fromValue($reverse_links)
            )
        );

        $result = $this->update();
        self::assertTrue(Result::isOk($result));
        self::assertNull($result->value);
        self::assertSame(1, $this->changeset_creator->getCallsCount());
    }

    public function testWhenThereAreReverseLinksItUpdatesSourceArtifactsAsWell(): void
    {
        $this->link_value = Option::fromValue(
            NewArtifactLinkChangesetValue::fromParts(
                ChangeForwardLinksCommand::buildNoChange(self::CURRENT_FIELD_ID),
                Option::nothing(NewParentLink::class),
                Option::fromValue(
                    new CollectionOfReverseLinks([
                        ReverseLinkStub::withType(self::SOURCE_ARTIFACT_ID, '_is_child'),
                        ReverseLinkStub::withNoType(self::SOURCE_ARTIFACT_ID_2),
                    ])
                )
            )
        );

        $result = $this->update();
        self::assertTrue(Result::isOk($result));
        self::assertNull($result->value);
        self::assertSame(3, $this->changeset_creator->getCallsCount());
    }

    public function testItContinuesTheUpdatingOfReverseLinkWhenASourceArtifactIsNotModified(): void
    {
        $this->changeset_creator = CreateNewChangesetStub::withCallback(static function (NewChangeset $new_changeset) {
            if ($new_changeset->getArtifact()->getId() === self::SOURCE_ARTIFACT_ID_2) {
                throw new \Tracker_NoChangeException(
                    self::SOURCE_ARTIFACT_ID_2,
                    sprintf('art #%d', self::SOURCE_ARTIFACT_ID_2)
                );
            }
            return ChangesetTestBuilder::aChangeset('658')->build();
        });

        $this->link_value = Option::fromValue(
            NewArtifactLinkChangesetValue::fromParts(
                ChangeForwardLinksCommand::buildNoChange(self::CURRENT_FIELD_ID),
                Option::nothing(NewParentLink::class),
                Option::fromValue(
                    new CollectionOfReverseLinks([
                        ReverseLinkStub::withType(self::SOURCE_ARTIFACT_ID, '_is_child'),
                        ReverseLinkStub::withNoType(self::SOURCE_ARTIFACT_ID_2),
                    ])
                )
            )
        );

        $result = $this->update();
        self::assertTrue(Result::isOk($result));
        self::assertNull($result->value);
        self::assertSame(3, $this->changeset_creator->getCallsCount());
    }
}
