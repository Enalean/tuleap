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

use EventManager;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\ArtifactDoesNotExistFault;
use Tuleap\Tracker\Artifact\Changeset\NewChangeset;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\CollectionOfReverseLinks;
use Tuleap\Tracker\Test\Builders\ArtifactLinkFieldBuilder;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Stub\CreateNewChangesetStub;
use Tuleap\Tracker\Test\Stub\RetrieveUsedArtifactLinkFieldsStub;
use Tuleap\Tracker\Test\Stub\RetrieveViewableArtifactStub;
use Tuleap\Tracker\Test\Stub\ReverseLinkStub;

final class ArtifactUpdateHandlerTest extends TestCase
{
    private const CURRENT_ARTIFACT_ID  = 10;
    private const SOURCE_ARTIFACT_ID   = 18;
    private const SOURCE_ARTIFACT_ID_2 = 36;
    private const ARTIFACT_TYPE        = "_is_child";

    private RetrieveUsedArtifactLinkFieldsStub $form_element_factory;
    private CreateNewChangesetStub $changeset_creator;
    private RetrieveViewableArtifactStub $artifact_retriever;

    private EventManager|MockObject $event;

    protected function setUp(): void
    {
        $this->form_element_factory = RetrieveUsedArtifactLinkFieldsStub::withSuccessiveFields(
            ArtifactLinkFieldBuilder::anArtifactLinkField(15)->build(),
            ArtifactLinkFieldBuilder::anArtifactLinkField(987)->build()
        );
        $this->changeset_creator    = CreateNewChangesetStub::withNullReturnChangeset();
        $this->artifact_retriever   = RetrieveViewableArtifactStub::withNoArtifact();
    }

    /**
     * @return Ok<null>|Err<Fault>
     */
    private function unlinkReverseArtifact(): Ok|Err
    {
        $artifact              = ArtifactTestBuilder::anArtifact(self::CURRENT_ARTIFACT_ID)->build();
        $user                  = UserTestBuilder::aUser()->build();
        $removed_link          = ReverseLinkStub::withNoType(self::SOURCE_ARTIFACT_ID);
        $removed_reverse_links = new CollectionOfReverseLinks([$removed_link]);

        $artifact_unlinker = new ArtifactUpdateHandler(
            $this->changeset_creator,
            $this->form_element_factory,
            $this->artifact_retriever,
        );
        return $artifact_unlinker->removeReverseLinks($artifact, $user, $removed_reverse_links);
    }

    public function testItReturnsAFaultWhenTheArtifactCannotBeRetrieved(): void
    {
        $result = $this->unlinkReverseArtifact();

        self::assertSame(0, $this->changeset_creator->getCallsCount());
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(ArtifactDoesNotExistFault::class, $result->error);
    }

    public function testItReturnsAFaultWhenTheSourceArtifactDoesNotHaveALinkField(): void
    {
        $this->form_element_factory = RetrieveUsedArtifactLinkFieldsStub::withNoField();

        $result = $this->unlinkReverseArtifact();

        self::assertSame(0, $this->changeset_creator->getCallsCount());
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(ArtifactDoesNotExistFault::class, $result->error);
    }

    public function testItUnlinkTheSourceArtifactWithTheCurrentArtifact(): void
    {
        $source_artifact          = ArtifactTestBuilder::anArtifact(self::SOURCE_ARTIFACT_ID)->build();
        $this->artifact_retriever = RetrieveViewableArtifactStub::withSuccessiveArtifacts($source_artifact);

        $result = $this->unlinkReverseArtifact();

        self::assertSame(1, $this->changeset_creator->getCallsCount());
        self::assertTrue(Result::isOk($result));
        self::assertNull($result->value);
    }

    private function updateTypeAndAddReverseLinks(
        CollectionOfReverseLinks $added_links,
        CollectionOfReverseLinks $updated_type,
    ): Ok|Err {
        $artifact = ArtifactTestBuilder::anArtifact(self::CURRENT_ARTIFACT_ID)->build();
        $user     = UserTestBuilder::aUser()->build();


        $artifact_updater = new ArtifactUpdateHandler(
            $this->changeset_creator,
            $this->form_element_factory,
            $this->artifact_retriever,
        );
        return $artifact_updater->updateTypeAndAddReverseLinks($artifact, $user, $added_links, $updated_type);
    }

    public function testItReturnsAFaultWhenTheSourceArtifactCannotBeRetrieved(): void
    {
        $added_link          = ReverseLinkStub::withNoType(self::SOURCE_ARTIFACT_ID);
        $added_reverse_links = new CollectionOfReverseLinks([$added_link]);

        $result = $this->updateTypeAndAddReverseLinks($added_reverse_links, new CollectionOfReverseLinks([]));

        self::assertSame(0, $this->changeset_creator->getCallsCount());
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(ArtifactDoesNotExistFault::class, $result->error);
    }

    public function testItLinksTheSourceArtifactWithTheCurrentArtifact(): void
    {
        $source_artifact          = ArtifactTestBuilder::anArtifact(self::SOURCE_ARTIFACT_ID)->build();
        $this->artifact_retriever = RetrieveViewableArtifactStub::withSuccessiveArtifacts($source_artifact);

        $added_link          = ReverseLinkStub::withNoType(self::SOURCE_ARTIFACT_ID);
        $added_reverse_links = new CollectionOfReverseLinks([$added_link]);

        $result = $this->updateTypeAndAddReverseLinks($added_reverse_links, new CollectionOfReverseLinks([]));

        self::assertSame(1, $this->changeset_creator->getCallsCount());
        self::assertTrue(Result::isOk($result));
        self::assertNull($result->value);
    }

    public function testItUpdatesTheReverseLinkType(): void
    {
        $source_artifact          = ArtifactTestBuilder::anArtifact(self::SOURCE_ARTIFACT_ID)->build();
        $this->artifact_retriever = RetrieveViewableArtifactStub::withSuccessiveArtifacts($source_artifact);

        $updated_link               = ReverseLinkStub::withType(self::SOURCE_ARTIFACT_ID, self::ARTIFACT_TYPE);
        $updated_reverse_links_type = new CollectionOfReverseLinks([$updated_link]);

        $result = $this->updateTypeAndAddReverseLinks(new CollectionOfReverseLinks([]), $updated_reverse_links_type);
        self::assertSame(1, $this->changeset_creator->getCallsCount());
        self::assertTrue(Result::isOk($result));
        self::assertNull($result->value);
    }

    public function testItContinuesTheUpdatingOfReverseLinkWhenASourceArtifactIsNotModified(): void
    {
        $this->changeset_creator = CreateNewChangesetStub::withCallback(function (NewChangeset $new_changeset) {
            if ($new_changeset->getArtifact()->getId() === self::SOURCE_ARTIFACT_ID_2) {
                throw new \Tracker_NoChangeException(
                    self::SOURCE_ARTIFACT_ID_2,
                    sprintf('art #%d', self::SOURCE_ARTIFACT_ID_2)
                );
            }
            return ChangesetTestBuilder::aChangeset('658')->build();
        });

        $source_artifact_1           = ArtifactTestBuilder::anArtifact(self::SOURCE_ARTIFACT_ID)->build();
        $source_artifact_2_no_change = ArtifactTestBuilder::anArtifact(self::SOURCE_ARTIFACT_ID_2)->build();
        $this->artifact_retriever    = RetrieveViewableArtifactStub::withSuccessiveArtifacts($source_artifact_1, $source_artifact_2_no_change);

        $updated_link_no_change     = ReverseLinkStub::withNoType(self::SOURCE_ARTIFACT_ID_2);
        $updated_link               = ReverseLinkStub::withType(self::SOURCE_ARTIFACT_ID, self::ARTIFACT_TYPE);
        $updated_reverse_links_type = new CollectionOfReverseLinks([$updated_link, $updated_link_no_change]);

        $result = $this->updateTypeAndAddReverseLinks(new CollectionOfReverseLinks([]), $updated_reverse_links_type);
        self::assertSame(2, $this->changeset_creator->getCallsCount());
        self::assertTrue(Result::isOk($result));
        self::assertNull($result->value);
    }
}
