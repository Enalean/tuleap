<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Data\SynchronizedFields;

use Psr\Log\NullLogger;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\BuildSynchronizedFields;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\Field;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFieldFromProgramAndTeamTrackersCollectionBuilder;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFields;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Source\SourceTrackerCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TrackerCollection;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Stub\BuildProjectStub;
use Tuleap\ProgramManagement\Stub\ProgramStoreStub;
use Tuleap\ProgramManagement\Stub\RetrievePlanningMilestoneTrackerStub;
use Tuleap\ProgramManagement\Stub\RetrieveVisibleProgramIncrementTrackerStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class SynchronizedFieldCollectionBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var SynchronizedFieldFromProgramAndTeamTrackersCollectionBuilder
     */
    private SynchronizedFieldFromProgramAndTeamTrackersCollectionBuilder $collection_builder;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub|BuildSynchronizedFields
     */
    private $fields_adapter;

    protected function setUp(): void
    {
        $this->fields_adapter     = $this->createStub(BuildSynchronizedFields::class);
        $this->collection_builder = new SynchronizedFieldFromProgramAndTeamTrackersCollectionBuilder(
            $this->fields_adapter,
            new NullLogger()
        );
    }

    public function testBuildFromMilestoneTrackersReturnsACollection(): void
    {
        $user           = UserTestBuilder::aUser()->build();
        $program        = ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 101, $user);
        $teams          = TeamProjectsCollection::fromProgramIdentifier(
            ProgramStoreStub::buildTeams(102, 104),
            new BuildProjectStub(),
            ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 100, UserTestBuilder::aUser()->build())
        );
        $first_tracker  = TrackerTestBuilder::aTracker()->withId(102)->build();
        $second_tracker = TrackerTestBuilder::aTracker()->withId(104)->build();
        $team_trackers  = TrackerCollection::buildRootPlanningMilestoneTrackers(
            RetrievePlanningMilestoneTrackerStub::withValidTrackers($first_tracker, $second_tracker),
            $teams,
            $user
        );

        $program_increment_tracker = TrackerTestBuilder::aTracker()->withId(67)->build();
        $all_trackers              = SourceTrackerCollection::fromProgramAndTeamTrackers(
            RetrieveVisibleProgramIncrementTrackerStub::withValidTracker($program_increment_tracker),
            $program,
            $team_trackers,
            $user
        );

        $program_increment_fields = $this->buildSynchronizedFieldsWithIds(1, 2, 3, 4, 5, 6);
        $first_team_fields        = $this->buildSynchronizedFieldsWithIds(1001, 1002, 1003, 1004, 1005, 1006);
        $second_team_fields       = $this->buildSynchronizedFieldsWithIds(2001, 2002, 2003, 2004, 2005, 2006);
        $this->fields_adapter->method('build')->willReturnOnConsecutiveCalls(
            $program_increment_fields,
            $first_team_fields,
            $second_team_fields
        );

        $expected_ids = [1, 2, 3, 4, 5, 6, 1001, 1002, 1003, 1004, 1005, 1006, 2001, 2002, 2003, 2004, 2005, 2006];
        $collection   = $this->collection_builder->buildFromSourceTrackers($all_trackers);
        self::assertEquals($expected_ids, $collection->getSynchronizedFieldIDs());
    }

    private function buildSynchronizedFieldsWithIds(
        int $artlink_id,
        int $title_id,
        int $description_id,
        int $status_id,
        int $start_date_id,
        int $end_period_id
    ): SynchronizedFields {
        $artifact_link_field_data = new Field(
            new \Tracker_FormElement_Field_ArtifactLink(
                $artlink_id,
                89,
                1000,
                'art_link',
                'Links',
                'Irrelevant',
                true,
                'P',
                false,
                '',
                1
            )
        );

        $title_field_data = new Field(
            new \Tracker_FormElement_Field_String(
                $title_id,
                89,
                1000,
                'title',
                'Title',
                'Irrelevant',
                true,
                'P',
                true,
                '',
                2
            )
        );

        $description_field_data = new Field(
            new \Tracker_FormElement_Field_Text(
                $description_id,
                89,
                1000,
                'description',
                'Description',
                'Irrelevant',
                true,
                'P',
                false,
                '',
                3
            )
        );

        $status_field_data = new Field(
            new \Tracker_FormElement_Field_Selectbox(
                $status_id,
                89,
                1000,
                'status',
                'Status',
                'Irrelevant',
                true,
                'P',
                false,
                '',
                4
            )
        );

        $start_date_field_data = new Field(
            new \Tracker_FormElement_Field_Date(
                $start_date_id,
                89,
                1000,
                'date',
                'Date',
                'Irrelevant',
                true,
                'P',
                false,
                '',
                5
            )
        );

        $end_date_field_data = new Field(
            new \Tracker_FormElement_Field_Date(
                $end_period_id,
                89,
                1000,
                'date',
                'Date',
                'Irrelevant',
                true,
                'P',
                false,
                '',
                6
            )
        );

        return new SynchronizedFields(
            $artifact_link_field_data,
            $title_field_data,
            $description_field_data,
            $status_field_data,
            $start_date_field_data,
            $end_date_field_data
        );
    }
}
