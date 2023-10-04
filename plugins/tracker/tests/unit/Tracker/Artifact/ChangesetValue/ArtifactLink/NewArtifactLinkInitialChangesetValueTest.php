<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink;

use Tuleap\Option\Option;
use Tuleap\Tracker\Test\Stub\ForwardLinkStub;
use Tuleap\Tracker\Test\Stub\NewParentLinkStub;
use Tuleap\Tracker\Test\Stub\ReverseLinkStub;

final class NewArtifactLinkInitialChangesetValueTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FIELD_ID                 = 562;
    private const FIRST_ADDED_ARTIFACT_ID  = 512;
    private const SECOND_ADDED_ARTIFACT_ID = 340;
    private const THIRD_ADDED_ARTIFACT_ID  = 341;
    private const PARENT_ARTIFACT_ID       = 636;

    public function testItBuildsFromParts(): void
    {
        $collection         = new CollectionOfForwardLinks([
            ForwardLinkStub::withNoType(self::FIRST_ADDED_ARTIFACT_ID),
            ForwardLinkStub::withType(self::SECOND_ADDED_ARTIFACT_ID, 'custom_type'),
        ]);
        $reverse_collection = new CollectionOfReverseLinks([
            ReverseLinkStub::withNoType(self::THIRD_ADDED_ARTIFACT_ID),
        ]);
        $parent             = Option::fromValue(NewParentLinkStub::withId(self::PARENT_ARTIFACT_ID));
        $value              = NewArtifactLinkInitialChangesetValue::fromParts(self::FIELD_ID, $collection, $parent, $reverse_collection);

        self::assertSame(self::FIELD_ID, $value->getFieldId());
        self::assertSame($collection, $value->getNewLinks());
        self::assertSame($parent, $value->getParent());
        self::assertSame($reverse_collection, $value->getReverseLinks());
    }
}
