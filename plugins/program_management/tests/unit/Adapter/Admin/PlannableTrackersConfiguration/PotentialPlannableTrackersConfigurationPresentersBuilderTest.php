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

namespace Tuleap\ProgramManagement\Adapter\Program\Admin\PlannableTrackersConfiguration;

use Tuleap\ProgramManagement\Domain\Program\Admin\PotentialTrackerCollection;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramSelectOptionConfigurationPresenter;
use Tuleap\ProgramManagement\Domain\Program\Plan\RetrievePlannableTrackers;
use Tuleap\ProgramManagement\Tests\Builder\ProgramForAdministrationIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrievePlannableTrackersStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchTrackersOfProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\TrackerReferenceStub;
use Tuleap\Test\PHPUnit\TestCase;

final class PotentialPlannableTrackersConfigurationPresentersBuilderTest extends TestCase
{
    private const FIRST_TRACKER_ID  = 300;
    private const SECOND_TRACKER_ID = 500;
    private ProgramForAdministrationIdentifier $program;

    protected function setUp(): void
    {
        $this->program = ProgramForAdministrationIdentifierBuilder::build();
    }

    public function testBuildTrackerPresentersWithCheckedTrackerIfExist(): void
    {
        $presenters = $this->getPresenters(RetrievePlannableTrackersStub::buildIds(300));

        self::assertCount(2, $presenters);
        self::assertSame(self::FIRST_TRACKER_ID, $presenters[0]->id);
        self::assertTrue($presenters[0]->is_selected);
        self::assertSame(self::SECOND_TRACKER_ID, $presenters[1]->id);
        self::assertFalse($presenters[1]->is_selected);
    }

    public function testBuildTrackerPresentersWithoutCheckedTracker(): void
    {
        $presenters = $this->getPresenters(RetrievePlannableTrackersStub::buildIds());

        self::assertCount(2, $presenters);
        self::assertSame(self::FIRST_TRACKER_ID, $presenters[0]->id);
        self::assertFalse($presenters[0]->is_selected);
        self::assertSame(self::SECOND_TRACKER_ID, $presenters[1]->id);
        self::assertFalse($presenters[1]->is_selected);
    }

    /**
     * @return ProgramSelectOptionConfigurationPresenter[]
     */
    private function getPresenters(RetrievePlannableTrackers $retriever): array
    {
        $builder = new PotentialPlannableTrackersConfigurationPresentersBuilder($retriever);
        return $builder->buildPotentialPlannableTrackerPresenters(
            $this->program,
            PotentialTrackerCollection::fromProgram(
                SearchTrackersOfProgramStub::withTrackers(
                    TrackerReferenceStub::withId(self::FIRST_TRACKER_ID),
                    TrackerReferenceStub::withId(self::SECOND_TRACKER_ID),
                ),
                $this->program
            )
        );
    }
}
