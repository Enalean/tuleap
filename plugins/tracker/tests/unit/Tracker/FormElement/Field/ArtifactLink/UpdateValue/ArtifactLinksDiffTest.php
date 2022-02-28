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

final class ArtifactLinksDiffTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItBuildsADiffOfAddedAndRemovedLinks(): void
    {
        $link_1 = $this->createMock(\Tracker_ArtifactLinkInfo::class);
        $link_2 = $this->createMock(\Tracker_ArtifactLinkInfo::class);
        $link_3 = $this->createMock(\Tracker_ArtifactLinkInfo::class);

        $link_1->method('getArtifactId')->willReturn(101);
        $link_2->method('getArtifactId')->willReturn(102);
        $link_3->method('getArtifactId')->willReturn(103);

        $submitted_links = new CollectionOfArtifactLinks([
            ArtifactLink::fromPayload(['id' => 101, 'type' => '_is_child']),
            ArtifactLink::fromPayload(['id' => 102, 'type' => '_is_child']),
            ArtifactLink::fromPayload(['id' => 104, 'type' => '_is_child']),
        ]);

        $current_forward_links = new CollectionOfArtifactLinksInfo([
            $link_1,
            $link_2,
            $link_3,
        ]);

        $diff = ArtifactLinksDiff::build($submitted_links, $current_forward_links);

        self::assertEquals([104], $diff->getNewValues());
        self::assertEquals([103], $diff->getRemovedValues());
    }

    public function testItBuildsAnEmptyDiffWhenThereIsNoChange(): void
    {
        $link_1 = $this->createMock(\Tracker_ArtifactLinkInfo::class);
        $link_1->method('getArtifactId')->willReturn(101);

        $submitted_links = new CollectionOfArtifactLinks([
            ArtifactLink::fromPayload(['id' => 101, 'type' => '_is_child']),
        ]);

        $current_forward_links = new CollectionOfArtifactLinksInfo([$link_1]);

        $diff = ArtifactLinksDiff::build($submitted_links, $current_forward_links);

        self::assertEquals([], $diff->getNewValues());
        self::assertEquals([], $diff->getRemovedValues());
    }
}
