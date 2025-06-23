<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink;

use Tuleap\Tracker\Artifact\Artifact;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class LinksRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ArtifactLinkFieldValueDao
     */
    private $dao;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Tracker_ArtifactFactory
     */
    private $tracker_artifact_factory;

    private LinksRetriever $retriever;

    protected function setUp(): void
    {
        $this->dao                      = $this->createMock(ArtifactLinkFieldValueDao::class);
        $this->tracker_artifact_factory = $this->createMock(\Tracker_ArtifactFactory::class);
        $this->retriever                = new LinksRetriever($this->dao, $this->tracker_artifact_factory);
    }

    public function testItReturnsTheListOfArtifactsOfTheGivenTrackerReverseLinkingAnArtifactWhenUserCanRead(): void
    {
        $artifact_id       = 73;
        $target_tracker_id = 32;

        $user           = $this->createMock(\PFUser::class);
        $artifact       = $this->createMock(Artifact::class);
        $art_link_field = $this->createMock(\Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField::class);
        $last_changeset = $this->createMock(\Tracker_Artifact_Changeset::class);
        $target_tracker = $this->createMock(\Tuleap\Tracker\Tracker::class);

        $target_tracker->expects($this->once())->method('getId')->willReturn($target_tracker_id);

        $artifact->expects($this->once())->method('getId')->willReturn($artifact_id);
        $artifact->expects($this->once())->method('getAnArtifactLinkField')->with($user)->willReturn($art_link_field);
        $artifact->expects($this->once())->method('getLastChangeset')->willReturn($last_changeset);

        $this->dao->expects($this->once())->method('searchReverseLinksByIdAndSourceTrackerId')
            ->with($artifact_id, $target_tracker_id)
            ->willReturn([
                ['artifact_id' => 83],
                ['artifact_id' => 93],
                ['artifact_id' => 103],
            ]);

        $art_83  = $this->createMock(Artifact::class);
        $art_93  = $this->createMock(Artifact::class);
        $art_103 = $this->createMock(Artifact::class);

        $art_83->expects($this->once())->method('userCanView')->with($user)->willReturn(true);
        $art_93->expects($this->once())->method('userCanView')->with($user)->willReturn(false);
        $art_103->expects($this->once())->method('userCanView')->with($user)->willReturn(true);

        $this->tracker_artifact_factory->expects($this->exactly(3))
            ->method('getArtifactById')
            ->willReturnCallback(
                fn (int $art_id): Artifact => match ($art_id) {
                    83 => $art_83,
                    93 => $art_93,
                    103 => $art_103,
                }
            );

        $reverse_links = $this->retriever->retrieveReverseLinksFromTracker($artifact, $user, $target_tracker);

        self::assertContains($art_83, $reverse_links);
        self::assertNotContains($art_93, $reverse_links);
        self::assertContains($art_103, $reverse_links);
    }
}
