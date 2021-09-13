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

namespace Tuleap\ProgramManagement\Domain\Program\Admin\TimeboxTrackerConfiguration;

use Tuleap\ProgramManagement\Domain\Program\Admin\PotentialTrackerCollection;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramSelectOptionConfigurationPresenter;
use Tuleap\ProgramManagement\Domain\ProgramTracker;
use Tuleap\ProgramManagement\Tests\Builder\ProgramForAdministrationIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\ProgramTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveTrackerFromProgramStub;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class PotentialTimeboxTrackerConfigurationPresenterCollectionTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ProgramForAdministrationIdentifier $program;

    protected function setUp(): void
    {
        $this->program = ProgramForAdministrationIdentifierBuilder::build();
    }

    public function testBuildTrackerPresentersWithCheckedTrackerIfExist(): void
    {
        $presenters = $this->getPresenters(
            ProgramTrackerStub::withId(300)
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
        return PotentialTimeboxTrackerConfigurationPresenterCollection::fromTimeboxTracker(
            PotentialTrackerCollection::fromProgram(
                RetrieveTrackerFromProgramStub::fromProgramReference(
                    ProgramTrackerStub::fromTracker(TrackerTestBuilder::aTracker()->withId(300)->withName('program increment tracker')->build()),
                    ProgramTrackerStub::fromTracker(TrackerTestBuilder::aTracker()->withId(500)->withName('feature tracker')->build()),
                ),
                $this->program
            ),
            $program_tracker
        )->presenters;
    }
}
