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
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Project\TeamProjectsCollection;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Project\TeamProjectsCollectionBuilder;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Changeset\Values\ArtifactLinkValueData;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Changeset\Values\DescriptionValueData;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Changeset\Values\EndPeriodValueData;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Changeset\Values\SourceChangesetValuesCollection;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Changeset\Values\SourceChangesetValuesCollectionAdapter;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Changeset\Values\StartDateValueData;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Changeset\Values\StatusValueData;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Changeset\Values\TitleValueData;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Tracker\ProjectIncrementsTrackerCollection;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Tracker\ProjectIncrementTrackerRetrievalException;
use Tuleap\ScaledAgile\Program\Backlog\TrackerCollectionFactory;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

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
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|SourceChangesetValuesCollectionAdapter
     */
    private $changeset_values_adapter;

    protected function setUp(): void
    {
        $this->changeset_values_adapter      = \Mockery::mock(SourceChangesetValuesCollectionAdapter::class);
        $this->projects_collection_builder   = \Mockery::mock(TeamProjectsCollectionBuilder::class);
        $this->milestone_trackers_factory    = \Mockery::mock(TrackerCollectionFactory::class);
        $this->mirror_creator                = \Mockery::mock(ProjectIncrementsCreator::class);
        $this->logger                        = \Mockery::mock(LoggerInterface::class);
        $this->pending_artifact_creation_dao = \Mockery::mock(PendingArtifactCreationDao::class);

        $this->task = new CreateProgramIncrementsTask(
            $this->changeset_values_adapter,
            $this->projects_collection_builder,
            $this->milestone_trackers_factory,
            $this->mirror_creator,
            $this->logger,
            $this->pending_artifact_creation_dao
        );
    }

    public function testItCreateMirrors(): void
    {
        $tracker = TrackerTestBuilder::aTracker()->withId(89)->withProject(Project::buildForTest())->build();
        $artifact = \Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact->shouldReceive('getTracker')->once()->andReturn($tracker);
        $artifact->shouldReceive('getId')->andReturn(101);
        $changeset = \Mockery::mock(\Tracker_Artifact_Changeset::class);

        $user = UserTestBuilder::aUser()->withId(1001)->build();

        $team_projects = new TeamProjectsCollection([Project::buildForTest()]);

        $copied_values = $this->buildCopiedValues();
        $this->changeset_values_adapter->shouldReceive('buildCollection')->andReturn($copied_values);
        $this->projects_collection_builder->shouldReceive('getTeamProjectForAGivenProgramProject')
            ->once()
            ->andReturn($team_projects);

        $milestone_trackers = [TrackerTestBuilder::aTracker()->withId(102)->build()];
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
        $tracker = TrackerTestBuilder::aTracker()->withId(89)->withProject(Project::buildForTest())->build();
        $artifact = \Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact->shouldReceive('getTracker')->once()->andReturn($tracker);
        $changeset = \Mockery::mock(\Tracker_Artifact_Changeset::class);

        $user = UserTestBuilder::aUser()->withId(1001)->build();

        $copied_values = $this->buildCopiedValues();
        $this->changeset_values_adapter->shouldReceive('buildCollection')->andReturn($copied_values);
        $this->projects_collection_builder->shouldReceive('getTeamProjectForAGivenProgramProject')
            ->once()
            ->andThrow(
                new class extends \RuntimeException implements ProjectIncrementTrackerRetrievalException {
                }
            );

        $this->logger->shouldReceive('error')->once();

        $this->task->createProjectIncrements($artifact, $user, $changeset);
    }

    private function buildCopiedValues(): SourceChangesetValuesCollection
    {
        $planned_value = new \Tracker_FormElement_Field_List_Bind_StaticValue(2000, 'Planned', 'Irrelevant', 1, false);

        $title_value         = new TitleValueData('Program Release');
        $description_value   = new DescriptionValueData('Description', 'text');
        $status_value        = new StatusValueData([$planned_value]);
        $start_date_value    = new StartDateValueData("2020-10-01");
        $end_period_value    = new EndPeriodValueData("2020-10-30");
        $artifact_link_value = new ArtifactLinkValueData(112);

        return new SourceChangesetValuesCollection(
            112,
            $title_value,
            $description_value,
            $status_value,
            123456789,
            $start_date_value,
            $end_period_value,
            $artifact_link_value
        );
    }
}
