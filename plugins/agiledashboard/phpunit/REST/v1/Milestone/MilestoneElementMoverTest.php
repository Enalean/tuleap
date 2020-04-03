<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\AgileDashboard\REST\v1\Milestone;

use MilestoneParentLinker;
use Mockery;
use PHPUnit\Framework\TestCase;
use Project;
use Tracker_Artifact;
use Tracker_ArtifactFactory;
use Tracker_FormElement_Field_ArtifactLink;
use Tuleap\AgileDashboard\ExplicitBacklog\ArtifactsInExplicitBacklogDao;
use Tuleap\AgileDashboard\REST\v1\MilestoneResourceValidator;
use Tuleap\AgileDashboard\REST\v1\ResourcesPatcher;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Tracker\REST\v1\ArtifactLinkUpdater;

class MilestoneElementMoverTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var DBTransactionExecutorPassthrough
     */
    private $db_transaction_executor;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_ArtifactFactory
     */
    private $tracker_artifact_factory;
    /**
     * @var MilestoneParentLinker|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $milestone_parent_linker;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ArtifactsInExplicitBacklogDao
     */
    private $explicit_backlog_dao;
    /**
     * @var MilestoneElementMover
     */
    private $mover;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ArtifactLinkUpdater
     */
    private $artifact_link_updater;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|MilestoneResourceValidator
     */
    private $milestone_validator;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ResourcesPatcher
     */
    private $resources_patcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resources_patcher        = Mockery::mock(ResourcesPatcher::class);
        $this->milestone_validator      = Mockery::mock(MilestoneResourceValidator::class);
        $this->artifact_link_updater    = Mockery::mock(ArtifactLinkUpdater::class);
        $this->db_transaction_executor  = new DBTransactionExecutorPassthrough();
        $this->tracker_artifact_factory = Mockery::mock(Tracker_ArtifactFactory::class);
        $this->milestone_parent_linker  = Mockery::mock(MilestoneParentLinker::class);
        $this->explicit_backlog_dao     = Mockery::mock(ArtifactsInExplicitBacklogDao::class);

        $this->mover = new MilestoneElementMover(
            $this->resources_patcher,
            $this->milestone_validator,
            $this->artifact_link_updater,
            $this->db_transaction_executor,
            $this->tracker_artifact_factory,
            $this->milestone_parent_linker,
            $this->explicit_backlog_dao
        );
    }

    public function testItMovesElementToMilestone(): void
    {
        $user         = Mockery::mock(\PFUser::class);
        $milestone    = Mockery::mock(\Planning_Milestone::class);
        $add          = ["id" => 112];
        $valid_to_add = [112];

        $expected_result = $valid_to_add;


        $this->resources_patcher->shouldReceive('startTransaction')->once();
        $this->resources_patcher->shouldReceive('removeArtifactFromSource')
            ->withArgs([$user, $add])
            ->once()
            ->andReturn($valid_to_add);

        $this->milestone_validator->shouldReceive('validateArtifactIdsCanBeAddedToBacklog')
            ->once()
            ->andReturn($valid_to_add);

        $artifact = Mockery::mock(\Tracker_Artifact::class);
        $milestone->shouldReceive('getArtifact')
            ->twice()
            ->andReturn($artifact);

        $this->artifact_link_updater->shouldReceive('updateArtifactLinks')
            ->withArgs(
                [
                    $user,
                    $milestone->getArtifact(),
                    $valid_to_add,
                    [],
                    Tracker_FormElement_Field_ArtifactLink::NO_NATURE
                ]
            )->once();
        $this->resources_patcher->shouldReceive('commit')->once();

        $result = $this->mover->moveElement($user, $add, $milestone);

        $this->assertEquals($expected_result, $result);
    }

    public function testItMovesElementFromTopBacklogToARelease(): void
    {
        $user      = Mockery::mock(\PFUser::class);
        $milestone = Mockery::mock(\Planning_Milestone::class);
        $add       = ["id" => 112];

        $this->resources_patcher->shouldReceive('removeArtifactFromSource')->once()->andReturn([$add]);

        $milestone->shouldReceive('getArtifact')->once()->andReturn(Mockery::mock(Tracker_Artifact::class));
        $planning_milestone = Mockery::mock(\Planning_Milestone::class);
        $planning_milestone->shouldReceive('getBacklogTrackersIds')->once()->andReturn([10, 11]);
        $milestone->shouldReceive('getPlanning')->once()->andReturn($planning_milestone);
        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getID')->once()->andReturn(101);
        $milestone->shouldReceive('getProject')->once()->andReturn($project);

        $artifact = Mockery::mock(Tracker_Artifact::class);
        $artifact->shouldReceive('getTrackerId')->once()->andReturn(10);
        $this->tracker_artifact_factory->shouldReceive('getArtifactById')->twice()->withArgs([$add])->andReturn($artifact);

        $this->artifact_link_updater->shouldReceive('updateArtifactLinks')->once();
        $this->milestone_parent_linker->shouldReceive('linkToMilestoneParent')->once();
        $this->explicit_backlog_dao->shouldReceive('removeItemsFromExplicitBacklogOfProject')->once();

        $this->mover->moveElementToMilestoneContent($milestone, $user, $add);
    }
}
