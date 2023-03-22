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

use Tuleap\Tracker\Test\Stub\ForwardLinkStub;

final class CollectionOfForwardLinksTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var ForwardLinkStub[]
     */
    private array $artifact_links;

    protected function setUp(): void
    {
        $this->artifact_links = [
            ForwardLinkStub::withType(101, '_is_child'),
            ForwardLinkStub::withType(102, '_depends_on'),
            ForwardLinkStub::withNoType(103),
        ];
    }

    public function testItReturnsItsArtifactLinks(): void
    {
        $collection = new CollectionOfForwardLinks($this->artifact_links);

        self::assertEquals($this->artifact_links, $collection->getArtifactLinks());
    }

    public function testItReturnsTheIdsOfItsArtifactLinks(): void
    {
        $collection = new CollectionOfForwardLinks($this->artifact_links);

        self::assertEquals([101, 102, 103], $collection->getTargetArtifactIds());
    }

    public function testItReturnsTheTypesByArtifactLinks(): void
    {
        $collection = new CollectionOfForwardLinks($this->artifact_links);

        self::assertEquals([
            101 => '_is_child',
            102 => '_depends_on',
            103 => \Tracker_FormElement_Field_ArtifactLink::NO_TYPE,
        ], $collection->getArtifactTypesByIds());
    }

    public function testItReturnsTrueWhenEmpty(): void
    {
        $collection = new CollectionOfForwardLinks([]);
        self::assertEmpty($collection->getArtifactLinks());
    }

    public function testItReturnsFalseWhenItContainsLinks(): void
    {
        $collection = new CollectionOfForwardLinks($this->artifact_links);
        self::assertNotEmpty($collection->getArtifactLinks());
    }

    public function testItReturnsADiffOfAddedAndRemovedLinks(): void
    {
        $current_forward_links = new CollectionOfForwardLinks([
            ForwardLinkStub::withType(101, '_is_child'),
            ForwardLinkStub::withNoType(102),
            ForwardLinkStub::withNoType(103),
        ]);

        $submitted_links = new CollectionOfForwardLinks([
            ForwardLinkStub::withType(101, '_is_child'),
            ForwardLinkStub::withType(102, '_is_child'),
            ForwardLinkStub::withType(104, '_is_child'),
        ]);

        $added_collection   = $current_forward_links->differenceById($submitted_links);
        $removed_collection = $submitted_links->differenceById($current_forward_links);

        $new_values = $added_collection->getTargetArtifactIds();
        self::assertCount(1, $new_values);
        self::assertContains(104, $new_values);
        $removed_values = $removed_collection->getTargetArtifactIds();
        self::assertCount(1, $removed_values);
        self::assertContains(103, $removed_values);
    }

    public function testItReturnsAnEmptyDiffWhenThereIsNoChange(): void
    {
        $submitted_links       = new CollectionOfForwardLinks([
            ForwardLinkStub::withType(101, '_is_child'),
            ForwardLinkStub::withNoType(102),
        ]);
        $current_forward_links = new CollectionOfForwardLinks([
            ForwardLinkStub::withType(101, '_is_child'),
            ForwardLinkStub::withNoType(102),
        ]);

        $added_collection   = $current_forward_links->differenceById($submitted_links);
        $removed_collection = $submitted_links->differenceById($current_forward_links);

        self::assertEmpty($added_collection->getTargetArtifactIds());
        self::assertEmpty($removed_collection->getTargetArtifactIds());
    }
}
