<?php
/**
 * Copyright (c) Enalean 2022 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\REST\Artifact\ChangesetValue\ArtifactLink;

use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\LinkWithDirectionRepresentationBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class AllLinkPayloadParserTest extends TestCase
{
    public function testItBuildsACollectionOfReverseLinks(): void
    {
        $all_links = [
            LinkWithDirectionRepresentationBuilder::aReverseLink(1)->build(),
            LinkWithDirectionRepresentationBuilder::aForwardLink(2)->build(),
            LinkWithDirectionRepresentationBuilder::aReverseLink(3)->withType('_is_child')->build(),
        ];

        $collection = AllLinkPayloadParser::buildReverseLinks($all_links);

        $this->assertCount(2, $collection->links);
        $ids = array_map(static fn($link) => $link->getSourceArtifactId(), $collection->links);
        self::assertContains(1, $ids);
        self::assertNotContains(2, $ids);
        self::assertContains(3, $ids);
    }
}
