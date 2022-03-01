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

use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;

final class ArtifactLinksByChangesetCacheTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItCachesLinksByChangeset(): void
    {
        $cache       = new ArtifactLinksByChangesetCache();
        $changeset_1 = ChangesetTestBuilder::aChangeset(101)->build();
        $changeset_2 = ChangesetTestBuilder::aChangeset(102)->build();

        $a_link_info = $this->createMock(\Tracker_ArtifactLinkInfo::class);

        $links_of_changeset_1 = new CollectionOfArtifactLinksInfo([$a_link_info]);
        $cache->cacheLinksInfoForChangeset(
            $changeset_1,
            $links_of_changeset_1
        );

        self::assertTrue($cache->hasCachedLinksInfoForChangeset($changeset_1));
        self::assertFalse($cache->hasCachedLinksInfoForChangeset($changeset_2));

        self::assertEquals($links_of_changeset_1, $cache->getCachedLinksInfoForChangeset($changeset_1));
    }
}
