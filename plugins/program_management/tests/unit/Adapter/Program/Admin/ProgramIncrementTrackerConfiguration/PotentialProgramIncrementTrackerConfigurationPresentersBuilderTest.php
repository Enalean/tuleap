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

namespace Tuleap\ProgramManagement\Adapter\Program\Admin\ProgramIncrementTrackerConfiguration;

use Tuleap\ProgramManagement\Domain\Program\Admin\PotentialTrackerCollection;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramIncrementTrackerConfiguration\PotentialProgramIncrementTrackerConfigurationPresentersBuilder;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramSelectOptionConfigurationPresenter;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\ProgramTracker;
use Tuleap\ProgramManagement\Domain\TrackerReference;
use Tuleap\ProgramManagement\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Stub\RetrieveTrackerFromProgramStub;
use Tuleap\ProgramManagement\Stub\RetrieveVisibleProgramIncrementTrackerStub;
use Tuleap\ProgramManagement\Stub\VerifyIsTeamStub;
use Tuleap\ProgramManagement\Stub\VerifyProjectPermissionStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class PotentialProgramIncrementTrackerConfigurationPresentersBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private \PFUser $user;
    private ProgramForAdministrationIdentifier $program;

    protected function setUp(): void
    {
        $this->user    = UserTestBuilder::aUser()->build();
        $this->program = ProgramForAdministrationIdentifier::fromProject(
            VerifyIsTeamStub::withNotValidTeam(),
            VerifyProjectPermissionStub::withAdministrator(),
            $this->user,
            ProjectTestBuilder::aProject()->withId(101)->build()
        );
    }

    public function testBuildTrackerPresentersWithCheckedTrackerIfExist(): void
    {
        $presenters = $this->getPresenters(
            ProgramTracker::buildProgramIncrementTrackerFromProgram(
                RetrieveVisibleProgramIncrementTrackerStub::withValidTracker(
                    TrackerTestBuilder::aTracker()->withId(300)->build()
                ),
                ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 101, $this->user),
                $this->user
            )
        );

        self::assertCount(2, $presenters);
        self::assertSame(300, $presenters[0]->id);
        self::assertTrue($presenters[0]->is_selected);
        self::assertSame(500, $presenters[1]->id);
        self::assertFalse($presenters[1]->is_selected);
    }

    public function testBuildTrackerPresentersWithoutCheckedTracker(): void
    {
        $presenters = $this->getPresenters(null);

        self::assertCount(2, $presenters);
        self::assertSame(300, $presenters[0]->id);
        self::assertFalse($presenters[0]->is_selected);
        self::assertSame(500, $presenters[1]->id);
        self::assertFalse($presenters[1]->is_selected);
    }

    /**
     * @return ProgramSelectOptionConfigurationPresenter[]
     */
    private function getPresenters(?ProgramTracker $program_tracker): array
    {
        $builder = new PotentialProgramIncrementTrackerConfigurationPresentersBuilder();
        return $builder->buildPotentialProgramIncrementTrackerPresenters(
            $this->program,
            $program_tracker,
            PotentialTrackerCollection::fromProgram(
                RetrieveTrackerFromProgramStub::fromTrackerReference(
                    TrackerReference::fromTracker(TrackerTestBuilder::aTracker()->withId(300)->withName('program increment tracker')->build()),
                    TrackerReference::fromTracker(TrackerTestBuilder::aTracker()->withId(500)->withName('feature tracker')->build()),
                ),
                $this->program
            )
        );
    }
}
