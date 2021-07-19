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

namespace Tuleap\ProgramManagement\Domain\Program\Admin\IterationTrackerConfiguration;

use Tuleap\ProgramManagement\Domain\Program\Admin\PotentialTrackerCollection;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramSelectOptionConfigurationPresenter;
use Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker\RetrieveVisibleIterationTracker;
use Tuleap\ProgramManagement\Domain\TrackerReference;
use Tuleap\ProgramManagement\Stub\RetrieveTrackerFromProgramStub;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\ProgramTracker;
use Tuleap\ProgramManagement\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Stub\RetrieveVisibleIterationTrackerStub;
use Tuleap\ProgramManagement\Stub\VerifyIsTeamStub;
use Tuleap\ProgramManagement\Stub\VerifyProjectPermissionStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class PotentialIterationTrackerConfigurationPresentersBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testBuildTrackerPresentersWithCheckedTrackerIfExist(): void
    {
        $presenters = $this->getPresenters(RetrieveVisibleIterationTrackerStub::withValidTracker(TrackerTestBuilder::aTracker()->withId(300)->build()));

        self::assertCount(2, $presenters);
        self::assertSame(300, $presenters[0]->id);
        self::assertTrue($presenters[0]->is_selected);
        self::assertSame(500, $presenters[1]->id);
        self::assertFalse($presenters[1]->is_selected);
    }

    public function testBuildTrackerPresentersWhenNoIterationTracker(): void
    {
        $presenters = $this->getPresenters(RetrieveVisibleIterationTrackerStub::withNotVisibleIterationTracker());

        self::assertCount(2, $presenters);
        self::assertSame(300, $presenters[0]->id);
        self::assertFalse($presenters[0]->is_selected);
        self::assertSame(500, $presenters[1]->id);
        self::assertFalse($presenters[1]->is_selected);
    }

    /**
     * @return ProgramSelectOptionConfigurationPresenter[]
     */
    private function getPresenters(RetrieveVisibleIterationTracker $retriever): array
    {
        $builder = new PotentialIterationTrackerConfigurationPresentersBuilder();
        return $builder->buildPotentialIterationConfigurationPresenters(
            PotentialTrackerCollection::fromProgram(
                RetrieveTrackerFromProgramStub::fromTrackerReference(
                    TrackerReference::fromTracker(TrackerTestBuilder::aTracker()->withId(300)->withName('program increment tracker')->build()),
                    TrackerReference::fromTracker(TrackerTestBuilder::aTracker()->withId(500)->withName('feature tracker')->build()),
                ),
                ProgramForAdministrationIdentifier::fromProject(
                    VerifyIsTeamStub::withNotValidTeam(),
                    VerifyProjectPermissionStub::withAdministrator(),
                    UserTestBuilder::aUser()->build(),
                    \Project::buildForTest()
                )
            ),
            ProgramTracker::buildIterationTrackerFromProgram(
                $retriever,
                ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 101, UserTestBuilder::aUser()->build()),
                UserTestBuilder::aUser()->build()
            ),
        );
    }
}
