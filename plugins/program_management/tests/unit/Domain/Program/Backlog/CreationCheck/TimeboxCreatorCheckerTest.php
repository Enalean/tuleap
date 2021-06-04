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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\CreationCheck;

use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use Tracker_FormElement_Field_ArtifactLink;
use Tracker_FormElement_Field_Date;
use Tracker_FormElement_Field_Selectbox;
use Tracker_FormElement_Field_Text;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\BuildSynchronizedFields;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\Field;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\FieldRetrievalException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFieldFromProgramAndTeamTrackersCollectionBuilder;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFields;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\SourceTrackerCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TrackerCollection;
use Tuleap\ProgramManagement\Domain\ProgramTracker;
use Tuleap\ProgramManagement\Domain\Project;
use Tuleap\ProgramManagement\Stub\RetrieveRootPlanningMilestoneTrackerStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class TimeboxCreatorCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|BuildSynchronizedFields
     */
    private $fields_adapter;
    private SynchronizedFieldFromProgramAndTeamTrackersCollectionBuilder $field_collection_builder;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|CheckSemantic
     */
    private $semantic_checker;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|CheckRequiredField
     */
    private $required_field_checker;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|CheckWorkflow
     */
    private $workflow_checker;
    private \Project $project;
    private \PFUser $user;
    private \Tracker $tracker;
    private ProgramTracker $program_increment_tracker;
    private Project $program;

    protected function setUp(): void
    {
        $this->fields_adapter           = $this->createMock(BuildSynchronizedFields::class);
        $this->field_collection_builder = new SynchronizedFieldFromProgramAndTeamTrackersCollectionBuilder(
            $this->fields_adapter,
            new NullLogger()
        );
        $this->semantic_checker         = $this->createMock(CheckSemantic::class);
        $this->required_field_checker   = $this->createMock(CheckRequiredField::class);
        $this->workflow_checker         = $this->createMock(CheckWorkflow::class);

        $this->project = new \Project(
            ['group_id' => 101, 'unix_group_name' => 'proj01', 'group_name' => 'Project 01']
        );

        $this->user                      = UserTestBuilder::aUser()->build();
        $this->tracker                   = TrackerTestBuilder::aTracker()->withId(1)->withProject($this->project)->build();
        $this->program_increment_tracker = new ProgramTracker($this->tracker);
        $this->program                   = new \Tuleap\ProgramManagement\Domain\Project(101, 'my_project', "My project");
    }

    public function testItReturnsTrueIfAllChecksAreOk(): void
    {
        $root_milestone_retriever = $this->getRetrieveRootPlanningMilestoneTrackerStub(true);
        $source_tracker           = new SourceTrackerCollection([ProgramTracker::buildMilestoneTrackerFromRootPlanning($root_milestone_retriever, $this->program, $this->user)]);
        $first_team_project       = new TeamProjectsCollection([new Project(104, "proj02", "Project 02")]);
        $tracker_collection       = TrackerCollection::buildRootPlanningMilestoneTrackers($root_milestone_retriever, $first_team_project, $this->user);

        $this->semantic_checker->expects(self::once())
            ->method('areTrackerSemanticsWellConfigured')
            ->willReturn(true);

        $this->buildSynchronizedFields(true);

        $this->required_field_checker->method('areRequiredFieldsOfTeamTrackersLimitedToTheSynchronizedFields')
            ->willReturn(true);
        $this->workflow_checker->method('areWorkflowsNotUsedWithSynchronizedFieldsInTeamTrackers')
            ->willReturn(true);

        $checker = $this->getChecker();
        self::assertTrue($checker->canTimeboxBeCreated($this->program_increment_tracker, $source_tracker, $tracker_collection, $this->user));
    }

    public function testItReturnsFalseIfSemanticsAreNotWellConfigured(): void
    {
        $root_milestone_retriever = $this->getRetrieveRootPlanningMilestoneTrackerStub(true);
        $source_tracker           = new SourceTrackerCollection([ProgramTracker::buildMilestoneTrackerFromRootPlanning($root_milestone_retriever, $this->program, $this->user)]);
        $first_team_project       = new TeamProjectsCollection([new Project(104, "proj02", "Project 02")]);
        $tracker_collection       = TrackerCollection::buildRootPlanningMilestoneTrackers($root_milestone_retriever, $first_team_project, $this->user);

        $this->semantic_checker->method('areTrackerSemanticsWellConfigured')
            ->willReturn(false);

        $checker = $this->getChecker();
        self::assertFalse($checker->canTimeboxBeCreated($this->program_increment_tracker, $source_tracker, $tracker_collection, $this->user));
    }

    public function testItReturnsFalseIfUserCannotSubmitArtifact(): void
    {
        $root_milestone_retriever = $this->getRetrieveRootPlanningMilestoneTrackerStub(false);
        $source_tracker           = new SourceTrackerCollection([ProgramTracker::buildMilestoneTrackerFromRootPlanning($root_milestone_retriever, $this->program, $this->user)]);
        $first_team_project       = new TeamProjectsCollection([new Project(104, "proj02", "Project 02")]);
        $tracker_collection       = TrackerCollection::buildRootPlanningMilestoneTrackers($root_milestone_retriever, $first_team_project, $this->user);

        $this->semantic_checker->method('areTrackerSemanticsWellConfigured')->willReturn(true);

        $checker = $this->getChecker();
        self::assertFalse($checker->canTimeboxBeCreated($this->program_increment_tracker, $source_tracker, $tracker_collection, $this->user));
    }

    public function testItReturnsFalseIfFieldsCantBeExtractedFromMilestoneTrackers(): void
    {
        $root_milestone_retriever = $this->getRetrieveRootPlanningMilestoneTrackerStub(true);
        $source_tracker           = new SourceTrackerCollection([ProgramTracker::buildMilestoneTrackerFromRootPlanning($root_milestone_retriever, $this->program, $this->user)]);
        $first_team_project       = new TeamProjectsCollection([new Project(104, "proj02", "Project 02")]);
        $tracker_collection       = TrackerCollection::buildRootPlanningMilestoneTrackers($root_milestone_retriever, $first_team_project, $this->user);

        $this->semantic_checker->method('areTrackerSemanticsWellConfigured')
            ->willReturn(true);

        $this->fields_adapter->method('build')->willThrowException(new FieldRetrievalException(1, 'title'));

        $checker = $this->getChecker();
        self::assertFalse($checker->canTimeboxBeCreated($this->program_increment_tracker, $source_tracker, $tracker_collection, $this->user));
    }

    public function testItReturnsFalseIfUserCantSubmitOneArtifactLink(): void
    {
        $root_milestone_retriever = $this->getRetrieveRootPlanningMilestoneTrackerStub(true);
        $source_tracker           = new SourceTrackerCollection([ProgramTracker::buildMilestoneTrackerFromRootPlanning($root_milestone_retriever, $this->program, $this->user)]);
        $first_team_project       = new TeamProjectsCollection([new Project(104, "proj02", "Project 02")]);
        $tracker_collection       = TrackerCollection::buildRootPlanningMilestoneTrackers($root_milestone_retriever, $first_team_project, $this->user);

        $this->semantic_checker->method('areTrackerSemanticsWellConfigured')->willReturn(true);

        $this->buildSynchronizedFields(false);

        $checker = $this->getChecker();
        self::assertFalse($checker->canTimeboxBeCreated($this->program_increment_tracker, $source_tracker, $tracker_collection, $this->user));
    }

    public function testItReturnsFalseIfTrackersHaveRequiredFieldsThatCannotBeSynchronized(): void
    {
        $root_milestone_retriever = $this->getRetrieveRootPlanningMilestoneTrackerStub(true);
        $source_tracker           = new SourceTrackerCollection([ProgramTracker::buildMilestoneTrackerFromRootPlanning($root_milestone_retriever, $this->program, $this->user)]);
        $first_team_project       = new TeamProjectsCollection([new Project(104, "proj02", "Project 02")]);
        $tracker_collection       = TrackerCollection::buildRootPlanningMilestoneTrackers($root_milestone_retriever, $first_team_project, $this->user);

        $this->semantic_checker->expects(self::once())
            ->method('areTrackerSemanticsWellConfigured')
            ->willReturn(true);

        $this->buildSynchronizedFields(true);

        $this->required_field_checker->method('areRequiredFieldsOfTeamTrackersLimitedToTheSynchronizedFields')
            ->willReturn(false);

        $checker = $this->getChecker();
        self::assertFalse($checker->canTimeboxBeCreated($this->program_increment_tracker, $source_tracker, $tracker_collection, $this->user));
    }

    public function testItReturnsFalseIfTeamTrackersAreUsingSynchronizedFieldsInWorkflowRules(): void
    {
        $root_milestone_retriever = $this->getRetrieveRootPlanningMilestoneTrackerStub(true);
        $source_tracker           = new SourceTrackerCollection([ProgramTracker::buildMilestoneTrackerFromRootPlanning($root_milestone_retriever, $this->program, $this->user)]);
        $first_team_project       = new TeamProjectsCollection([new Project(104, "proj02", "Project 02")]);
        $tracker_collection       = TrackerCollection::buildRootPlanningMilestoneTrackers($root_milestone_retriever, $first_team_project, $this->user);

        $this->semantic_checker->expects(self::once())
            ->method('areTrackerSemanticsWellConfigured')
            ->willReturn(true);

        $this->buildSynchronizedFields(true);

        $this->required_field_checker->method('areRequiredFieldsOfTeamTrackersLimitedToTheSynchronizedFields')
            ->willReturn(true);
        $this->workflow_checker->method('areWorkflowsNotUsedWithSynchronizedFieldsInTeamTrackers')
            ->willReturn(false);

        $checker = $this->getChecker();
        self::assertFalse($checker->canTimeboxBeCreated($this->program_increment_tracker, $source_tracker, $tracker_collection, $this->user));
    }

    private function getRetrieveRootPlanningMilestoneTrackerStub(bool $user_can_submit_artifact_in_team = true): RetrieveRootPlanningMilestoneTrackerStub
    {
        $first_milestone_tracker = $this->createMock(\Tracker::class);
        $first_milestone_tracker->method('userCanSubmitArtifact')->willReturn($user_can_submit_artifact_in_team);
        $first_milestone_tracker->method('getId')->willReturn(1);
        return RetrieveRootPlanningMilestoneTrackerStub::withValidTrackers(
            $first_milestone_tracker,
            $first_milestone_tracker
        );
    }

    private function getChecker(): TimeboxCreatorChecker
    {
        return new TimeboxCreatorChecker(
            $this->field_collection_builder,
            $this->semantic_checker,
            $this->required_field_checker,
            $this->workflow_checker,
            new NullLogger()
        );
    }

    private function buildSynchronizedFields(bool $submitable): void
    {
        $title_field = $this->createMock(\Tracker_FormElement_Field_Text::class);
        $this->mockField($title_field, 1, true, true);
        $title_field_data = new Field($title_field);

        $artifact_link = $this->createMock(Tracker_FormElement_Field_ArtifactLink::class);
        $artifact_link->method("getLabel")->willReturn('Link');
        $artifact_link->method("getTrackerId")->willReturn(49);
        $this->mockField($artifact_link, 1, $submitable, true);
        $artifact_link_field_data = new Field($artifact_link);

        $description_field = $this->createMock(Tracker_FormElement_Field_Text::class);
        $this->mockField($description_field, 2, true, true);
        $description_field_data = new Field($description_field);

        $status_field = $this->createMock(Tracker_FormElement_Field_Selectbox::class);
        $this->mockField($status_field, 3, true, true);
        $status_field_data = new Field($status_field);

        $field_start_date = $this->createMock(Tracker_FormElement_Field_Date::class);
        $this->mockField($field_start_date, 4, true, true);
        $start_date_field_data = new Field($field_start_date);

        $field_end_date = $this->createMock(Tracker_FormElement_Field_Date::class);
        $this->mockField($field_end_date, 5, true, true);
        $end_date_field_data = new Field($field_end_date);

        $synchronized_fields = new SynchronizedFields(
            $artifact_link_field_data,
            $title_field_data,
            $description_field_data,
            $status_field_data,
            $start_date_field_data,
            $end_date_field_data
        );
        $this->fields_adapter->method('build')->willReturn($synchronized_fields);
    }

    private function mockField(MockObject $field, int $id, bool $submitable, bool $updatable): void
    {
        $field->method('getId')->willReturn($id);
        $field->method('userCanSubmit')->willReturn($submitable);
        $field->method('userCanUpdate')->willReturn($updatable);
    }
}
