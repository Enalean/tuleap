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

namespace Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\ForwardLinkStub;
use Tuleap\Tracker\Test\Stub\RetrieveArtifactStub;

final class ArtifactForwardLinksRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FIRST_ARTIFACT_ID  = 103;
    private const SECOND_ARTIFACT_ID = 104;
    /**
     * @var ChangesetValueArtifactLinkDao & MockObject
     */
    private $dao;
    private RetrieveArtifactStub $artifact_retriever;
    private ArtifactLinksByChangesetCache $cache;

    protected function setUp(): void
    {
        $this->dao   = $this->createMock(ChangesetValueArtifactLinkDao::class);
        $this->cache = new ArtifactLinksByChangesetCache();

        $tracker_456              = $this->getMockedTracker(456);
        $artifact_103             = $this->getMockedArtifact(self::FIRST_ARTIFACT_ID, 789789, $tracker_456, true);
        $artifact_104             = $this->getMockedArtifact(self::SECOND_ARTIFACT_ID, 789790, $tracker_456, true);
        $this->artifact_retriever = RetrieveArtifactStub::withArtifacts($artifact_103, $artifact_104);
    }

    private function retrieve(?Artifact $artifact): CollectionOfForwardLinks
    {
        $link_field = new \Tracker_FormElement_Field_ArtifactLink(
            453,
            69,
            1,
            'irrelevant',
            'Irrelevant',
            'Irrelevant',
            true,
            'P',
            false,
            '',
            1
        );

        $user      = UserTestBuilder::buildWithDefaults();
        $retriever = new ArtifactForwardLinksRetriever(
            $this->cache,
            $this->dao,
            $this->artifact_retriever
        );
        return $retriever->retrieve($user, $link_field, $artifact);
    }

    public function testItReturnsAnEmptyCollectionWhenThereIsNoArtifact(): void
    {
        $forward_links = $this->retrieve(null);
        self::assertEmpty($forward_links->getArtifactLinks());
    }

    public function testItReturnsAnEmptyCollectionWhenArtifactHasNoLastChangeset(): void
    {
        $artifact = $this->createStub(Artifact::class);
        $artifact->method('getLastChangeset')->willReturn(null);

        $forward_links = $this->retrieve($artifact);

        self::assertEmpty($forward_links->getArtifactLinks());
    }

    public function testItReturnsTheForwardLinksAsACollectionOfLinksInfo(): void
    {
        $last_changeset = ChangesetTestBuilder::aChangeset('1807')->build();

        $artifact = $this->createStub(Artifact::class);
        $artifact->method('getLastChangeset')->willReturn($last_changeset);

        $this->dao->expects(self::once())->method('searchChangesetValues')->willReturn($this->getDbData());

        $forward_links = $this->retrieve($artifact);

        $links = $forward_links->getArtifactLinks();
        self::assertCount(2, $links);
        self::assertSame(self::FIRST_ARTIFACT_ID, $links[0]->getTargetArtifactId());
        self::assertSame('_is_child', $links[0]->getType());
        self::assertSame(self::SECOND_ARTIFACT_ID, $links[1]->getTargetArtifactId());
        self::assertSame('_is_child', $links[1]->getType());

        self::assertTrue(
            $this->cache->hasCachedLinksInfoForChangeset($last_changeset)
        );
    }

    public function testItDoesNotReturnLinksForArtifactsUserCannotSee(): void
    {
        $last_changeset = ChangesetTestBuilder::aChangeset('1807')->build();

        $artifact = $this->createStub(Artifact::class);
        $artifact->method('getLastChangeset')->willReturn($last_changeset);

        $this->dao->expects(self::once())->method('searchChangesetValues')->willReturn($this->getDbData());

        $tracker_456              = $this->getMockedTracker(456);
        $artifact_103             = $this->getMockedArtifact(self::FIRST_ARTIFACT_ID, 789789, $tracker_456, false);
        $artifact_104             = $this->getMockedArtifact(self::SECOND_ARTIFACT_ID, 789790, $tracker_456, false);
        $this->artifact_retriever = RetrieveArtifactStub::withArtifacts($artifact_103, $artifact_104);

        $forward_links = $this->retrieve($artifact);

        self::assertEmpty($forward_links->getArtifactLinks());
    }

    public function testItReturnsDirectlyCachedLinksInfoWhenThereAreAvailable(): void
    {
        $last_changeset = ChangesetTestBuilder::aChangeset('1807')->build();

        $this->cache->cacheLinksInfoForChangeset(
            $last_changeset,
            new CollectionOfForwardLinks([
                ForwardLinkStub::withType(self::FIRST_ARTIFACT_ID, '_is_child'),
                ForwardLinkStub::withType(self::SECOND_ARTIFACT_ID, '_is_child'),
            ])
        );

        $artifact = $this->createMock(Artifact::class);
        $artifact->method('getLastChangeset')->willReturn($last_changeset);

        $this->dao->expects(self::never())->method('searchChangesetValues');

        $forward_links = $this->retrieve($artifact);
        $links         = $forward_links->getArtifactLinks();
        self::assertCount(2, $links);
        self::assertSame(self::FIRST_ARTIFACT_ID, $links[0]->getTargetArtifactId());
        self::assertSame('_is_child', $links[0]->getType());
        self::assertSame(self::SECOND_ARTIFACT_ID, $links[1]->getTargetArtifactId());
        self::assertSame('_is_child', $links[1]->getType());
    }

    private function getDbData(): array
    {
        return [
            [
                'artifact_id'       => self::FIRST_ARTIFACT_ID,
                'keyword'           => 'bananas',
                'group_id'          => 123,
                'tracker_id'        => 456,
                'last_changeset_id' => 789789,
                'nature'            => '_is_child',
            ], [
                'artifact_id'       => self::SECOND_ARTIFACT_ID,
                'keyword'           => 'bogoya',
                'group_id'          => 123,
                'tracker_id'        => 456,
                'last_changeset_id' => 789790,
                'nature'            => '_is_child',
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
     * @return \PHPUnit\Framework\MockObject\Stub & Artifact
     */
    private function getMockedArtifact(int $artifact_id, int $last_changeset_id, \Tracker $tracker, bool $can_user_see)
    {
        $artifact = $this->createStub(Artifact::class);

        $artifact->method('getId')->willReturn($artifact_id);
        $artifact->method('getTracker')->willReturn($tracker);
        $artifact->method('getLastChangeset')->willReturn(ChangesetTestBuilder::aChangeset($last_changeset_id)->build());
        $artifact->method('userCanView')->willReturn($can_user_see);

        return $artifact;
    }
}
