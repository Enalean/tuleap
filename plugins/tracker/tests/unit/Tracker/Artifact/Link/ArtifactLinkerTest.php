<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

use Tuleap\GlobalResponseMock;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\CollectionOfForwardLinks;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ArtifactLinkFieldBuilder;
use Tuleap\Tracker\Test\Stub\CreateNewChangesetStub;
use Tuleap\Tracker\Test\Stub\ForwardLinkStub;
use Tuleap\Tracker\Test\Stub\RetrieveForwardLinksStub;
use Tuleap\Tracker\Test\Stub\RetrieveUsedArtifactLinkFieldsStub;

final class ArtifactLinkerTest extends TestCase
{
    use GlobalResponseMock;

    private const CURRENT_ARTIFACT_ID = 10;

    private RetrieveUsedArtifactLinkFieldsStub $form_element_factory;
    private CreateNewChangesetStub $changeset_creator;
    private RetrieveForwardLinksStub $links_retriever;

    private \PFUser $user;

    protected function setUp(): void
    {
        $this->user = UserTestBuilder::aUser()->build();

        $artifact_link_field        = ArtifactLinkFieldBuilder::anArtifactLinkField(15)->build();
        $this->form_element_factory = RetrieveUsedArtifactLinkFieldsStub::withAField($artifact_link_field);
        $this->changeset_creator    = CreateNewChangesetStub::withNullReturnChangeset();
        $this->links_retriever      = RetrieveForwardLinksStub::withLinks(
            new CollectionOfForwardLinks([ForwardLinkStub::withNoType(10)])
        );
    }

    private function linkArtifact(): bool
    {
        $artifact         = ArtifactTestBuilder::anArtifact(self::CURRENT_ARTIFACT_ID)->build();
        $linked_artifacts = new CollectionOfForwardLinks([ForwardLinkStub::withNoType(18)]);

        $artifact_linker = new ArtifactLinker(
            $this->form_element_factory,
            $this->changeset_creator,
            $this->links_retriever,
        );
        return $artifact_linker->linkArtifact($artifact, $linked_artifacts, $this->user);
    }

    public function testItReturnsFalseAndDisplayAnErrorWhenNoArtifactLinkFieldsAreUsed(): void
    {
        $this->form_element_factory = RetrieveUsedArtifactLinkFieldsStub::withNoField();

        $GLOBALS['Response']->expects(self::once())->method('addFeedback')->with('error');
        self::assertFalse($this->linkArtifact());
    }

    public function testItReturnsTrueAndCreateChangeset(): void
    {
        $this->changeset_creator = CreateNewChangesetStub::withReturnChangeset(
            ChangesetTestBuilder::aChangeset("45")->build()
        );

        $GLOBALS['Response']->expects(self::never())->method('addFeedback')->with('error');
        self::assertTrue($this->linkArtifact());
        self::assertSame(1, $this->changeset_creator->getCallsCount());
    }

    public function testItReturnsFalseAndDisplayAnInfoWhenThereIsNoChange(): void
    {
        $this->changeset_creator = CreateNewChangesetStub::withException(
            new \Tracker_NoChangeException(self::CURRENT_ARTIFACT_ID, '#art 125')
        );

        $GLOBALS['Response']->expects(self::once())->method('addFeedback')->with('info');
        self::assertFalse($this->linkArtifact());
        self::assertSame(1, $this->changeset_creator->getCallsCount());
    }

    public function testItReturnsFalseAndDisplayAnInfoWhenThereIsAnErrorDuringTheChangesetCreation(): void
    {
        $this->changeset_creator = CreateNewChangesetStub::withException(new \Tracker_Exception());

        $GLOBALS['Response']->expects(self::once())->method('addFeedback')->with('error');
        self::assertFalse($this->linkArtifact());
        self::assertSame(1, $this->changeset_creator->getCallsCount());
    }
}
