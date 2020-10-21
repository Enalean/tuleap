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

namespace Tuleap\ScaledAgile\Program\Administration\Administration\ProgramIncremant\Mirroring\Asynchronous;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Project;
use Psr\Log\LoggerInterface;
use Tuleap\ScaledAgile\Program\Backlog\AsynchronousCreation\CreateProgramIncrementsTask;
use Tuleap\ScaledAgile\Program\Backlog\AsynchronousCreation\PendingArtifactCreationDao;
use Tuleap\ScaledAgile\Program\Backlog\AsynchronousCreation\ProjectIncrementsCreator;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Data\SynchronizedFields\CopiedValues;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Data\SynchronizedFields\CopiedValuesGatherer;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Project\TeamProjectsCollection;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Project\TeamProjectsCollectionBuilder;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Tracker\ProjectIncrementsTrackerCollection;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Tracker\ProjectIncrementTrackerRetrievalException;
use Tuleap\ScaledAgile\Program\Backlog\TrackerCollectionFactory;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\TrackerColor;

final class CreateProgramIncrementsTaskTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var CreateProgramIncrementsTask
     */
    private $task;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PendingArtifactCreationDao
     */
    private $pending_artifact_creation_dao;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|LoggerInterface
     */
    private $logger;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ProjectIncrementsCreator
     */
    private $mirror_creator;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|TrackerCollectionFactory
     */
    private $milestone_trackers_factory;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|TeamProjectsCollectionBuilder
     */
    private $projects_collection_builder;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|CopiedValuesGatherer
     */
    private $copied_values_gatherer;

    protected function setUp(): void
    {
        $this->copied_values_gatherer        = \Mockery::mock(CopiedValuesGatherer::class);
        $this->projects_collection_builder   = \Mockery::mock(TeamProjectsCollectionBuilder::class);
        $this->milestone_trackers_factory    = \Mockery::mock(TrackerCollectionFactory::class);
        $this->mirror_creator                = \Mockery::mock(ProjectIncrementsCreator::class);
        $this->logger                        = \Mockery::mock(LoggerInterface::class);
        $this->pending_artifact_creation_dao = \Mockery::mock(PendingArtifactCreationDao::class);

        $this->task = new CreateProgramIncrementsTask(
            $this->copied_values_gatherer,
            $this->projects_collection_builder,
            $this->milestone_trackers_factory,
            $this->mirror_creator,
            $this->logger,
            $this->pending_artifact_creation_dao
        );
    }

    public function testItCreateMirrors(): void
    {
        $project = \Mockery::mock(Project::class);
        $project->shouldReceive('getId')->andReturn(101);
        $tracker = $this->buildTestTracker(89);
        $tracker->setProject($project);
        $artifact = \Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact->shouldReceive('getTracker')->once()->andReturn($tracker);
        $artifact->shouldReceive('getId')->andReturn(101);
        $changeset = \Mockery::mock(\Tracker_Artifact_Changeset::class);

        $user = UserTestBuilder::aUser()->withId(1001)->build();

        $team_projects = new TeamProjectsCollection([\Mockery::mock(Project::class)]);

        $copied_values = $this->buildCopiedValues();
        $this->copied_values_gatherer->shouldReceive('gather')->andReturn($copied_values);
        $this->projects_collection_builder->shouldReceive('getTeamProjectForAGivenProgramProject')
            ->once()
            ->andReturn($team_projects);

        $milestone_trackers = [\Mockery::mock(\Tracker::class)];
        $team_milestones = new ProjectIncrementsTrackerCollection($milestone_trackers);
        $this->milestone_trackers_factory->shouldReceive('buildFromTeamProjects')
            ->once()
            ->withArgs([$team_projects, $user])
            ->andReturn($team_milestones);


        $this->mirror_creator->shouldReceive('createProjectIncrements')
            ->once()
            ->withArgs([$copied_values, $team_milestones, $user]);

        $this->pending_artifact_creation_dao->shouldReceive('deleteArtifactFromPendingCreation')
            ->once()
            ->withArgs([(int) $artifact->getId(), (int) $user->getId()]);

        $this->task->createProjectIncrements($artifact, $user, $changeset);
    }

    public function testItLogsWhenAnExceptionOccurrs(): void
    {
        $project = \Mockery::mock(Project::class);
        $project->shouldReceive('getId')->andReturn(101);
        $tracker = $this->buildTestTracker(89);
        $tracker->setProject($project);
        $artifact = \Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact->shouldReceive('getTracker')->once()->andReturn($tracker);
        $changeset = \Mockery::mock(\Tracker_Artifact_Changeset::class);

        $user = UserTestBuilder::aUser()->withId(1001)->build();

        $copied_values = $this->buildCopiedValues();
        $this->copied_values_gatherer->shouldReceive('gather')->andReturn($copied_values);
        $this->projects_collection_builder->shouldReceive('getTeamProjectForAGivenProgramProject')
            ->once()
            ->andThrow(new class extends \RuntimeException implements ProjectIncrementTrackerRetrievalException {
            });

        $this->logger->shouldReceive('error')->once();

        $this->task->createProjectIncrements($artifact, $user, $changeset);
    }

    private function buildCopiedValues(): CopiedValues
    {
        $tracker = $this->buildTestTracker(89);
        $title_changeset_value = new \Tracker_Artifact_ChangesetValue_String(
            10000,
            \Mockery::mock(\Tracker_Artifact_Changeset::class),
            \Mockery::mock(\Tracker_FormElement_Field::class),
            true,
            'Program Release',
            'text'
        );

        $description_field = \Mockery::mock(\Tracker_FormElement_Field::class);
        $description_field->shouldReceive('getTracker')->andReturn($tracker);
        $description_changeset_value = new \Tracker_Artifact_ChangesetValue_Text(10001, \Mockery::mock(\Tracker_Artifact_Changeset::class), $description_field, true, 'Description', 'text');

        $planned_value          = new \Tracker_FormElement_Field_List_Bind_StaticValue(2000, 'Planned', 'Irrelevant', 1, false);
        $status_changeset_value = new \Tracker_Artifact_ChangesetValue_List(
            10002,
            \Mockery::mock(\Tracker_Artifact_Changeset::class),
            \Mockery::mock(\Tracker_FormElement_Field::class),
            true,
            [$planned_value]
        );

        $start_date_changeset_value = new \Tracker_Artifact_ChangesetValue_Date(
            100003,
            \Mockery::mock(\Tracker_Artifact_Changeset::class),
            \Mockery::mock(\Tracker_FormElement_Field::class),
            true,
            1285891200
        );

        $end_period_changeset_value = new \Tracker_Artifact_ChangesetValue_Date(
            100004,
            \Mockery::mock(\Tracker_Artifact_Changeset::class),
            \Mockery::mock(\Tracker_FormElement_Field_Date::class),
            true,
            1602288000
        );

        return new CopiedValues(
            $title_changeset_value,
            $description_changeset_value,
            $status_changeset_value,
            123456789,
            112,
            $start_date_changeset_value,
            $end_period_changeset_value
        );
    }

    private function buildTestTracker(int $tracker_id): \Tracker
    {
        return new \Tracker(
            $tracker_id,
            null,
            'Irrelevant',
            'Irrelevant',
            'irrelevant',
            false,
            null,
            null,
            null,
            null,
            true,
            false,
            \Tracker::NOTIFICATIONS_LEVEL_DEFAULT,
            TrackerColor::default(),
            false
        );
    }
}
