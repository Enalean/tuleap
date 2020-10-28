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
use Psr\Log\NullLogger;
use Tracker_FormElement_Field_ArtifactLink;
use Tracker_FormElement_Field_Date;
use Tracker_FormElement_Field_Selectbox;
use Tracker_FormElement_Field_Text;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Project\TeamProjectsCollection;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Project\TeamProjectsCollectionBuilder;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Fields\FieldData;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Fields\FieldRetrievalException;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Fields\SynchronizedFieldDataFromProgramAndTeamTrackers;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Fields\SynchronizedFieldDataFromProgramAndTeamTrackersCollection;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Fields\SynchronizedFieldDataFromProgramAndTeamTrackersCollectionBuilder;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Fields\SynchronizedFieldsData;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\SourceTrackerCollection;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Tracker\ProjectIncrementsTrackerCollection;
use Tuleap\ScaledAgile\Program\Backlog\TrackerCollectionFactory;
use Tuleap\ScaledAgile\Program\PlanningConfiguration\PlanningData;
use Tuleap\ScaledAgile\Program\PlanningConfiguration\TopPlanningNotFoundInProjectException;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class ProjectIncrementArtifactCreatorCheckerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var ProjectIncrementArtifactCreatorChecker
     */
    private $checker;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TeamProjectsCollectionBuilder
     */
    private $projects_builder;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TrackerCollectionFactory
     */
    private $trackers_builder;
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

        $this->projects_builder         = Mockery::mock(TeamProjectsCollectionBuilder::class);
        $this->trackers_builder         = Mockery::mock(TrackerCollectionFactory::class);
        $this->field_collection_builder = Mockery::mock(SynchronizedFieldDataFromProgramAndTeamTrackersCollectionBuilder::class);
        $this->semantic_checker         = Mockery::mock(SemanticChecker::class);
        $this->required_field_checker   = Mockery::mock(RequiredFieldChecker::class);
        $this->workflow_checker         = Mockery::mock(WorkflowChecker::class);

        $this->checker = new ProjectIncrementArtifactCreatorChecker(
            $this->projects_builder,
            $this->trackers_builder,
            $this->field_collection_builder,
            $this->semantic_checker,
            $this->required_field_checker,
            $this->workflow_checker,
            new NullLogger()
        );

        $this->project = new Project([
            'group_id'   => '101',
            'unix_group_name' => 'proj01'
        ]);
    }

    public function testItReturnsTrueIfAllChecksAreOk(): void
    {
        $user                 = UserTestBuilder::aUser()->build();
        $program_milestone    = $this->getPlanningData();

        $this->mockTeamMilestoneTrackers($this->project);
        $this->semantic_checker->shouldReceive('areTrackerSemanticsWellConfigured')
            ->once()
            ->andReturnTrue();

        $collection = new SynchronizedFieldDataFromProgramAndTeamTrackersCollection();
        $collection->add($this->buildSynchronizedFieldDataFromProgramAndTeamTrackers(true));
        $this->field_collection_builder->shouldReceive('buildFromSourceTrackers')
            ->once()
            ->andReturn($collection);
        $this->required_field_checker->shouldReceive('areRequiredFieldsOfTeamTrackersLimitedToTheSynchronizedFields')
            ->andReturnTrue();
        $this->workflow_checker->shouldReceive('areWorkflowsNotUsedWithSynchronizedFieldsInTeamTrackers')
            ->andReturnTrue();

        $this->assertTrue($this->checker->canProjectIncrementBeCreated($program_milestone, $user));
    }

    public function testItReturnsTrueWhenAProjectHasNoTeamProjects(): void
    {
        $user                 = UserTestBuilder::aUser()->build();
        $program_milestone    = $this->getPlanningData();

        $this->projects_builder->shouldReceive('getTeamProjectForAGivenProgramProject')
            ->andReturn(new TeamProjectsCollection([]));

        $this->assertTrue($this->checker->canProjectIncrementBeCreated($program_milestone, $user));
    }

    public function testItReturnsFalseIfOneProjectDoesNotHaveARootPlanningWithAMilestoneTracker(): void
    {
        $user                 = UserTestBuilder::aUser()->build();
        $program_milestone    = $this->getPlanningData();

        $first_team_project  = new \Project(['group_id' => '104']);
        $second_team_project = new \Project(['group_id' => '198']);
        $this->projects_builder->shouldReceive('getTeamProjectForAGivenProgramProject')
            ->once()
            ->with($this->project)
            ->andReturn(new TeamProjectsCollection([$first_team_project, $second_team_project]));
        $this->trackers_builder->shouldReceive('buildFromProgramProjectAndItsTeam')
            ->once()
            ->andThrow(new TopPlanningNotFoundInProjectException(198));

        $this->assertFalse($this->checker->canProjectIncrementBeCreated($program_milestone, $user));
    }

    public function testItReturnsFalseIfSemanticsAreNotWellConfigured(): void
    {
        $user                 = UserTestBuilder::aUser()->build();
        $program_milestone    = $this->getPlanningData();

        $this->mockTeamMilestoneTrackers($this->project);
        $this->semantic_checker->shouldReceive('areTrackerSemanticsWellConfigured')
            ->andReturnFalse();

        $this->assertFalse($this->checker->canProjectIncrementBeCreated($program_milestone, $user));
    }

    public function testItReturnsFalseIfUserCannotSubmitArtifact(): void
    {
        $user                 = UserTestBuilder::aUser()->build();
        $program_milestone    = $this->getPlanningData();

        $this->mockTeamMilestoneTrackers($this->project, false);
        $this->semantic_checker->shouldReceive('areTrackerSemanticsWellConfigured')
            ->andReturnTrue();

        $this->assertFalse($this->checker->canProjectIncrementBeCreated($program_milestone, $user));
    }

    public function testItReturnsFalseIfFieldsCantBeExtractedFromMilestoneTrackers(): void
    {
        $user                 = UserTestBuilder::aUser()->build();
        $program_milestone    = $this->getPlanningData();

        $this->mockTeamMilestoneTrackers($this->project);
        $this->semantic_checker->shouldReceive('areTrackerSemanticsWellConfigured')
            ->andReturnTrue();
        $this->field_collection_builder->shouldReceive('buildFromSourceTrackers')
            ->andThrow(new FieldRetrievalException(1, 'title'));

        $this->assertFalse($this->checker->canProjectIncrementBeCreated($program_milestone, $user));
    }

    public function testItReturnsFalseIfUserCantSubmitOneArtifactLink(): void
    {
        $user                 = UserTestBuilder::aUser()->build();
        $program_milestone    = $this->getPlanningData();

        $this->mockTeamMilestoneTrackers($this->project);
        $this->semantic_checker->shouldReceive('areTrackerSemanticsWellConfigured')
            ->andReturnTrue();

        $collection = new SynchronizedFieldDataFromProgramAndTeamTrackersCollection();
        $collection->add($this->buildSynchronizedFieldDataFromProgramAndTeamTrackers(false));

        $this->field_collection_builder->shouldReceive('buildFromSourceTrackers')
            ->once()
            ->andReturn($collection);

        $this->assertFalse($this->checker->canProjectIncrementBeCreated($program_milestone, $user));
    }

    public function testItReturnsFalseIfTrackersHaveRequiredFieldsThatCannotBeSynchronized(): void
    {
        $user                 = UserTestBuilder::aUser()->build();
        $program_milestone    = $this->getPlanningData();

        $this->mockTeamMilestoneTrackers($this->project);
        $this->semantic_checker->shouldReceive('areTrackerSemanticsWellConfigured')
            ->once()
            ->andReturnTrue();

        $collection = new SynchronizedFieldDataFromProgramAndTeamTrackersCollection();
        $collection->add($this->buildSynchronizedFieldDataFromProgramAndTeamTrackers(true));

        $this->field_collection_builder->shouldReceive('buildFromSourceTrackers')
            ->once()
            ->andReturn($collection);

        $this->required_field_checker->shouldReceive('areRequiredFieldsOfTeamTrackersLimitedToTheSynchronizedFields')
            ->andReturnFalse();

        $this->assertFalse($this->checker->canProjectIncrementBeCreated($program_milestone, $user));
    }

    public function testItReturnsFalseIfTeamTrackersAreUsingSynchronizedFieldsInWorkflowRules(): void
    {
        $user                 = UserTestBuilder::aUser()->build();
        $program_milestone    = $this->getPlanningData();

        $this->mockTeamMilestoneTrackers($this->project);
        $this->semantic_checker->shouldReceive('areTrackerSemanticsWellConfigured')
            ->once()
            ->andReturnTrue();

        $collection = new SynchronizedFieldDataFromProgramAndTeamTrackersCollection();
        $collection->add($this->buildSynchronizedFieldDataFromProgramAndTeamTrackers(true));

        $this->field_collection_builder->shouldReceive('buildFromSourceTrackers')
            ->once()
            ->andReturn(new SynchronizedFieldDataFromProgramAndTeamTrackersCollection());
        $this->required_field_checker->shouldReceive('areRequiredFieldsOfTeamTrackersLimitedToTheSynchronizedFields')
            ->andReturnTrue();
        $this->workflow_checker->shouldReceive('areWorkflowsNotUsedWithSynchronizedFieldsInTeamTrackers')
            ->andReturnFalse();

        $this->assertFalse($this->checker->canProjectIncrementBeCreated($program_milestone, $user));
    }

    private function mockTeamMilestoneTrackers(Project $project, bool $user_can_submit_artifact = true): void
    {
        $first_team_project  = new \Project(['group_id' => '104']);
        $second_team_project = new \Project(['group_id' => '198']);
        $this->projects_builder->shouldReceive('getTeamProjectForAGivenProgramProject')
            ->once()
            ->with($project)
            ->andReturn(new TeamProjectsCollection([$first_team_project, $second_team_project]));
        $first_milestone_tracker = Mockery::mock(\Tracker::class);
        $first_milestone_tracker->shouldReceive('userCanSubmitArtifact')->andReturn($user_can_submit_artifact);
        $first_milestone_tracker->shouldReceive('getGroupId')->andReturn($first_team_project->getID());
        $second_milestone_tracker = Mockery::mock(\Tracker::class);
        $second_milestone_tracker->shouldReceive('userCanSubmitArtifact')->andReturn($user_can_submit_artifact);
        $second_milestone_tracker->shouldReceive('getGroupId')->andReturn($second_team_project->getID());
        $this->trackers_builder->shouldReceive('buildFromProgramProjectAndItsTeam')
            ->once()
            ->andReturn(new SourceTrackerCollection([$first_milestone_tracker, $second_milestone_tracker]));
        $this->trackers_builder->shouldReceive('buildFromTeamProjects')
            ->once()
            ->andReturn(new ProjectIncrementsTrackerCollection([$first_milestone_tracker, $second_milestone_tracker]));
    }

    private function getPlanningData(): PlanningData
    {
        $tracker = TrackerTestBuilder::aTracker()->withId(1)->withProject($this->project)->build();
        return new PlanningData($tracker, 1, 'Release Planning', []);
    }

    private function buildSynchronizedFieldDataFromProgramAndTeamTrackers(bool $submitable): SynchronizedFieldDataFromProgramAndTeamTrackers
    {
        $title_field = Mockery::mock(\Tracker_FormElement_Field_Text::class);
        $this->mockField($title_field, 1, true, true);
        $title_field_data = new FieldData($title_field);

        $artifact_link      = Mockery::mock(Tracker_FormElement_Field_ArtifactLink::class);
        $this->mockField($artifact_link, 1, $submitable, true);
        $artifact_link_field_data = new FieldData($artifact_link);

        $description_field    = Mockery::mock(Tracker_FormElement_Field_Text::class);
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

        $synchronized_field_data = new SynchronizedFieldsData(
            $artifact_link_field_data,
            $title_field_data,
            $description_field_data,
            $status_field_data,
            $start_date_field_data,
            $end_date_field_data
        );

        return new SynchronizedFieldDataFromProgramAndTeamTrackers($synchronized_field_data);
    }

    private function mockField(MockInterface $field, int $id, bool $submitable, bool $updatable): void
    {
        $field->shouldReceive('getId')->andReturn((string) $id);
        $field->shouldReceive('userCanSubmit')->andReturn($submitable);
        $field->shouldReceive('userCanUpdate')->andReturn($updatable);
    }
}
