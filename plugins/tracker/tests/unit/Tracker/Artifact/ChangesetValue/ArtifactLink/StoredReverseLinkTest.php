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

namespace Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink;

use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveArtifactStub;

final class StoredReverseLinkTest extends TestCase
{
    private const ARTIFACT_ID = 1045;
    private const TYPE        = '_is_child';

    public function testItReturnsNullIfTheSourceArtifactCannotBeRetrieved(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

        $reverse_link = StoredReverseLink::fromRow(
            RetrieveArtifactStub::withNoArtifact(),
            $user,
            new StoredLinkRow(self::ARTIFACT_ID, self::TYPE),
        );

        self::assertNull($reverse_link);
    }

    public function testItReturnsNullIfTheUserCannotSeeTheArtifact(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

        $reverse_link = StoredReverseLink::fromRow(
            RetrieveArtifactStub::withArtifacts(
                ArtifactTestBuilder::anArtifact(self::ARTIFACT_ID)
                    ->userCannotView($user)
                    ->build(),
            ),
            $user,
            new StoredLinkRow(self::ARTIFACT_ID, self::TYPE),
        );

        self::assertNull($reverse_link);
    }

    public function testItReturnsTheSourceArtifactAndTheType(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

        $reverse_link = StoredReverseLink::fromRow(
            RetrieveArtifactStub::withArtifacts(
                ArtifactTestBuilder::anArtifact(self::ARTIFACT_ID)
                    ->userCanView($user)
                    ->build(),
            ),
            $user,
            new StoredLinkRow(self::ARTIFACT_ID, self::TYPE),
        );

        self::assertSame(self::ARTIFACT_ID, $reverse_link->getSourceArtifactId());
        self::assertSame(self::TYPE, $reverse_link->getType());
    }

    public function testItDefaultsNullTypeToNoType(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

        $reverse_link = StoredReverseLink::fromRow(
            RetrieveArtifactStub::withArtifacts(
                ArtifactTestBuilder::anArtifact(self::ARTIFACT_ID)
                    ->userCanView($user)
                    ->build(),
            ),
            $user,
            new StoredLinkRow(self::ARTIFACT_ID, null),
        );

        self::assertSame(\Tracker_FormElement_Field_ArtifactLink::NO_TYPE, $reverse_link->getType());
    }
}
