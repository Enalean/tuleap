<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

namespace unit\Tracker\Artifact\ChangesetValue\ArtifactLink;

use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\CollectionOfReverseLinks;
use Tuleap\Tracker\Test\Stub\ReverseLinkStub;

final class CollectionOfReverseLinksTest extends TestCase
{
    private const ARTIFACT_ID_1 = 15;
    private const ARTIFACT_ID_2 = 20;
    private const ARTIFACT_ID_3 = 100;

    private const ARTIFACT_NEW_TYPE = "_wololo";

    public function testItReturnsTheDiffByArtifactIdOfTwoCollections(): void
    {
        $links       = new CollectionOfReverseLinks([ReverseLinkStub::withNoType(self::ARTIFACT_ID_1), ReverseLinkStub::withNoType(self::ARTIFACT_ID_2)]);
        $other_links = new CollectionOfReverseLinks([ReverseLinkStub::withNoType(self::ARTIFACT_ID_3), ReverseLinkStub::withNoType(self::ARTIFACT_ID_2)]);

        $expected_result = new CollectionOfReverseLinks([ReverseLinkStub::withNoType(self::ARTIFACT_ID_3)]);
        $result          = $other_links->differenceById($links);

        self::assertEquals($expected_result, $result);
    }

    public function testItReturnsTheDiffByTypeOfTwoCollections(): void
    {
        $links       = new CollectionOfReverseLinks([ReverseLinkStub::withType(self::ARTIFACT_ID_1, self::ARTIFACT_NEW_TYPE), ReverseLinkStub::withNoType(self::ARTIFACT_ID_2)]);
        $other_links = new CollectionOfReverseLinks([ReverseLinkStub::withNoType(self::ARTIFACT_ID_1), ReverseLinkStub::withNoType(self::ARTIFACT_ID_2)]);

        $expected_result = new CollectionOfReverseLinks([ReverseLinkStub::withType(self::ARTIFACT_ID_1, self::ARTIFACT_NEW_TYPE)]);

        $result = $other_links->differenceByType($links);

        self::assertEquals($expected_result, $result);
    }
}
