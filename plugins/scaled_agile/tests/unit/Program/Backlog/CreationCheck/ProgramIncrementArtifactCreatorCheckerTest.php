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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Project;
use ProjectManager;
use Psr\Log\NullLogger;
use Tracker_FormElement_Field_ArtifactLink;
use Tracker_FormElement_Field_Date;
use Tracker_FormElement_Field_Selectbox;
use Tracker_FormElement_Field_Text;
use Tuleap\ScaledAgile\Adapter\Program\SynchronizedFieldsAdapter;
use Tuleap\ScaledAgile\Program\Backlog\ProgramDao;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollectionBuilder;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Fields\FieldData;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Fields\FieldRetrievalException;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFieldDataFromProgramAndTeamTrackersCollectionBuilder;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFields;
use Tuleap\ScaledAgile\Program\Backlog\TrackerCollectionFactory;
use Tuleap\ScaledAgile\Adapter\Program\PlanningAdapter;
use Tuleap\ScaledAgile\Program\PlanningConfiguration\PlanningData;
use Tuleap\ScaledAgile\Adapter\ProjectDataAdapter;
use Tuleap\ScaledAgile\Adapter\TrackerDataAdapter;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class ProgramIncrementArtifactCreatorCheckerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|MockInterface|SynchronizedFieldsAdapter
     */
    private $fields_adapter;

    /**
     * @var Mockery\LegacyMockInterface|MockInterface|\PlanningFactory
     */
    private $planning_factory;

    /**
     * @var Mockery\LegacyMockInterface|MockInterface|ProjectManager
     */
    private $project_manager;

    /**
     * @var Mockery\LegacyMockInterface|MockInterface|ProgramDao
     */
    private $program_dao;

    /**
     * @var \Tuleap\ScaledAgile\ProjectData
     */
    private $project_data;

    /**
     * @var ProgramIncrementArtifactCreatorChecker
     */
    private $checker;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|SynchronizedFieldDataFromProgramAndTeamTrackersCollectionBuilder
     */
    private $field_collection_builder;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|SemanticChecker
     */
    private $semantic_checker;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|RequiredFieldChecker
     */
    private $required_field_checker;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|WorkflowChecker
     */
    private $workflow_checker;
    /**
     * @var Project
     */
    private $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->program_dao     = Mockery::mock(ProgramDao::class);
        $this->project_manager = Mockery::mock(ProjectManager::class);
        $project_data_adapter  = new ProjectDataAdapter($this->project_manager);

        $projects_collection_builder = new TeamProjectsCollectionBuilder(
            $this->program_dao,
            $project_data_adapter
        );

        $this->planning_factory = Mockery::mock(\PlanningFactory::class);
        $planning_adapter       = new PlanningAdapter($this->planning_factory);
        $trackers_builder       = new TrackerCollectionFactory($planning_adapter);

        $this->fields_adapter           = Mockery::mock(SynchronizedFieldsAdapter::class);
        $this->field_collection_builder = new SynchronizedFieldDataFromProgramAndTeamTrackersCollectionBuilder(
            $this->fields_adapter
        );
        $this->semantic_checker         = Mockery::mock(SemanticChecker::class);
        $this->required_field_checker   = Mockery::mock(RequiredFieldChecker::class);
        $this->workflow_checker         = Mockery::mock(WorkflowChecker::class);

        $this->checker = new ProgramIncrementArtifactCreatorChecker(
            $projects_collection_builder,
            $trackers_builder,
            $this->field_collection_builder,
            $this->semantic_checker,
            $this->required_field_checker,
            $this->workflow_checker,
            new NullLogger()
        );

        $this->project = new Project(
            ['group_id' => '101', 'unix_group_name' => 'proj01', 'group_name' => 'Project 01']
        );

        $this->project_data = ProjectDataAdapter::build($this->project);
    }

    public function testItReturnsTrueIfAllChecksAreOk(): void
    {
        $user              = UserTestBuilder::aUser()->build();
        $program_milestone = $this->getPlanningData();

        $this->mockTeamMilestoneTrackers($this->project);
        $this->semantic_checker->shouldReceive('areTrackerSemanticsWellConfigured')
            ->once()
            ->andReturnTrue();

        $this->buildSynchronizedFields(true);

        $this->required_field_checker->shouldReceive('areRequiredFieldsOfTeamTrackersLimitedToTheSynchronizedFields')
            ->andReturnTrue();
        $this->workflow_checker->shouldReceive('areWorkflowsNotUsedWithSynchronizedFieldsInTeamTrackers')
            ->andReturnTrue();

        $this->assertTrue($this->checker->canProgramIncrementBeCreated($program_milestone, $user));
    }

    public function testItReturnsTrueWhenAProjectHasNoTeamProjects(): void
    {
        $user              = UserTestBuilder::aUser()->build();
        $program_milestone = $this->getPlanningData();

        $this->program_dao->shouldReceive('getTeamProjectIdsForGivenProgramProject')->andReturn([]);

        $this->assertTrue($this->checker->canProgramIncrementBeCreated($program_milestone, $user));
    }

    public function testItReturnsFalseIfOneProjectDoesNotHaveARootPlanningWithAMilestoneTracker(): void
    {
        $user              = UserTestBuilder::aUser()->build();
        $program_milestone = $this->getPlanningData();

        $planning = new \Planning(1, 'Incorrect', $this->project->getID(), '', '');
        $planning->setPlanningTracker(new \NullTracker());
        $this->planning_factory->shouldReceive('getRootPlanning')->andReturn($planning);

        $first_team_project = new \Project(
            ['group_id' => '104', 'unix_group_name' => 'proj02', 'group_name' => 'Project 02']
        );
        $this->program_dao->shouldReceive('getTeamProjectIdsForGivenProgramProject')
            ->andReturn([['team_project_id' => $first_team_project->getID()]]);
        $this->project_manager->shouldReceive('getProject')
            ->with($first_team_project->getID())
            ->once()
            ->andReturn($first_team_project);

        $this->assertFalse($this->checker->canProgramIncrementBeCreated($program_milestone, $user));
    }

    public function testItReturnsFalseIfSemanticsAreNotWellConfigured(): void
    {
        $user              = UserTestBuilder::aUser()->build();
        $program_milestone = $this->getPlanningData();

        $this->mockTeamMilestoneTrackers($this->project);
        $this->semantic_checker->shouldReceive('areTrackerSemanticsWellConfigured')
            ->andReturnFalse();

        $this->assertFalse($this->checker->canProgramIncrementBeCreated($program_milestone, $user));
    }

    public function testItReturnsFalseIfUserCannotSubmitArtifact(): void
    {
        $user                 = UserTestBuilder::aUser()->build();
        $program_milestone    = $this->getPlanningData();

        $this->mockTeamMilestoneTrackers($this->project, false);
        $this->semantic_checker->shouldReceive('areTrackerSemanticsWellConfigured')
            ->andReturnTrue();

        $this->assertFalse($this->checker->canProgramIncrementBeCreated($program_milestone, $user));
    }

    public function testItReturnsFalseIfFieldsCantBeExtractedFromMilestoneTrackers(): void
    {
        $user              = UserTestBuilder::aUser()->build();
        $program_milestone = $this->getPlanningData();

        $this->mockTeamMilestoneTrackers($this->project);
        $this->semantic_checker->shouldReceive('areTrackerSemanticsWellConfigured')
            ->andReturnTrue();

        $this->fields_adapter->shouldReceive('build')
            ->andThrow(new FieldRetrievalException(1, 'title'));

        $this->assertFalse($this->checker->canProgramIncrementBeCreated($program_milestone, $user));
    }

    public function testItReturnsFalseIfUserCantSubmitOneArtifactLink(): void
    {
        $user                 = UserTestBuilder::aUser()->build();
        $program_milestone    = $this->getPlanningData();

        $this->mockTeamMilestoneTrackers($this->project);
        $this->semantic_checker->shouldReceive('areTrackerSemanticsWellConfigured')
            ->andReturnTrue();

        $this->buildSynchronizedFields(false);

        $this->assertFalse($this->checker->canProgramIncrementBeCreated($program_milestone, $user));
    }

    public function testItReturnsFalseIfTrackersHaveRequiredFieldsThatCannotBeSynchronized(): void
    {
        $user                 = UserTestBuilder::aUser()->build();
        $program_milestone    = $this->getPlanningData();

        $this->mockTeamMilestoneTrackers($this->project);
        $this->semantic_checker->shouldReceive('areTrackerSemanticsWellConfigured')
            ->once()
            ->andReturnTrue();

        $this->buildSynchronizedFields(true);

        $this->required_field_checker->shouldReceive('areRequiredFieldsOfTeamTrackersLimitedToTheSynchronizedFields')
            ->andReturnFalse();

        $this->assertFalse($this->checker->canProgramIncrementBeCreated($program_milestone, $user));
    }

    public function testItReturnsFalseIfTeamTrackersAreUsingSynchronizedFieldsInWorkflowRules(): void
    {
        $user                 = UserTestBuilder::aUser()->build();
        $program_milestone    = $this->getPlanningData();

        $this->mockTeamMilestoneTrackers($this->project);
        $this->semantic_checker->shouldReceive('areTrackerSemanticsWellConfigured')
            ->once()
            ->andReturnTrue();

        $this->buildSynchronizedFields(true);

        $this->required_field_checker->shouldReceive('areRequiredFieldsOfTeamTrackersLimitedToTheSynchronizedFields')
            ->andReturnTrue();
        $this->workflow_checker->shouldReceive('areWorkflowsNotUsedWithSynchronizedFieldsInTeamTrackers')
            ->andReturnFalse();

        $this->assertFalse($this->checker->canProgramIncrementBeCreated($program_milestone, $user));
    }

    private function mockTeamMilestoneTrackers(Project $project, bool $user_can_submit_artifact = true): void
    {
        $first_team_project  = new \Project(
            ['group_id' => '104', 'unix_group_name' => 'proj02', 'group_name' => 'Project 02']
        );
        $second_team_project = new \Project(
            ['group_id' => '198', 'unix_group_name' => 'proj03', 'group_name' => 'Project 03']
        );

        $this->program_dao->shouldReceive('getTeamProjectIdsForGivenProgramProject')
            ->andReturn([['team_project_id' => $project->getID()]]);
        $this->project_manager->shouldReceive('getProject')
            ->with($project->getID())
            ->once()
            ->andReturn($project);

        $first_milestone_tracker = Mockery::mock(\Tracker::class);
        $first_milestone_tracker->shouldReceive('userCanSubmitArtifact')->andReturn($user_can_submit_artifact);
        $first_milestone_tracker->shouldReceive('getGroupId')->andReturn($first_team_project->getID());
        $first_milestone_tracker->shouldReceive('getId')->andReturn(1);
        $first_milestone_tracker->shouldReceive('getProject')->andReturn($first_team_project);
        $second_milestone_tracker = Mockery::mock(\Tracker::class);
        $second_milestone_tracker->shouldReceive('userCanSubmitArtifact')->andReturn($user_can_submit_artifact);
        $second_milestone_tracker->shouldReceive('getGroupId')->andReturn($second_team_project->getID());
        $second_milestone_tracker->shouldReceive('getId')->andReturn(2);
        $second_milestone_tracker->shouldReceive('getProject')->andReturn($second_team_project);
        $planning = new \Planning(1, 'Release', $this->project->getID(), '', '');
        $planning->setBacklogTrackers([$first_milestone_tracker, $second_milestone_tracker]);
        $planning->setPlanningTracker($first_milestone_tracker);

        $this->planning_factory->shouldReceive('getRootPlanning')->andReturn($planning);
    }

    private function getPlanningData(): PlanningData
    {
        $tracker = TrackerTestBuilder::aTracker()->withId(1)->withProject($this->project)->build();

        return new PlanningData(TrackerDataAdapter::build($tracker), 1, 'Release Planning', [], $this->project_data);
    }

    private function buildSynchronizedFields(bool $submitable): void
    {
        $title_field = Mockery::mock(\Tracker_FormElement_Field_Text::class);
        $this->mockField($title_field, 1, true, true);
        $title_field_data = new FieldData($title_field);

        $artifact_link = Mockery::mock(Tracker_FormElement_Field_ArtifactLink::class);
        $this->mockField($artifact_link, 1, $submitable, true);
        $artifact_link_field_data = new FieldData($artifact_link);

        $description_field = Mockery::mock(Tracker_FormElement_Field_Text::class);
        $this->mockField($description_field, 2, true, true);
        $description_field_data = new FieldData($description_field);

        $status_field        = Mockery::mock(Tracker_FormElement_Field_Selectbox::class);
        $this->mockField($status_field, 3, true, true);
        $status_field_data = new FieldData($status_field);

        $field_start_date      = Mockery::mock(Tracker_FormElement_Field_Date::class);
        $this->mockField($field_start_date, 4, true, true);
        $start_date_field_data = new FieldData($field_start_date);

        $field_end_date          = Mockery::mock(Tracker_FormElement_Field_Date::class);
        $this->mockField($field_end_date, 5, true, true);
        $end_date_field_data = new FieldData($field_end_date);

        $synchronized_fields = new SynchronizedFields(
            $artifact_link_field_data,
            $title_field_data,
            $description_field_data,
            $status_field_data,
            $start_date_field_data,
            $end_date_field_data
        );
        $this->fields_adapter->shouldReceive('build')->andReturn($synchronized_fields);
    }

    private function mockField(MockInterface $field, int $id, bool $submitable, bool $updatable): void
    {
        $field->shouldReceive('getId')->andReturn((string) $id);
        $field->shouldReceive('userCanSubmit')->andReturn($submitable);
        $field->shouldReceive('userCanUpdate')->andReturn($updatable);
    }
}
