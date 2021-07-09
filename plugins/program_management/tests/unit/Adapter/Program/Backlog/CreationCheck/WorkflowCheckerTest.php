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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\CreationCheck;

use Psr\Log\NullLogger;
use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\ConfigurationErrorsCollector;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\Field;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFieldFromProgramAndTeamTrackers;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFieldFromProgramAndTeamTrackersCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFields;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TrackerCollection;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Stub\BuildProjectStub;
use Tuleap\ProgramManagement\Stub\SearchTeamsOfProgramStub;
use Tuleap\ProgramManagement\Stub\RetrievePlanningMilestoneTrackerStub;
use Tuleap\Test\Builders\UserTestBuilder;

final class WorkflowCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private WorkflowChecker $checker;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Workflow_Dao
     */
    private $workflow_dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Tracker_Rule_Date_Dao
     */
    private $rule_date_dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Tracker_Rule_List_Dao
     */
    private $rule_list_dao;

    protected function setUp(): void
    {
        $this->workflow_dao  = $this->createMock(\Workflow_Dao::class);
        $this->rule_date_dao = $this->createMock(\Tracker_Rule_Date_Dao::class);
        $this->rule_list_dao = $this->createMock(\Tracker_Rule_List_Dao::class);
        $this->checker       = new WorkflowChecker(
            $this->workflow_dao,
            $this->rule_date_dao,
            $this->rule_list_dao
        );
    }

    public function testConsiderTrackerTeamsAreValidWhenAllRulesAreVerified(): void
    {
        $this->workflow_dao->method('searchWorkflowsByFieldIDsAndTrackerIDs')->willReturn([]);
        $this->rule_date_dao->method('searchTrackersWithRulesByFieldIDsAndTrackerIDs')->willReturn([]);
        $this->rule_list_dao->method('searchTrackersWithRulesByFieldIDsAndTrackerIDs')->willReturn([]);

        $teams     = TeamProjectsCollection::fromProgramIdentifier(
            SearchTeamsOfProgramStub::buildTeams(),
            new BuildProjectStub(),
            ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 100, UserTestBuilder::aUser()->build())
        );
        $retriever = RetrievePlanningMilestoneTrackerStub::withValidTrackerIds(123);
        $user      = UserTestBuilder::aUser()->build();
        $trackers  = TrackerCollection::buildRootPlanningMilestoneTrackers($retriever, $teams, $user);

        $synchronized_fields = $this->buildSynchronizedFieldsCollectionFromProgramAndTeam();
        $collection          = new SynchronizedFieldFromProgramAndTeamTrackersCollection(new NullLogger());
        $collection->add($synchronized_fields);

        self::assertTrue(
            $this->checker->areWorkflowsNotUsedWithSynchronizedFieldsInTeamTrackers(
                $trackers,
                new SynchronizedFieldFromProgramAndTeamTrackersCollection(new NullLogger()),
                new ConfigurationErrorsCollector(true)
            )
        );
    }

    public function testRejectsWhenSomeWorkflowTransitionRulesAreDefinedWithASynchronizedField(): void
    {
        $this->workflow_dao->method('searchWorkflowsByFieldIDsAndTrackerIDs')->willReturn(
            [['tracker_id' => 758, 'field_id' => 963]]
        );

        $teams     = TeamProjectsCollection::fromProgramIdentifier(
            SearchTeamsOfProgramStub::buildTeams(147),
            new BuildProjectStub(),
            ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 100, UserTestBuilder::aUser()->build())
        );
        $retriever = RetrievePlanningMilestoneTrackerStub::withValidTrackerIds(758);
        $user      = UserTestBuilder::aUser()->build();
        $trackers  = TrackerCollection::buildRootPlanningMilestoneTrackers($retriever, $teams, $user);

        $synchronized_fields = $this->buildSynchronizedFieldsCollectionFromProgramAndTeam();
        $collection          = new SynchronizedFieldFromProgramAndTeamTrackersCollection(new NullLogger());
        $collection->add($synchronized_fields);

        self::assertFalse(
            $this->checker->areWorkflowsNotUsedWithSynchronizedFieldsInTeamTrackers(
                $trackers,
                new SynchronizedFieldFromProgramAndTeamTrackersCollection(new NullLogger()),
                new ConfigurationErrorsCollector(false)
            )
        );
    }

    public function testRejectsWhenSomeDateRulesAreDefinedWithASynchronizedField(): void
    {
        $this->workflow_dao->method('searchWorkflowsByFieldIDsAndTrackerIDs')->willReturn([]);
        $this->rule_date_dao->method('searchTrackersWithRulesByFieldIDsAndTrackerIDs')->willReturn([758]);

        $teams     = TeamProjectsCollection::fromProgramIdentifier(
            SearchTeamsOfProgramStub::buildTeams(147),
            new BuildProjectStub(),
            ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 100, UserTestBuilder::aUser()->build())
        );
        $retriever = RetrievePlanningMilestoneTrackerStub::withValidTrackerIds(758);
        $user      = UserTestBuilder::aUser()->build();
        $trackers  = TrackerCollection::buildRootPlanningMilestoneTrackers($retriever, $teams, $user);

        $synchronized_fields = $this->buildSynchronizedFieldsCollectionFromProgramAndTeam();
        $collection          = new SynchronizedFieldFromProgramAndTeamTrackersCollection(new NullLogger());
        $collection->add($synchronized_fields);

        self::assertFalse(
            $this->checker->areWorkflowsNotUsedWithSynchronizedFieldsInTeamTrackers(
                $trackers,
                new SynchronizedFieldFromProgramAndTeamTrackersCollection(new NullLogger()),
                new ConfigurationErrorsCollector(false)
            )
        );
    }

    public function testRejectsWhenSomeListRulesAreDefinedWithASynchronizedField(): void
    {
        $this->workflow_dao->method('searchWorkflowsByFieldIDsAndTrackerIDs')->willReturn([]);
        $this->rule_date_dao->method('searchTrackersWithRulesByFieldIDsAndTrackerIDs')->willReturn([]);
        $this->rule_list_dao->method('searchTrackersWithRulesByFieldIDsAndTrackerIDs')->willReturn([758]);

        $teams     = TeamProjectsCollection::fromProgramIdentifier(
            SearchTeamsOfProgramStub::buildTeams(147),
            new BuildProjectStub(),
            ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 100, UserTestBuilder::aUser()->build())
        );
        $retriever = RetrievePlanningMilestoneTrackerStub::withValidTrackerIds(758);
        $user      = UserTestBuilder::aUser()->build();
        $trackers  = TrackerCollection::buildRootPlanningMilestoneTrackers($retriever, $teams, $user);

        $synchronized_fields = $this->buildSynchronizedFieldsCollectionFromProgramAndTeam();
        $collection          = new SynchronizedFieldFromProgramAndTeamTrackersCollection(new NullLogger());
        $collection->add($synchronized_fields);

        self::assertFalse(
            $this->checker->areWorkflowsNotUsedWithSynchronizedFieldsInTeamTrackers(
                $trackers,
                new SynchronizedFieldFromProgramAndTeamTrackersCollection(new NullLogger()),
                new ConfigurationErrorsCollector(true)
            )
        );
    }

    public function testCollectsAllErrors(): void
    {
        $this->workflow_dao->method('searchWorkflowsByFieldIDsAndTrackerIDs')->willReturn(
            [['tracker_id' => 758, 'field_id' => 963]]
        );
        $this->rule_date_dao->method('searchTrackersWithRulesByFieldIDsAndTrackerIDs')->willReturn([123]);
        $this->rule_list_dao->method('searchTrackersWithRulesByFieldIDsAndTrackerIDs')->willReturn([758]);

        $teams     = TeamProjectsCollection::fromProgramIdentifier(
            SearchTeamsOfProgramStub::buildTeams(147),
            new BuildProjectStub(),
            ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 100, UserTestBuilder::aUser()->build())
        );
        $retriever = RetrievePlanningMilestoneTrackerStub::withValidTrackerIds(758);
        $user      = UserTestBuilder::aUser()->build();
        $trackers  = TrackerCollection::buildRootPlanningMilestoneTrackers($retriever, $teams, $user);

        $synchronized_fields = $this->buildSynchronizedFieldsCollectionFromProgramAndTeam();
        $collection          = new SynchronizedFieldFromProgramAndTeamTrackersCollection(new NullLogger());
        $collection->add($synchronized_fields);

        $errors_collector = new ConfigurationErrorsCollector(true);
        self::assertFalse(
            $this->checker->areWorkflowsNotUsedWithSynchronizedFieldsInTeamTrackers(
                $trackers,
                new SynchronizedFieldFromProgramAndTeamTrackersCollection(new NullLogger()),
                $errors_collector
            )
        );
        self::assertCount(3, $errors_collector->getErrorMessages());
        self::assertStringContainsString('transition', $errors_collector->getErrorMessages()[0]);
        self::assertStringContainsString('date', $errors_collector->getErrorMessages()[1]);
        self::assertStringContainsString('list', $errors_collector->getErrorMessages()[2]);
    }

    private function buildSynchronizedFieldsCollectionFromProgramAndTeam(): SynchronizedFieldFromProgramAndTeamTrackers
    {
        $artifact_link_field_data = new Field(new \Tracker_FormElement_Field_ArtifactLink(1001, 89, 1000, 'art_link', 'Links', 'Irrelevant', true, 'P', false, '', 1));

        $title_field_data = new Field(new \Tracker_FormElement_Field_String(1002, 89, 1000, 'title', 'Title', 'Irrelevant', true, 'P', true, '', 2));

        $description_field_data = new Field(new \Tracker_FormElement_Field_Text(1003, 89, 1000, 'description', 'Description', 'Irrelevant', true, 'P', false, '', 3));

        $status_field_data = new Field(new \Tracker_FormElement_Field_Selectbox(1004, 89, 1000, 'status', 'Status', 'Irrelevant', true, 'P', false, '', 4));

        $start_date_field_data = new Field(new \Tracker_FormElement_Field_Date(1005, 89, 1000, 'date', 'Date', 'Irrelevant', true, 'P', false, '', 5));

        $end_date_field_data = new Field(new \Tracker_FormElement_Field_Date(1006, 89, 1000, 'date', 'Date', 'Irrelevant', true, 'P', false, '', 6));

        $synchronized_fields = new SynchronizedFields(
            $artifact_link_field_data,
            $title_field_data,
            $description_field_data,
            $status_field_data,
            $start_date_field_data,
            $end_date_field_data
        );

        return new SynchronizedFieldFromProgramAndTeamTrackers($synchronized_fields);
    }
}
