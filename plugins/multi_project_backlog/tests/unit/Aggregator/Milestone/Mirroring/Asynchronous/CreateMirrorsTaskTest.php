<?php
/*
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

namespace Tuleap\MultiProjectBacklog\Aggregator\Milestone\Mirroring\Asynchronous;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Project;
use Psr\Log\LoggerInterface;
use Tuleap\MultiProjectBacklog\Aggregator\ContributorProjectsCollection;
use Tuleap\MultiProjectBacklog\Aggregator\ContributorProjectsCollectionBuilder;
use Tuleap\MultiProjectBacklog\Aggregator\Milestone\ContributorMilestoneTrackerCollection;
use Tuleap\MultiProjectBacklog\Aggregator\Milestone\MilestoneTrackerCollectionFactory;
use Tuleap\MultiProjectBacklog\Aggregator\Milestone\MilestoneTrackerRetrievalException;
use Tuleap\MultiProjectBacklog\Aggregator\Milestone\Mirroring\CopiedValues;
use Tuleap\MultiProjectBacklog\Aggregator\Milestone\Mirroring\CopiedValuesGatherer;
use Tuleap\MultiProjectBacklog\Aggregator\Milestone\Mirroring\MirrorMilestonesCreator;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\TrackerColor;

final class CreateMirrorsTaskTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var CreateMirrorsTask
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
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|MirrorMilestonesCreator
     */
    private $mirror_creator;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|MilestoneTrackerCollectionFactory
     */
    private $milestone_trackers_factory;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ContributorProjectsCollectionBuilder
     */
    private $projects_collection_builder;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|CopiedValuesGatherer
     */
    private $copied_values_gatherer;

    protected function setUp(): void
    {
        $this->copied_values_gatherer        = \Mockery::mock(CopiedValuesGatherer::class);
        $this->projects_collection_builder   = \Mockery::mock(ContributorProjectsCollectionBuilder::class);
        $this->milestone_trackers_factory    = \Mockery::mock(MilestoneTrackerCollectionFactory::class);
        $this->mirror_creator                = \Mockery::mock(MirrorMilestonesCreator::class);
        $this->logger                        = \Mockery::mock(LoggerInterface::class);
        $this->pending_artifact_creation_dao = \Mockery::mock(PendingArtifactCreationDao::class);

        $this->task = new CreateMirrorsTask(
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
        $artifact = \Mockery::mock(\Tracker_Artifact::class);
        $artifact->shouldReceive('getTracker')->once()->andReturn($tracker);
        $artifact->shouldReceive('getId')->andReturn(101);
        $changeset = \Mockery::mock(\Tracker_Artifact_Changeset::class);

        $user = UserTestBuilder::aUser()->withId(1001)->build();

        $contributor_projects = new ContributorProjectsCollection([\Mockery::mock(Project::class)]);

        $copied_values = $this->buildCopiedValues();
        $this->copied_values_gatherer->shouldReceive('gather')->andReturn($copied_values);
        $this->projects_collection_builder->shouldReceive('getContributorProjectForAGivenAggregatorProject')
            ->once()
            ->andReturn($contributor_projects);

        $milestone_trackers = [\Mockery::mock(\Tracker::class)];
        $contributor_milestones = new ContributorMilestoneTrackerCollection($milestone_trackers);
        $this->milestone_trackers_factory->shouldReceive('buildFromContributorProjects')
            ->once()
            ->withArgs([$contributor_projects, $user])
            ->andReturn($contributor_milestones);


        $this->mirror_creator->shouldReceive('createMirrors')
            ->once()
            ->withArgs([$copied_values, $contributor_milestones, $user]);

        $this->pending_artifact_creation_dao->shouldReceive('deleteArtifactFromPendingCreation')
            ->once()
            ->withArgs([(int) $artifact->getId(), (int) $user->getId()]);

        $this->task->createMirrors($artifact, $user, $changeset);
    }

    public function testItLogsWhenAnExceptionOccurrs(): void
    {
        $project = \Mockery::mock(Project::class);
        $project->shouldReceive('getId')->andReturn(101);
        $tracker = $this->buildTestTracker(89);
        $tracker->setProject($project);
        $artifact = \Mockery::mock(\Tracker_Artifact::class);
        $artifact->shouldReceive('getTracker')->once()->andReturn($tracker);
        $changeset = \Mockery::mock(\Tracker_Artifact_Changeset::class);

        $user = UserTestBuilder::aUser()->withId(1001)->build();

        $copied_values = $this->buildCopiedValues();
        $this->copied_values_gatherer->shouldReceive('gather')->andReturn($copied_values);
        $this->projects_collection_builder->shouldReceive('getContributorProjectForAGivenAggregatorProject')
            ->once()
            ->andThrow(new class extends \RuntimeException implements MilestoneTrackerRetrievalException {
            });

        $this->logger->shouldReceive('error')->once();

        $this->task->createMirrors($artifact, $user, $changeset);
    }

    private function buildCopiedValues(): CopiedValues
    {
        $tracker = $this->buildTestTracker(89);
        $title_changeset_value = new \Tracker_Artifact_ChangesetValue_String(
            10000,
            \Mockery::mock(\Tracker_Artifact_Changeset::class),
            \Mockery::mock(\Tracker_FormElement_Field::class),
            true,
            'Aggregator Release',
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
