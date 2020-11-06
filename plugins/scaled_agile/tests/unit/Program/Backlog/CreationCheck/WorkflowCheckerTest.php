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

namespace Tuleap\ScaledAgile\Program\Backlog\CreationCheck;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Fields\FieldData;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFieldDataFromProgramAndTeamTrackers;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFieldDataFromProgramAndTeamTrackersCollection;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFields;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Team\ProgramIncrementsTrackerCollection;
use Tuleap\ScaledAgile\Adapter\TrackerDataAdapter;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class WorkflowCheckerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Workflow_Dao
     */
    private $workflow_dao;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tracker_Rule_Date_Dao
     */
    private $rule_date_dao;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tracker_Rule_List_Dao
     */
    private $rule_list_dao;
    /**
     * @var WorkflowChecker
     */
    private $checker;

    protected function setUp(): void
    {
        $this->workflow_dao  = \Mockery::mock(\Workflow_Dao::class);
        $this->rule_date_dao = \Mockery::mock(\Tracker_Rule_Date_Dao::class);
        $this->rule_list_dao = \Mockery::mock(\Tracker_Rule_List_Dao::class);
        $this->checker = new WorkflowChecker(
            $this->workflow_dao,
            $this->rule_date_dao,
            $this->rule_list_dao,
            new NullLogger()
        );
    }

    public function testConsiderTrackerTeamsAreValidWhenAllRulesAreVerified(): void
    {
        $this->workflow_dao->shouldReceive('searchWorkflowsByFieldIDsAndTrackerIDs')->andReturn([]);
        $this->rule_date_dao->shouldReceive('searchTrackersWithRulesByFieldIDsAndTrackerIDs')->andReturn([]);
        $this->rule_list_dao->shouldReceive('searchTrackersWithRulesByFieldIDsAndTrackerIDs')->andReturn([]);

        $synchronized_fields = $this->buildSynchronizedFieldsCollectionFromProgramAndTeam();
        $collection = new SynchronizedFieldDataFromProgramAndTeamTrackersCollection();
        $collection->add($synchronized_fields);

        $this->assertTrue(
            $this->checker->areWorkflowsNotUsedWithSynchronizedFieldsInTeamTrackers(
                new ProgramIncrementsTrackerCollection([]),
                new SynchronizedFieldDataFromProgramAndTeamTrackersCollection()
            )
        );
    }

    public function testRejectsWhenSomeWorkflowTransitionRulesAreDefinedWithASynchronizedField(): void
    {
        $this->workflow_dao->shouldReceive('searchWorkflowsByFieldIDsAndTrackerIDs')->andReturn(
            [['tracker_id' => 758, 'field_id' => 963]]
        );

        $tracker = TrackerTestBuilder::aTracker()->withId(758)->withProject(new \Project(['group_id' => 147]))->build();
        $synchronized_fields = $this->buildSynchronizedFieldsCollectionFromProgramAndTeam();
        $collection = new SynchronizedFieldDataFromProgramAndTeamTrackersCollection();
        $collection->add($synchronized_fields);

        $this->assertFalse(
            $this->checker->areWorkflowsNotUsedWithSynchronizedFieldsInTeamTrackers(
                new ProgramIncrementsTrackerCollection([TrackerDataAdapter::build($tracker)]),
                new SynchronizedFieldDataFromProgramAndTeamTrackersCollection()
            )
        );
    }

    public function testRejectsWhenSomeDateRulesAreDefinedWithASynchronizedField(): void
    {
        $this->workflow_dao->shouldReceive('searchWorkflowsByFieldIDsAndTrackerIDs')->andReturn([]);
        $this->rule_date_dao->shouldReceive('searchTrackersWithRulesByFieldIDsAndTrackerIDs')->andReturn([758]);

        $tracker = TrackerTestBuilder::aTracker()->withId(758)->withProject(new \Project(['group_id' => 147]))->build();
        $synchronized_fields = $this->buildSynchronizedFieldsCollectionFromProgramAndTeam();
        $collection = new SynchronizedFieldDataFromProgramAndTeamTrackersCollection();
        $collection->add($synchronized_fields);

        $this->assertFalse(
            $this->checker->areWorkflowsNotUsedWithSynchronizedFieldsInTeamTrackers(
                new ProgramIncrementsTrackerCollection([TrackerDataAdapter::build($tracker)]),
                new SynchronizedFieldDataFromProgramAndTeamTrackersCollection()
            )
        );
    }

    public function testRejectsWhenSomeListRulesAreDefinedWithASynchronizedField(): void
    {
        $this->workflow_dao->shouldReceive('searchWorkflowsByFieldIDsAndTrackerIDs')->andReturn([]);
        $this->rule_date_dao->shouldReceive('searchTrackersWithRulesByFieldIDsAndTrackerIDs')->andReturn([]);
        $this->rule_list_dao->shouldReceive('searchTrackersWithRulesByFieldIDsAndTrackerIDs')->andReturn([758]);

        $tracker = TrackerTestBuilder::aTracker()->withId(758)->withProject(new \Project(['group_id' => 147]))->build();
        $synchronized_fields = $this->buildSynchronizedFieldsCollectionFromProgramAndTeam();
        $collection = new SynchronizedFieldDataFromProgramAndTeamTrackersCollection();
        $collection->add($synchronized_fields);

        $this->assertFalse(
            $this->checker->areWorkflowsNotUsedWithSynchronizedFieldsInTeamTrackers(
                new ProgramIncrementsTrackerCollection([TrackerDataAdapter::build($tracker)]),
                new SynchronizedFieldDataFromProgramAndTeamTrackersCollection()
            )
        );
    }

    private function buildSynchronizedFieldsCollectionFromProgramAndTeam(): SynchronizedFieldDataFromProgramAndTeamTrackers
    {
        $artifact_link_field_data = new FieldData(new \Tracker_FormElement_Field_ArtifactLink(1001, 89, 1000, 'art_link', 'Links', 'Irrelevant', true, 'P', false, '', 1));

        $title_field_data = new FieldData(new \Tracker_FormElement_Field_String(1002, 89, 1000, 'title', 'Title', 'Irrelevant', true, 'P', true, '', 2));

        $description_field_data = new FieldData(new \Tracker_FormElement_Field_Text(1003, 89, 1000, 'description', 'Description', 'Irrelevant', true, 'P', false, '', 3));

        $status_field_data = new FieldData(new \Tracker_FormElement_Field_Selectbox(1004, 89, 1000, 'status', 'Status', 'Irrelevant', true, 'P', false, '', 4));

        $start_date_field_data = new FieldData(new \Tracker_FormElement_Field_Date(1005, 89, 1000, 'date', 'Date', 'Irrelevant', true, 'P', false, '', 5));

        $end_date_field_data = new FieldData(new \Tracker_FormElement_Field_Date(1006, 89, 1000, 'date', 'Date', 'Irrelevant', true, 'P', false, '', 6));

        $synchronized_fields = new SynchronizedFields(
            $artifact_link_field_data,
            $title_field_data,
            $description_field_data,
            $status_field_data,
            $start_date_field_data,
            $end_date_field_data
        );

        return new SynchronizedFieldDataFromProgramAndTeamTrackers($synchronized_fields);
    }
}
