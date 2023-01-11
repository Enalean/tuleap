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

use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue_ArtifactLink;
use Tracker_ArtifactLinkInfo;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\CollectionOfForwardLinks;
use Tuleap\Tracker\Test\Builders\ArtifactLinkFieldBuilder;

final class ArtifactLinkFilterTest extends TestCase
{
    private ArtifactLinkFilter $artifact_link_filter;

    protected function setUp(): void
    {
        $this->artifact_link_filter = new ArtifactLinkFilter();
    }

    public function testItReturnsLinkedArtifactIdWhenTheCurrentArtifactDoesNotHaveChangeset(): void
    {
        $artifact = self::createMock(Artifact::class);
        $artifact->method("getLastChangeset")->willReturn(null);
        $artifact_link_field = ArtifactLinkFieldBuilder::anArtifactLinkField(15)->build();
        $linked_artifact_id  = "36";

        $links            = [ForwardLinkProxy::buildFromData((int) $linked_artifact_id, "")];
        $linked_artifacts = new CollectionOfForwardLinks($links);

        $result = $this->artifact_link_filter->filterArtifactIdsIAmAlreadyLinkedTo($artifact, $artifact_link_field, $linked_artifacts);
        self::assertSame($linked_artifact_id, $result->getArtifactLinksAsStringList());
    }

    public function testItReturnsLinkedArtifactIdWhenTheCurrentArtifactDoesNotHaveChangesetValue(): void
    {
        $changeset = self::createMock(Tracker_Artifact_Changeset::class);
        $changeset->method("getValue")->willReturn(null);

        $artifact = self::createMock(Artifact::class);
        $artifact->method("getLastChangeset")->willReturn($changeset);

        $artifact_link_field = ArtifactLinkFieldBuilder::anArtifactLinkField(15)->build();

        $linked_artifact_id = "36";
        $links              = [ForwardLinkProxy::buildFromData((int) $linked_artifact_id, "")];
        $linked_artifacts   = new CollectionOfForwardLinks($links);

        $result = $this->artifact_link_filter->filterArtifactIdsIAmAlreadyLinkedTo($artifact, $artifact_link_field, $linked_artifacts);
        self::assertSame($linked_artifact_id, $result->getArtifactLinksAsStringList());
    }

    public function testItReturnsTheIdsOfTheLinkedArtifactsIfTheLinkedArtifactWasNotAlreadyLinkedWithTheCurrentOne(): void
    {
        $artifact_link_field = ArtifactLinkFieldBuilder::anArtifactLinkField(15)->build();
        $artifact            = self::createMock(Artifact::class);
        $artifact->method("getId")->willReturn(15);

        $artifact_already_linked_1 = self::createMock(Artifact::class);
        $artifact_already_linked_1->method("getId")->willReturn(20);

        $artifact_already_linked_2 = self::createMock(Artifact::class);
        $artifact_already_linked_2->method("getId")->willReturn(45);

        $changeset = self::createMock(Tracker_Artifact_Changeset::class);

        $artifact_link_changeset_value = new Tracker_Artifact_ChangesetValue_ArtifactLink(
            68,
            $changeset,
            $artifact_link_field,
            true,
            [$artifact_already_linked_1->getId() => $this->buildTrackerLinkInfo($artifact), $artifact_already_linked_2->getId() => $this->buildTrackerLinkInfo($artifact)],
            []
        );
        $changeset->method("getValue")->willReturn($artifact_link_changeset_value);

        $artifact->method("getLastChangeset")->willReturn($changeset);

        $linked_artifact_id = "36";

        $links            = [ForwardLinkProxy::buildFromData((int) $linked_artifact_id, "")];
        $linked_artifacts = new CollectionOfForwardLinks($links);

        $result = $this->artifact_link_filter->filterArtifactIdsIAmAlreadyLinkedTo($artifact, $artifact_link_field, $linked_artifacts);
        self::assertSame($linked_artifact_id, $result->getArtifactLinksAsStringList());
    }

    private function buildTrackerLinkInfo(Artifact $artifact): Tracker_ArtifactLinkInfo
    {
        return new class ($artifact) extends Tracker_ArtifactLinkInfo {
            public function __construct(private Artifact $artifact)
            {
                parent::__construct(
                    $artifact->getId(),
                    'keyword',
                    102,
                    14,
                    10,
                    ''
                );
            }
        };
    }
}
