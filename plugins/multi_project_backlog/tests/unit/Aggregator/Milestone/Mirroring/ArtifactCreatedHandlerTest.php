<?php
/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\MultiProjectBacklog\Aggregator\Milestone\Mirroring;

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\MultiProjectBacklog\Aggregator\AggregatorDao;
use Tuleap\MultiProjectBacklog\Aggregator\ContributorProjectsCollection;
use Tuleap\MultiProjectBacklog\Aggregator\ContributorProjectsCollectionBuilder;
use Tuleap\MultiProjectBacklog\Aggregator\Milestone\ContributorMilestoneTrackerCollection;
use Tuleap\MultiProjectBacklog\Aggregator\Milestone\MilestoneTrackerCollectionFactory;
use Tuleap\MultiProjectBacklog\Aggregator\Milestone\MilestoneTrackerRetrievalException;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Event\ArtifactCreated;

final class ArtifactCreatedHandlerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var ArtifactCreatedHandler
     */
    private $handler;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|AggregatorDao
     */
    private $aggregator_dao;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|\UserManager
     */
    private $user_manager;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|\PlanningFactory
     */
    private $planning_factory;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|CopiedValuesGatherer
     */
    private $copied_values_gatherer;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|ContributorProjectsCollectionBuilder
     */
    private $project_collection_builder;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|MilestoneTrackerCollectionFactory
     */
    private $milestone_trackers_factory;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|MirrorMilestonesCreator
     */
    private $mirror_creator;

    protected function setUp(): void
    {
        $this->aggregator_dao             = M::mock(AggregatorDao::class);
        $this->user_manager               = M::mock(\UserManager::class);
        $this->planning_factory           = M::mock(\PlanningFactory::class);
        $this->copied_values_gatherer     = M::mock(CopiedValuesGatherer::class);
        $this->project_collection_builder = M::mock(ContributorProjectsCollectionBuilder::class);
        $this->milestone_trackers_factory = M::mock(MilestoneTrackerCollectionFactory::class);
        $this->mirror_creator             = M::mock(MirrorMilestonesCreator::class);
        $this->handler                    = new ArtifactCreatedHandler(
            $this->aggregator_dao,
            $this->user_manager,
            $this->planning_factory,
            $this->copied_values_gatherer,
            $this->project_collection_builder,
            $this->milestone_trackers_factory,
            $this->mirror_creator,
            new \Psr\Log\NullLogger()
        );
    }

    public function testHandleDelegatesToMirrorCreator(): void
    {
        $project  = \Project::buildForTest();
        $artifact = M::mock(\Tracker_Artifact::class);
        $tracker  = $this->buildTestTracker(15, $project);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);
        $changeset = new \Tracker_Artifact_Changeset(21, $artifact, 36, 1, '');
        $this->aggregator_dao->shouldReceive('isProjectAnAggregatorProject')->andReturnTrue();
        $current_user = UserTestBuilder::aUser()->build();
        $this->user_manager->shouldReceive('getCurrentUser')->andReturn($current_user);
        $planning = new \Planning(7, 'Irrelevant', 101, 'Irrelevant', 'Irrelevant', [], 15);
        $this->planning_factory->shouldReceive('getVirtualTopPlanning')->andReturn($planning);

        $copied_values = $this->buildCopiedValues();
        $this->copied_values_gatherer->shouldReceive('gather')->once()->andReturn($copied_values);
        $this->project_collection_builder->shouldReceive('getContributorProjectForAGivenAggregatorProject')
            ->once()
            ->andReturn(new ContributorProjectsCollection([]));
        $contributor_milestones = new ContributorMilestoneTrackerCollection([]);
        $this->milestone_trackers_factory->shouldReceive('buildFromContributorProjects')
            ->once()
            ->andReturn($contributor_milestones);
        $this->mirror_creator->shouldReceive('createMirrors')
            ->once()
            ->with($copied_values, $contributor_milestones, $current_user);

        $this->handler->handle(new ArtifactCreated($artifact, $changeset));
    }

    public function testHandleReactsOnlyToArtifactsFromAggregatorProjects(): void
    {
        $project  = \Project::buildForTest();
        $artifact = M::mock(\Tracker_Artifact::class);
        $tracker  = $this->buildTestTracker(15, $project);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);
        $changeset = new \Tracker_Artifact_Changeset(21, $artifact, 36, 1, '');
        $this->aggregator_dao->shouldReceive('isProjectAnAggregatorProject')->with(101)->once()->andReturnFalse();

        $this->handler->handle(new ArtifactCreated($artifact, $changeset));

        $this->mirror_creator->shouldNotHaveReceived('createMirrors');
    }

    public function testHandleDoesNotReactWhenNoPlanningException(): void
    {
        $project  = \Project::buildForTest();
        $artifact = M::mock(\Tracker_Artifact::class);
        $tracker  = $this->buildTestTracker(15, $project);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);
        $changeset = new \Tracker_Artifact_Changeset(21, $artifact, 36, 1, '');
        $this->aggregator_dao->shouldReceive('isProjectAnAggregatorProject')->andReturnTrue();
        $this->user_manager->shouldReceive('getCurrentUser')->andReturn(UserTestBuilder::aUser()->build());
        $this->planning_factory->shouldReceive('getVirtualTopPlanning')->andThrow(new \Planning_NoPlanningsException());

        $this->handler->handle(new ArtifactCreated($artifact, $changeset));

        $this->mirror_creator->shouldNotHaveReceived('createMirrors');
    }

    public function testHandleReactsOnlyToTopMilestones(): void
    {
        $project  = \Project::buildForTest();
        $artifact = M::mock(\Tracker_Artifact::class);
        $tracker  = $this->buildTestTracker(404, $project);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);
        $changeset = new \Tracker_Artifact_Changeset(21, $artifact, 36, 1, '');
        $this->aggregator_dao->shouldReceive('isProjectAnAggregatorProject')->andReturnTrue();
        $this->user_manager->shouldReceive('getCurrentUser')->andReturn(UserTestBuilder::aUser()->build());
        $planning = new \Planning(7, 'Irrelevant', 101, 'Irrelevant', 'Irrelevant', [], 15);
        $this->planning_factory->shouldReceive('getVirtualTopPlanning')->andReturn($planning);

        $this->handler->handle(new ArtifactCreated($artifact, $changeset));

        $this->mirror_creator->shouldNotHaveReceived('createMirrors');
    }

    public function testHandleCatchesMilestoneTrackerRetrievalExceptionToAvoidStoppingSourceMilestoneHalfway(): void
    {
        $project  = \Project::buildForTest();
        $artifact = M::mock(\Tracker_Artifact::class);
        $tracker  = $this->buildTestTracker(15, $project);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);
        $changeset = new \Tracker_Artifact_Changeset(21, $artifact, 36, 1, '');
        $this->aggregator_dao->shouldReceive('isProjectAnAggregatorProject')->andReturnTrue();
        $current_user = UserTestBuilder::aUser()->build();
        $this->user_manager->shouldReceive('getCurrentUser')->andReturn($current_user);
        $planning = new \Planning(7, 'Irrelevant', 101, 'Irrelevant', 'Irrelevant', [], 15);
        $this->planning_factory->shouldReceive('getVirtualTopPlanning')->andReturn($planning);

        $copied_values = $this->buildCopiedValues();
        $this->copied_values_gatherer->shouldReceive('gather')->andReturn($copied_values);
        $this->project_collection_builder->shouldReceive('getContributorProjectForAGivenAggregatorProject')
            ->andReturn(new ContributorProjectsCollection([]));
        $this->milestone_trackers_factory->shouldReceive('buildFromContributorProjects')
            ->andThrow(
                new class extends \RuntimeException implements MilestoneTrackerRetrievalException {
                }
            );

        $this->handler->handle(new ArtifactCreated($artifact, $changeset));

        $this->mirror_creator->shouldNotHaveReceived('createMirrors');
    }

    public function testHandleCatchesMirroringExceptionsToAvoidStoppingSourceMilestoneHalfway(): void
    {
        $project  = \Project::buildForTest();
        $artifact = M::mock(\Tracker_Artifact::class);
        $tracker  = $this->buildTestTracker(15, $project);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);
        $changeset = new \Tracker_Artifact_Changeset(21, $artifact, 36, 1, '');
        $this->aggregator_dao->shouldReceive('isProjectAnAggregatorProject')->andReturnTrue();
        $current_user = UserTestBuilder::aUser()->build();
        $this->user_manager->shouldReceive('getCurrentUser')->andReturn($current_user);
        $planning = new \Planning(7, 'Irrelevant', 101, 'Irrelevant', 'Irrelevant', [], 15);
        $this->planning_factory->shouldReceive('getVirtualTopPlanning')->andReturn($planning);

        $copied_values = $this->buildCopiedValues();
        $this->copied_values_gatherer->shouldReceive('gather')->once()->andReturn($copied_values);
        $this->project_collection_builder->shouldReceive('getContributorProjectForAGivenAggregatorProject')
            ->once()
            ->andReturn(new ContributorProjectsCollection([]));
        $contributor_milestones = new ContributorMilestoneTrackerCollection([]);
        $this->milestone_trackers_factory->shouldReceive('buildFromContributorProjects')
            ->once()
            ->andReturn($contributor_milestones);
        $this->mirror_creator->shouldReceive('createMirrors')
            ->andThrow(
                new class extends \RuntimeException implements MilestoneMirroringException {
                }
            );

        $this->handler->handle(new ArtifactCreated($artifact, $changeset));
    }

    private function buildTestTracker(int $tracker_id, \Project $project): \Tracker
    {
        $tracker = new \Tracker(
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
            \Tuleap\Tracker\TrackerColor::default(),
            false
        );
        $tracker->setProject($project);
        return $tracker;
    }

    private function buildCopiedValues(): CopiedValues
    {
        $project = \Project::buildForTest();
        $tracker = $this->buildTestTracker(89, $project);
        $title_changeset_value = new \Tracker_Artifact_ChangesetValue_String(
            10000,
            M::mock(\Tracker_Artifact_Changeset::class),
            M::mock(\Tracker_FormElement_Field::class),
            true,
            'Aggregator Release',
            'text'
        );

        $description_field = M::mock(\Tracker_FormElement_Field::class);
        $description_field->shouldReceive('getTracker')->andReturn($tracker);
        $description_changeset_value = new \Tracker_Artifact_ChangesetValue_Text(10001, M::mock(\Tracker_Artifact_Changeset::class), $description_field, true, 'Description', 'text');

        $planned_value          = new \Tracker_FormElement_Field_List_Bind_StaticValue(2000, 'Planned', 'Irrelevant', 1, false);
        $status_changeset_value = new \Tracker_Artifact_ChangesetValue_List(
            10002,
            M::mock(\Tracker_Artifact_Changeset::class),
            M::mock(\Tracker_FormElement_Field::class),
            true,
            [$planned_value]
        );

        return new CopiedValues(
            $title_changeset_value,
            $description_changeset_value,
            $status_changeset_value,
            123456789,
            112
        );
    }
}
