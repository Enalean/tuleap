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
    public function testItReturnsTheDiffOffTwoCollection(): void
    {
        $links       = new CollectionOfReverseLinks([ReverseLinkStub::withNoType(15), ReverseLinkStub::withNoType(20)]);
        $other_links = new CollectionOfReverseLinks([ReverseLinkStub::withNoType(100), ReverseLinkStub::withNoType(20)]);

        $expected_result = new CollectionOfReverseLinks([ReverseLinkStub::withNoType(100)]);
        $result          = $other_links->differenceById($links);

        self::assertEquals($expected_result, $result);
    }
}
