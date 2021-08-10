<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Admin;

use Tuleap\ProgramManagement\Domain\TrackerReference;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveTrackerFromProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsTeamStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyProjectPermissionStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class PotentialTrackerCollectionTest extends TestCase
{
    public function testReturnCollection(): void
    {
        $collection = PotentialTrackerCollection::fromProgram(
            RetrieveTrackerFromProgramStub::fromTrackerReference(
                TrackerReference::fromTracker(TrackerTestBuilder::aTracker()->withId(80)->withName('Sprint')->build()),
                TrackerReference::fromTracker(TrackerTestBuilder::aTracker()->withId(88)->withName('Feature')->build()),
            ),
            ProgramForAdministrationIdentifier::fromProject(
                VerifyIsTeamStub::withNotValidTeam(),
                VerifyProjectPermissionStub::withAdministrator(),
                UserTestBuilder::aUser()->build(),
                \Project::buildForTest()
            )
        );
        self::assertCount(2, $collection->trackers_reference);
        self::assertSame(80, $collection->trackers_reference[0]->id);
        self::assertSame('Sprint', $collection->trackers_reference[0]->label);
        self::assertSame(88, $collection->trackers_reference[1]->id);
        self::assertSame('Feature', $collection->trackers_reference[1]->label);
    }
}
