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
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\ArtifactDoesNotExistFault;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\CollectionOfReverseLinks;
use Tuleap\Tracker\Test\Builders\ArtifactLinkFieldBuilder;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Stub\CreateNewChangesetStub;
use Tuleap\Tracker\Test\Stub\RetrieveUsedArtifactLinkFieldsStub;
use Tuleap\Tracker\Test\Stub\RetrieveViewableArtifactStub;
use Tuleap\Tracker\Test\Stub\ReverseLinkStub;

final class ArtifactFromRestUpdaterTest extends TestCase
{
    private const CURRENT_ARTIFACT_ID = 10;
    private const SOURCE_ARTIFACT_ID  = 18;

    private RetrieveUsedArtifactLinkFieldsStub $form_element_factory;
    private CreateNewChangesetStub $changeset_creator;
    private RetrieveViewableArtifactStub $artifact_retriever;

    protected function setUp(): void
    {
        $artifact_link_field = ArtifactLinkFieldBuilder::anArtifactLinkField(15)->build();

        $this->form_element_factory = RetrieveUsedArtifactLinkFieldsStub::buildWithArtifactLinkFields(
            [$artifact_link_field]
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
            $this->artifact_retriever
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
        $this->form_element_factory = RetrieveUsedArtifactLinkFieldsStub::buildWithArtifactLinkFields([]);

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

    private function linkReverseArtifact(): Ok|Err
    {
        $artifact              = ArtifactTestBuilder::anArtifact(self::CURRENT_ARTIFACT_ID)->build();
        $user                  = UserTestBuilder::aUser()->build();
        $removed_link          = ReverseLinkStub::withNoType(self::SOURCE_ARTIFACT_ID);
        $removed_reverse_links = new CollectionOfReverseLinks([$removed_link]);

        $artifact_unlinker = new ArtifactUpdateHandler(
            $this->changeset_creator,
            $this->form_element_factory,
            $this->artifact_retriever
        );
        return $artifact_unlinker->addReverseLink($artifact, $user, $removed_reverse_links);
    }

    public function testItReturnsAFaultWhenTheSourceArtifactCannotBeRetrieved(): void
    {
        $result = $this->linkReverseArtifact();

        self::assertSame(0, $this->changeset_creator->getCallsCount());
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(ArtifactDoesNotExistFault::class, $result->error);
    }

    public function testItLinksTheSourceArtifactWithTheCurrentArtifact(): void
    {
        $source_artifact          = ArtifactTestBuilder::anArtifact(self::SOURCE_ARTIFACT_ID)->build();
        $this->artifact_retriever = RetrieveViewableArtifactStub::withSuccessiveArtifacts($source_artifact);

        $result = $this->linkReverseArtifact();

        self::assertSame(1, $this->changeset_creator->getCallsCount());
        self::assertTrue(Result::isOk($result));
        self::assertNull($result->value);
    }
}
