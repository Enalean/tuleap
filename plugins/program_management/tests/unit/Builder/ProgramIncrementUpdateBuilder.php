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

namespace Tuleap\ProgramManagement\Tests\Builder;

use Tuleap\ProgramManagement\Adapter\Events\ArtifactUpdatedProxy;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementUpdate;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProgramIncrementTrackerStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Event\ArtifactUpdated;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class ProgramIncrementUpdateBuilder
{
    public static function build(): ProgramIncrementUpdate
    {
        $user      = UserTestBuilder::aUser()->withId(141)->build();
        $tracker   = TrackerTestBuilder::aTracker()->withId(20)->build();
        $artifact  = ArtifactTestBuilder::anArtifact(334)->inTracker($tracker)->build();
        $changeset = ChangesetTestBuilder::aChangeset('7516')
            ->ofArtifact($artifact)
            ->submittedBy($user->getId())
            ->build();

        $tracker_event = new ArtifactUpdated($artifact, $user, $changeset);
        $proxy         = ArtifactUpdatedProxy::fromArtifactUpdated($tracker_event);
        return ProgramIncrementUpdate::fromArtifactUpdatedEvent(
            VerifyIsProgramIncrementTrackerStub::buildValidProgramIncrement(),
            $proxy
        );
    }
}
