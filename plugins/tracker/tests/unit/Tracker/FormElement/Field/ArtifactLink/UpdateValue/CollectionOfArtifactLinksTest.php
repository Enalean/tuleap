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

final class CollectionOfArtifactLinksTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var ArtifactLink[]
     */
    private array $artifact_links;

    protected function setUp(): void
    {
        $this->artifact_links = [
            ArtifactLink::fromPayload(['id' => 101, 'type' => '_is_child']),
            ArtifactLink::fromPayload(['id' => 102, 'type' => '_depends_on']),
            ArtifactLink::fromPayload(['id' => 103, 'type' => '']),
            ArtifactLink::fromPayload(['id' => 104]),
        ];
    }

    public function testItReturnsItsArtifactLinks(): void
    {
        $collection = new CollectionOfArtifactLinks($this->artifact_links);

        self::assertEquals($this->artifact_links, $collection->getArtifactLinks());
    }

    public function testItReturnsTheIdsOfItsArtifactLinks(): void
    {
        $collection = new CollectionOfArtifactLinks($this->artifact_links);

        self::assertEquals([101, 102, 103, 104], $collection->getArtifactLinksIds());
    }

    public function testItReturnsTheTypesByArtifactLinks(): void
    {
        $collection = new CollectionOfArtifactLinks($this->artifact_links);

        self::assertEquals([
            101 => '_is_child',
            102 => '_depends_on',
            103 => '',
        ], $collection->getArtifactTypesByIds());
    }
}
