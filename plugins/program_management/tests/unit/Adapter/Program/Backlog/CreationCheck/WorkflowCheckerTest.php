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
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\RetrieveTrackerFromField;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFieldFromProgramAndTeamTrackers;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFieldFromProgramAndTeamTrackersCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFields;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TrackerCollection;
use Tuleap\ProgramManagement\Domain\Workspace\RetrieveUser;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\BuildProjectStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveTrackerFromFieldStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchTeamsOfProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrievePlanningMilestoneTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

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
    private RetrieveTrackerFromField $retrieve_tracker_from_field;
    private RetrieveUser $retrieve_user;

    protected function setUp(): void
    {
        $this->workflow_dao                = $this->createMock(\Workflow_Dao::class);
        $this->rule_date_dao               = $this->createMock(\Tracker_Rule_Date_Dao::class);
        $this->rule_list_dao               = $this->createMock(\Tracker_Rule_List_Dao::class);
        $this->tracker_factory             = $this->createMock(\TrackerFactory::class);
        $this->retrieve_tracker_from_field = RetrieveTrackerFromFieldStub::with(1, 'tracker');
        $this->checker                     = new WorkflowChecker(
            $this->workflow_dao,
            $this->rule_date_dao,
            $this->rule_list_dao,
            $this->tracker_factory
        );
        $this->retrieve_user               = RetrieveUserStub::withGenericUser();
    }

    public function testConsiderTrackerTeamsAreValidWhenAllRulesAreVerified(): void
    {
        $this->workflow_dao->method('searchWorkflowsByFieldIDsAndTrackerIDs')->willReturn([]);
        $this->rule_date_dao->method('searchTrackersWithRulesByFieldIDsAndTrackerIDs')->willReturn([]);
        $this->rule_list_dao->method('searchTrackersWithRulesByFieldIDsAndTrackerIDs')->willReturn([]);

        $teams     = TeamProjectsCollection::fromProgramIdentifier(
            SearchTeamsOfProgramStub::buildTeams(),
            new BuildProjectStub(),
            ProgramIdentifierBuilder::build()
        );
        $retriever = RetrievePlanningMilestoneTrackerStub::withValidTrackerIds(123);
        $this->tracker_factory->method('getTrackerById')
            ->with(123)
            ->willReturn(TrackerTestBuilder::aTracker()->withId(123)->withName('tracker')->build());
        $trackers = TrackerCollection::buildRootPlanningMilestoneTrackers($retriever, $teams, UserIdentifierStub::buildGenericUser());

        $synchronized_fields = $this->buildSynchronizedFieldsCollectionFromProgramAndTeam();
        $collection          = new SynchronizedFieldFromProgramAndTeamTrackersCollection(new NullLogger(), $this->retrieve_user, $this->retrieve_tracker_from_field);
        $collection->add($synchronized_fields);

        self::assertTrue(
            $this->checker->areWorkflowsNotUsedWithSynchronizedFieldsInTeamTrackers(
                $trackers,
                new SynchronizedFieldFromProgramAndTeamTrackersCollection(new NullLogger(), $this->retrieve_user, $this->retrieve_tracker_from_field),
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
            ProgramIdentifierBuilder::build()
        );
        $retriever = RetrievePlanningMilestoneTrackerStub::withValidTrackerIds(758);
        $this->tracker_factory->method('getTrackerById')
            ->with(758)
            ->willReturn(TrackerTestBuilder::aTracker()->withId(758)->withName('tracker')->build());
        $trackers = TrackerCollection::buildRootPlanningMilestoneTrackers($retriever, $teams, UserIdentifierStub::buildGenericUser());

        $synchronized_fields = $this->buildSynchronizedFieldsCollectionFromProgramAndTeam();
        $collection          = new SynchronizedFieldFromProgramAndTeamTrackersCollection(new NullLogger(), $this->retrieve_user, $this->retrieve_tracker_from_field);
        $collection->add($synchronized_fields);

        self::assertFalse(
            $this->checker->areWorkflowsNotUsedWithSynchronizedFieldsInTeamTrackers(
                $trackers,
                new SynchronizedFieldFromProgramAndTeamTrackersCollection(new NullLogger(), $this->retrieve_user, $this->retrieve_tracker_from_field),
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
            ProgramIdentifierBuilder::build()
        );
        $retriever = RetrievePlanningMilestoneTrackerStub::withValidTrackerIds(758);
        $this->tracker_factory->method('getTrackerById')
            ->with(758)
            ->willReturn(TrackerTestBuilder::aTracker()->withId(758)->withName('tracker')->build());
        $trackers = TrackerCollection::buildRootPlanningMilestoneTrackers($retriever, $teams, UserIdentifierStub::buildGenericUser());

        $synchronized_fields = $this->buildSynchronizedFieldsCollectionFromProgramAndTeam();
        $collection          = new SynchronizedFieldFromProgramAndTeamTrackersCollection(new NullLogger(), $this->retrieve_user, $this->retrieve_tracker_from_field);
        $collection->add($synchronized_fields);

        self::assertFalse(
            $this->checker->areWorkflowsNotUsedWithSynchronizedFieldsInTeamTrackers(
                $trackers,
                new SynchronizedFieldFromProgramAndTeamTrackersCollection(new NullLogger(), $this->retrieve_user, $this->retrieve_tracker_from_field),
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
            ProgramIdentifierBuilder::build()
        );
        $retriever = RetrievePlanningMilestoneTrackerStub::withValidTrackerIds(758);
        $this->tracker_factory->method('getTrackerById')
            ->with(758)
            ->willReturn(TrackerTestBuilder::aTracker()->withId(758)->withName('tracker')->build());
        $trackers = TrackerCollection::buildRootPlanningMilestoneTrackers($retriever, $teams, UserIdentifierStub::buildGenericUser());

        $synchronized_fields = $this->buildSynchronizedFieldsCollectionFromProgramAndTeam();
        $collection          = new SynchronizedFieldFromProgramAndTeamTrackersCollection(new NullLogger(), $this->retrieve_user, $this->retrieve_tracker_from_field);
        $collection->add($synchronized_fields);

        self::assertFalse(
            $this->checker->areWorkflowsNotUsedWithSynchronizedFieldsInTeamTrackers(
                $trackers,
                new SynchronizedFieldFromProgramAndTeamTrackersCollection(new NullLogger(), $this->retrieve_user, $this->retrieve_tracker_from_field),
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
            ProgramIdentifierBuilder::build()
        );
        $retriever = RetrievePlanningMilestoneTrackerStub::withValidTrackerIds(758);
        $this->tracker_factory->method('getTrackerById')
            ->willReturnOnConsecutiveCalls(
                TrackerTestBuilder::aTracker()->withId(758)->withName('tracker A')->build(),
                TrackerTestBuilder::aTracker()->withId(123)->withName('tracker B')->build(),
                TrackerTestBuilder::aTracker()->withId(758)->withName('tracker A')->build(),
            );
        $trackers = TrackerCollection::buildRootPlanningMilestoneTrackers($retriever, $teams, UserIdentifierStub::buildGenericUser());

        $synchronized_fields = $this->buildSynchronizedFieldsCollectionFromProgramAndTeam();
        $collection          = new SynchronizedFieldFromProgramAndTeamTrackersCollection(new NullLogger(), $this->retrieve_user, $this->retrieve_tracker_from_field);
        $collection->add($synchronized_fields);

        $errors_collector = new ConfigurationErrorsCollector(true);
        self::assertFalse(
            $this->checker->areWorkflowsNotUsedWithSynchronizedFieldsInTeamTrackers(
                $trackers,
                new SynchronizedFieldFromProgramAndTeamTrackersCollection(new NullLogger(), $this->retrieve_user, $this->retrieve_tracker_from_field),
                $errors_collector
            )
        );

        self::assertSame(758, $errors_collector->getFieldDependencyError()[0]->tracker_id);
        self::assertSame(123, $errors_collector->getTransitionRuleDateError()[0]->tracker_id);
        self::assertSame(758, $errors_collector->getTransitionRuleError()[0]->tracker_id);
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
