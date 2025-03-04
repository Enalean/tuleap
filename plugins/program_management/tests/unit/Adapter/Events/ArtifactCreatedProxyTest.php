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
use Tuleap\Tracker\Artifact\Event\ArtifactCreated;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtifactCreatedProxyTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItBuildsFromArtifactCreated(): void
    {
        $user      = UserTestBuilder::buildWithId(116);
        $tracker   = TrackerTestBuilder::aTracker()->withId(15)->build();
        $artifact  = ArtifactTestBuilder::anArtifact(246)->inTracker($tracker)->build();
        $changeset = ChangesetTestBuilder::aChangeset(1090)
            ->ofArtifact($artifact)
            ->submittedBy((int) $user->getId())
            ->build();

        $event = new ArtifactCreated($artifact, $changeset, $user);

        $proxy = ArtifactCreatedProxy::fromArtifactCreated($event);
        self::assertSame(246, $proxy->getArtifact()->getId());
        self::assertSame(15, $proxy->getTracker()->getId());
        self::assertSame(116, $proxy->getUser()->getId());
        self::assertSame(1090, $proxy->getChangeset()->getId());
    }
}
