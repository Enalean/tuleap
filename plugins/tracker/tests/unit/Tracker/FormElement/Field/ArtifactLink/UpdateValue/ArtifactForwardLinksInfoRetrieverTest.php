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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink\UpdateValue;

use PHPUnit\Framework\MockObject\MockObject;
use Tracker_ArtifactFactory;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ChangesetValueArtifactLinkDao;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class ArtifactForwardLinksInfoRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var ChangesetValueArtifactLinkDao & MockObject
     */
    private $dao;
    /**
     * @var MockObject & Tracker_ArtifactFactory
     */
    private $tracker_artifact_factory;
    private ArtifactLinksByChangesetCache $cache;
    private ArtifactForwardLinksInfoRetriever $retriever;
    private \PFUser $submitter;

    protected function setUp(): void
    {
        $this->submitter                = UserTestBuilder::anActiveUser()->build();
        $this->dao                      = $this->createMock(ChangesetValueArtifactLinkDao::class);
        $this->tracker_artifact_factory = $this->createMock(Tracker_ArtifactFactory::class);
        $this->cache                    = new ArtifactLinksByChangesetCache();
        $this->retriever                = new ArtifactForwardLinksInfoRetriever(
            $this->cache,
            $this->dao,
            $this->tracker_artifact_factory
        );
    }

    public function testItReturnsAnEmptyCollectionWhenThereIsNoArtifact(): void
    {
        $link_field    = $this->getMockedLinkField();
        $forward_links = $this->retriever->retrieve($this->submitter, $link_field, null);

        self::assertEmpty($forward_links->getLinksInfo());
    }

    public function testItReturnsAnEmptyCollectionWhenArtifactHasNoLastChangeset(): void
    {
        $link_field = $this->getMockedLinkField();
        $artifact   = $this->createMock(Artifact::class);
        $artifact->method('getAnArtifactLinkField')->willReturn($link_field);
        $artifact->method('getLastChangeset')->willReturn(null);

        $forward_links = $this->retriever->retrieve($this->submitter, $link_field, $artifact);

        self::assertEmpty($forward_links->getLinksInfo());
    }

    public function testItReturnsTheForwardLinksAsACollectionOfLinksInfo(): void
    {
        $link_field     = $this->getMockedLinkField();
        $last_changeset = $this->createMock(\Tracker_Artifact_Changeset::class);
        $last_changeset->method('getId')->willReturn(123456789);

        $artifact = $this->createMock(Artifact::class);
        $artifact->method('getAnArtifactLinkField')->willReturn($link_field);
        $artifact->method('getLastChangeset')->willReturn($last_changeset);

        $tracker_456  = $this->getMockedTracker(456);
        $artifact_103 = $this->getMockedArtifactUserCanView(103, 789789, $tracker_456);
        $artifact_104 = $this->getMockedArtifactUserCanView(104, 789790, $tracker_456);

        $this->dao->expects(self::once())->method('searchChangesetValues')->willReturn($this->getDbData());

        $this->tracker_artifact_factory->expects(self::exactly(2))->method('getArtifactById')
            ->withConsecutive([103], [104])
            ->willReturnOnConsecutiveCalls($artifact_103, $artifact_104);

        $forward_links = $this->retriever->retrieve($this->submitter, $link_field, $artifact);

        self::assertEquals(
            [
                \Tracker_ArtifactLinkInfo::buildFromArtifact($artifact_103, '_is_child'),
                \Tracker_ArtifactLinkInfo::buildFromArtifact($artifact_104, '_is_child'),
            ],
            $forward_links->getLinksInfo()
        );

        self::assertTrue(
            $this->cache->hasCachedLinksInfoForChangeset($last_changeset)
        );
    }

    public function testItReturnsDirectlyCachedLinksInfoWhenThereAreAvailable(): void
    {
        $link_field     = $this->getMockedLinkField();
        $last_changeset = $this->createMock(\Tracker_Artifact_Changeset::class);
        $last_changeset->method('getId')->willReturn(123456789);

        $tracker_456  = $this->getMockedTracker(456);
        $artifact_103 = $this->getMockedArtifactUserCanView(103, 789789, $tracker_456);
        $artifact_104 = $this->getMockedArtifactUserCanView(104, 789790, $tracker_456);

        $links_info = [
            \Tracker_ArtifactLinkInfo::buildFromArtifact($artifact_103, '_is_child'),
            \Tracker_ArtifactLinkInfo::buildFromArtifact($artifact_104, '_is_child'),
        ];

        $this->cache->cacheLinksInfoForChangeset(
            $last_changeset,
            new CollectionOfArtifactLinksInfo($links_info)
        );

        $artifact = $this->createMock(Artifact::class);
        $artifact->method('getAnArtifactLinkField')->willReturn($link_field);
        $artifact->method('getLastChangeset')->willReturn($last_changeset);

        $this->dao->expects(self::never())->method('searchChangesetValues');

        $forward_links = $this->retriever->retrieve($this->submitter, $link_field, $artifact);

        self::assertEquals($links_info, $forward_links->getLinksInfo());
    }

    private function getDbData(): array
    {
        return [
            [
                'artifact_id' => 103,
                'keyword' => 'bananas',
                'group_id' => 123,
                'tracker_id' => 456,
                'last_changeset_id' => 789789,
                'nature' => '_is_child',
            ], [
                'artifact_id' => 104,
                'keyword' => 'bogoya',
                'group_id' => 123,
                'tracker_id' => 456,
                'last_changeset_id' => 789790,
                'nature' => '_is_child',
            ],
        ];
    }

    private function getMockedTracker(int $tracker_id): \Tracker
    {
        return TrackerTestBuilder::aTracker()
            ->withId($tracker_id)
            ->withShortName("fruits")
            ->withProject(ProjectTestBuilder::aProject()->withId(123)->build())
            ->build();
    }

    /**
     * @return MockObject & Artifact
     */
    private function getMockedArtifactUserCanView(int $artifact_id, int $last_changeset_id, \Tracker $tracker)
    {
        $artifact = $this->createMock(Artifact::class);

        $artifact->method('getId')->willReturn($artifact_id);
        $artifact->method('getTracker')->willReturn($tracker);
        $artifact->method('getLastChangeset')->willReturn(ChangesetTestBuilder::aChangeset($last_changeset_id)->build());
        $artifact->method('userCanView')->willReturn(true);

        return $artifact;
    }

    /**
     * @return MockObject & \Tracker_FormElement_Field_ArtifactLink
     */
    private function getMockedLinkField()
    {
        $link_field = $this->createMock(\Tracker_FormElement_Field_ArtifactLink::class);
        $link_field->method('getId')->willReturn(6666666);
        return $link_field;
    }
}
