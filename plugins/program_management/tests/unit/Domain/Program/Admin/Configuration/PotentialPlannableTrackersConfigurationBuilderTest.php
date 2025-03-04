<?php
/**
 * Copyright (c) Enalean 2022 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Admin\Configuration;

use Tuleap\ProgramManagement\Domain\Program\Admin\PotentialTrackerCollection;
use Tuleap\ProgramManagement\Tests\Builder\ProgramForAdministrationIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrievePlannableTrackersStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchTrackersOfProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\TrackerReferenceStub;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PotentialPlannableTrackersConfigurationBuilderTest extends TestCase
{
    public function testItBuildsAListOfPotentialPlannableTrackers(): void
    {
        $tracker1                     = TrackerReferenceStub::withId(1);
        $tracker2                     = TrackerReferenceStub::withId(2);
        $plannable_trackers_retriever = RetrievePlannableTrackersStub::build($tracker1);
        $builder                      = new PotentialPlannableTrackersConfigurationBuilder($plannable_trackers_retriever);

        $search_trackers_of_program   = SearchTrackersOfProgramStub::withTrackers($tracker1, $tracker2);
        $program                      = ProgramForAdministrationIdentifierBuilder::build();
        $potential_tracker_collection = PotentialTrackerCollection::fromProgram($search_trackers_of_program, $program);

        $expected_selection_configuration = [
            new ProgramSelectOptionConfiguration(
                $tracker1->getId(),
                $tracker1->getLabel(),
                true
            ),
            new ProgramSelectOptionConfiguration(
                $tracker2->getId(),
                $tracker2->getLabel(),
                false
            ),
        ];

        self::assertEquals($expected_selection_configuration, $builder->buildPotentialPlannableTracker($program, $potential_tracker_collection));
    }
}
