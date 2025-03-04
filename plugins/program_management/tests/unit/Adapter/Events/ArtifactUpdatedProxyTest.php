<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Events;

use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Event\ArtifactUpdated;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtifactUpdatedProxyTest extends TestCase
{
    public function testItBuildsFromArtifactUpdated(): void
    {
        $user          = UserTestBuilder::buildWithId(110);
        $tracker       = TrackerTestBuilder::aTracker()->withId(33)->build();
        $artifact      = ArtifactTestBuilder::anArtifact(228)->inTracker($tracker)->build();
        $changeset     = ChangesetTestBuilder::aChangeset(884)
            ->ofArtifact($artifact)
            ->submittedBy((int) $user->getId())
            ->build();
        $old_changeset = ChangesetTestBuilder::aChangeset(883)
            ->ofArtifact($artifact)
            ->submittedBy((int) $user->getId())
            ->build();

        $event = new ArtifactUpdated(
            $artifact,
            $user,
            $changeset,
            $old_changeset
        );

        $proxy = ArtifactUpdatedProxy::fromArtifactUpdated($event);
        self::assertSame(228, $proxy->getArtifact()->getId());
        self::assertSame(33, $proxy->getTracker()->getId());
        self::assertSame(110, $proxy->getUser()->getId());
        self::assertSame(884, $proxy->getChangeset()->getId());
        self::assertSame(883, $proxy->getOldChangeset()->getId());
    }
}
