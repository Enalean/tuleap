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

use RuntimeException;
use Tracker;
use Tracker_Exception;
use Tracker_NoChangeException;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\CollectionOfForwardLinks;
use Tuleap\Tracker\Test\Builders\ArtifactLinkFieldBuilder;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\CreateNewChangesetStub;
use Tuleap\Tracker\Test\Stub\FilterArtifactLinkStub;
use Tuleap\Tracker\Test\Stub\RetrieveTrackerStub;
use Tuleap\Tracker\Test\Stub\RetrieveUsedArtifactLinkFieldsStub;

final class ArtifactLinkerTest extends TestCase
{
    use GlobalResponseMock;

    private RetrieveUsedArtifactLinkFieldsStub $form_element_factory;
    private RetrieveTrackerStub $tracker_factory;
    private CreateNewChangesetStub $changeset_creator;
    private FilterArtifactLinkStub $artifact_link_filter;

    private \PFUser $user;

    protected function setUp(): void
    {
        $this->user = UserTestBuilder::aUser()->build();

        $artifact_link_field        = ArtifactLinkFieldBuilder::anArtifactLinkField(15)->build();
        $this->form_element_factory = RetrieveUsedArtifactLinkFieldsStub::buildWithArtifactLinkFields([$artifact_link_field]);
        $this->tracker_factory      = RetrieveTrackerStub::withDefaultTracker();
        $this->changeset_creator    = CreateNewChangesetStub::withNullReturnChangeset();
        $this->artifact_link_filter = FilterArtifactLinkStub::withArtifactIdsIAmAlreadyLinkedTo("18");
    }

    private function instantiateArtifactLinker(): ArtifactLinker
    {
        return new ArtifactLinker(
            $this->form_element_factory,
            $this->tracker_factory,
            $this->changeset_creator,
            $this->artifact_link_filter
        );
    }

    public function testItThrowsAnExceptionWhenTheArtifactIsNotInATracker(): void
    {
        $artifact              = ArtifactTestBuilder::anArtifact(10)->build();
        $this->tracker_factory = RetrieveTrackerStub::withoutTracker();

        $this->form_element_factory = RetrieveUsedArtifactLinkFieldsStub::buildWithArtifactLinkFields([]);

        $artifact_linker  = $this->instantiateArtifactLinker();
        $links            = [ForwardLinkProxy::buildFromData(18, "")];
        $linked_artifacts = new CollectionOfForwardLinks($links);

        self::expectException(RuntimeException::class);
        $GLOBALS['Response']->expects(self::never())->method('addFeedback')->with('error');
        $artifact_linker->linkArtifact($artifact, $linked_artifacts, $this->user, '');
    }

    public function testItReturnsFalseAndDisplayAnErrorWhenNoArtifactLinkFieldsAreUsed(): void
    {
        $tracker  = TrackerTestBuilder::aTracker()->withId(100)->build();
        $artifact = ArtifactTestBuilder::anArtifact(10)->inTracker($tracker)->build();

        $this->form_element_factory = RetrieveUsedArtifactLinkFieldsStub::buildWithArtifactLinkFields([]);

        $GLOBALS['Response']->expects(self::once())->method('addFeedback')->with('error');

        $links            = [ForwardLinkProxy::buildFromData(18, "")];
        $linked_artifacts = new CollectionOfForwardLinks($links);
        $artifact_linker  = $this->instantiateArtifactLinker();
        self::assertFalse($artifact_linker->linkArtifact($artifact, $linked_artifacts, $this->user, ''));
    }

    public function testItReturnsTrueAndCreateChangeset(): void
    {
        $tracker = self::createMock(Tracker::class);
        $tracker->method("isProjectAllowedToUseType")->willReturn(true);
        $tracker->method("getId")->willReturn(100);

        $artifact = ArtifactTestBuilder::anArtifact(10)->inTracker($tracker)->build();

        $this->artifact_link_filter = FilterArtifactLinkStub::withArtifactIdsIAmAlreadyLinkedTo("18");
        $this->tracker_factory      = RetrieveTrackerStub::withTracker($tracker);
        $this->changeset_creator    = CreateNewChangesetStub::withReturnChangeset(ChangesetTestBuilder::aChangeset("45")->build());

        $artifact_linker = $this->instantiateArtifactLinker();

        $GLOBALS['Response']->expects(self::never())->method('addFeedback')->with('error');

        $links            = [ForwardLinkProxy::buildFromData(18, "")];
        $linked_artifacts = new CollectionOfForwardLinks($links);

        self::assertTrue($artifact_linker->linkArtifact($artifact, $linked_artifacts, $this->user, ''));
        self::assertSame(1, $this->changeset_creator->getCallsCount());
    }

    public function testItReturnsFalseAndDisplayAnInfoWhenThereIsNoChange(): void
    {
        $tracker = self::createMock(Tracker::class);
        $tracker->method("isProjectAllowedToUseType")->willReturn(true);
        $tracker->method("getId")->willReturn(100);

        $artifact = ArtifactTestBuilder::anArtifact(10)->inTracker($tracker)->build();

        $this->artifact_link_filter = FilterArtifactLinkStub::withArtifactIdsIAmAlreadyLinkedTo("18");
        $this->tracker_factory      = RetrieveTrackerStub::withTracker($tracker);
        $this->changeset_creator    = CreateNewChangesetStub::withException(new Tracker_NoChangeException($artifact->getId(), "#art 125"));

        $artifact_linker = $this->instantiateArtifactLinker();

        $GLOBALS['Response']->expects(self::once())->method('addFeedback')->with('info');

        $links            = [ForwardLinkProxy::buildFromData(18, "")];
        $linked_artifacts = new CollectionOfForwardLinks($links);

        self::assertFalse($artifact_linker->linkArtifact($artifact, $linked_artifacts, $this->user, ''));
        self::assertSame(0, $this->changeset_creator->getCallsCount());
    }

    public function testItReturnsFalseAndDisplayAnInfoWhenThereIsAnErrorDuringTheChangesetCreation(): void
    {
        $tracker = self::createMock(Tracker::class);
        $tracker->method("isProjectAllowedToUseType")->willReturn(true);
        $tracker->method("getId")->willReturn(100);

        $artifact = ArtifactTestBuilder::anArtifact(10)->inTracker($tracker)->build();

        $this->artifact_link_filter = FilterArtifactLinkStub::withArtifactIdsIAmAlreadyLinkedTo("18");
        $this->tracker_factory      = RetrieveTrackerStub::withTracker($tracker);
        $this->changeset_creator    = CreateNewChangesetStub::withException(new Tracker_Exception());

        $artifact_linker = $this->instantiateArtifactLinker();

        $GLOBALS['Response']->expects(self::once())->method('addFeedback')->with('error');
        $links            = [ForwardLinkProxy::buildFromData(18, "")];
        $linked_artifacts = new CollectionOfForwardLinks($links);

        self::assertFalse($artifact_linker->linkArtifact($artifact, $linked_artifacts, $this->user, ''));
        self::assertSame(0, $this->changeset_creator->getCallsCount());
    }
}
