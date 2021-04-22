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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Project;
use Psr\Log\LoggerInterface;
use Tracker_Artifact_Changeset;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\ReplicationDataAdapter;
use Tuleap\ProgramManagement\Adapter\Program\Feature\UserStoriesInMirroredMilestonesPlanner;
use Tuleap\ProgramManagement\Adapter\Program\PlanningAdapter;
use Tuleap\ProgramManagement\Adapter\Program\ProgramDao;
use Tuleap\ProgramManagement\Adapter\ProjectAdapter;
use Tuleap\ProgramManagement\Program\Backlog\AsynchronousCreation\PendingArtifactCreationStore;
use Tuleap\ProgramManagement\Program\Backlog\AsynchronousCreation\ProgramIncrementsCreator;
use Tuleap\ProgramManagement\Program\Backlog\Plan\BuildPlanProgramIncrementConfiguration;
use Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ArtifactLinkValue;
use Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\Source\Changeset\Values\BuildFieldValues;
use Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\Source\Changeset\Values\DescriptionValue;
use Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\Source\Changeset\Values\EndPeriodValue;
use Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\Source\Changeset\Values\SourceChangesetValuesCollection;
use Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\Source\Changeset\Values\StartDateValue;
use Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\Source\Changeset\Values\StatusValue;
use Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\Source\Changeset\Values\TitleValue;
use Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\Source\Fields\FieldRetrievalException;
use Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\Source\ReplicationData;
use Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\Source\SubmissionDate;
use Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollectionBuilder;
use Tuleap\ProgramManagement\Program\Backlog\TrackerCollectionFactory;
use Tuleap\ProgramManagement\Program\ProgramStore;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class CreateProgramIncrementsTaskTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|UserStoriesInMirroredMilestonesPlanner
     */
    private $user_stories_planner;

    /**
     * @var Project
     */
    private $project;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\PlanningFactory
     */
    private $planning_factory;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|BuildFieldValues
     */
    private $changeset_values_adapter;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\ProjectManager
     */
    private $project_manager;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ProgramDao
     */
    private $program_store;

    /**
     * @var CreateProgramIncrementsTask
     */
    private $task;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PendingArtifactCreationStore
     */
    private $pending_artifact_creation_store;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|LoggerInterface
     */
    private $logger;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ProgramIncrementsCreator
     */
    private $mirror_creator;

    protected function setUp(): void
    {
        $this->changeset_values_adapter        = \Mockery::mock(BuildFieldValues::class);
        $this->program_store                   = \Mockery::mock(ProgramStore::class);
        $this->project_manager                 = Mockery::mock(\ProjectManager::class);
        $project_data_adapter                  = new ProjectAdapter($this->project_manager);
        $projects_collection_builder           = new TeamProjectsCollectionBuilder(
            $this->program_store,
            $project_data_adapter
        );
        $this->planning_factory                = Mockery::mock(\PlanningFactory::class);
        $milestone_trackers_factory            = new TrackerCollectionFactory(
            new PlanningAdapter($this->planning_factory),
            Mockery::mock(BuildPlanProgramIncrementConfiguration::class)
        );
        $this->mirror_creator                  = \Mockery::mock(ProgramIncrementsCreator::class);
        $this->logger                          = \Mockery::mock(LoggerInterface::class);
        $this->pending_artifact_creation_store = \Mockery::mock(PendingArtifactCreationStore::class);
        $this->user_stories_planner            = Mockery::mock(UserStoriesInMirroredMilestonesPlanner::class);

        $this->task = new CreateProgramIncrementsTask(
            $this->changeset_values_adapter,
            $projects_collection_builder,
            $milestone_trackers_factory,
            $this->mirror_creator,
            $this->logger,
            $this->pending_artifact_creation_store,
            $this->user_stories_planner
        );

        $this->project = new Project(['group_id' => 101, 'unix_group_name' => 'test', 'group_name' => 'My project']);
    }

    public function testItCreateMirrors(): void
    {
        $replication_data = $this->getReplicationData();

        $copied_values = $this->buildCopiedValues();
        $this->changeset_values_adapter->shouldReceive('buildCollection')->andReturn($copied_values);
        $this->program_store->shouldReceive('getTeamProjectIdsForGivenProgramProject')
            ->andReturn([['team_project_id' => $this->project->getID()]]);
        $this->project_manager->shouldReceive('getProject')->andReturn($this->project);

        $planning = new \Planning(1, "Root planning", $this->project->getID(), '', '');
        $planning->setPlanningTracker($replication_data->getTracker()->getFullTracker());
        $this->planning_factory->shouldReceive('getRootPlanning')->once()->andReturn($planning);

        $this->mirror_creator->shouldReceive('createProgramIncrements')->once();

        $this->pending_artifact_creation_store->shouldReceive('deleteArtifactFromPendingCreation')
            ->once()
            ->withArgs(
                [(int) $replication_data->getArtifact()->getId(), (int) $replication_data->getUser()->getId()]
            );

        $this->user_stories_planner->shouldReceive('plan')->once();

        $this->task->createProgramIncrements($replication_data);
    }

    public function testItLogsWhenAnExceptionOccurrs(): void
    {
        $replication_data = $this->getReplicationData();

        $this->changeset_values_adapter->shouldReceive('buildCollection')
            ->andThrow(new FieldRetrievalException(1, 'title'));

        $this->logger->shouldReceive('error')->once();

        $this->user_stories_planner->shouldReceive('plan')->never();

        $this->task->createProgramIncrements($replication_data);
    }

    private function buildCopiedValues(): SourceChangesetValuesCollection
    {
        $planned_value = new \Tracker_FormElement_Field_List_Bind_StaticValue(2000, 'Planned', 'Irrelevant', 1, false);

        $title_value         = new TitleValue('Program Release');
        $description_value   = new DescriptionValue('Description', 'text');
        $status_value        = new StatusValue([$planned_value]);
        $start_date_value    = new StartDateValue("2020-10-01");
        $end_period_value    = new EndPeriodValue("2020-10-30");
        $artifact_link_value = new ArtifactLinkValue(112);
        $submission_date     = new SubmissionDate(123456789);

        return new SourceChangesetValuesCollection(
            112,
            $title_value,
            $description_value,
            $status_value,
            $submission_date,
            $start_date_value,
            $end_period_value,
            $artifact_link_value
        );
    }

    private function getReplicationData(): ReplicationData
    {
        $user     = UserTestBuilder::aUser()->withId(1001)->build();
        $tracker  = TrackerTestBuilder::aTracker()->withId(89)->withProject($this->project)->build();
        $artifact = new Artifact(101, $tracker->getId(), $user->getId(), 12345678, false);
        $artifact->setTracker($tracker);
        $changeset = new Tracker_Artifact_Changeset(1, $artifact, $user->getId(), 12345678, "user@email.com");

        return ReplicationDataAdapter::build($artifact, $user, $changeset);
    }
}
