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

final class CollectionOfArtifactLinksInfoTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var ArtifactLink[]
     */
    private array $links_info;

    protected function setUp(): void
    {
        $this->links_info = [
            new \Tracker_ArtifactLinkInfo(101, 'story', 101, 123, 1, null),
            new \Tracker_ArtifactLinkInfo(102, 'story', 101, 123, 2, null),
            new \Tracker_ArtifactLinkInfo(103, 'story', 101, 123, 3, null),
        ];
    }

    public function testItReturnsItsLinksInfo(): void
    {
        $collection = new CollectionOfArtifactLinksInfo($this->links_info);

        self::assertEquals($this->links_info, $collection->getLinksInfo());
    }

    public function testItReturnsTheIdsOfItsLinksInfo(): void
    {
        $collection = new CollectionOfArtifactLinksInfo($this->links_info);

        self::assertEquals([101, 102, 103], $collection->getLinksInfoArtifactsIds());
    }
}
