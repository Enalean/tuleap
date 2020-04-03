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

namespace Tuleap\AgileDashboard\ExplicitBacklog;

use AgileDashboard_BacklogItemDao;
use Codendi_Request;
use MilestoneReportCriterionDao;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Planning;
use Planning_MilestoneFactory;
use Planning_VirtualTopMilestone;
use Project;
use Tuleap\AgileDashboard\Workflow\AddToTopBacklogPostActionDao;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;

class ConfigurationUpdaterTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalResponseMock;

    /**
     * @var ConfigurationUpdater
     */
    private $updater;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ExplicitBacklogDao
     */
    private $explicit_backlog_dao;

    /**
     * @var MilestoneReportCriterionDao|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $milestone_report_criterion_dao;

    /**
     * @var DBTransactionExecutorPassthrough
     */
    private $db_transaction_executor;

    /**
     * @var AgileDashboard_BacklogItemDao|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $backlog_item_dao;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Planning_MilestoneFactory
     */
    private $milestone_factory;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ArtifactsInExplicitBacklogDao
     */
    private $artifacts_in_explicit_backlog_dao;

    /**
     * @var Codendi_Request|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $request;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|UnplannedArtifactsAdder
     */
    private $unplanned_artifacts_adder;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|AddToTopBacklogPostActionDao
     */
    private $add_to_top_backlog_post_action_dao;

    protected function setUp(): void
    {
        parent::setUp();

        $this->explicit_backlog_dao               = Mockery::mock(ExplicitBacklogDao::class);
        $this->milestone_report_criterion_dao     = Mockery::mock(MilestoneReportCriterionDao::class);
        $this->backlog_item_dao                   = Mockery::mock(AgileDashboard_BacklogItemDao::class);
        $this->milestone_factory                  = Mockery::mock(Planning_MilestoneFactory::class);
        $this->artifacts_in_explicit_backlog_dao  = Mockery::mock(ArtifactsInExplicitBacklogDao::class);
        $this->unplanned_artifacts_adder          = Mockery::mock(UnplannedArtifactsAdder::class);
        $this->add_to_top_backlog_post_action_dao = Mockery::mock(AddToTopBacklogPostActionDao::class);
        $this->db_transaction_executor            = new DBTransactionExecutorPassthrough();

        $this->updater = new ConfigurationUpdater(
            $this->explicit_backlog_dao,
            $this->milestone_report_criterion_dao,
            $this->backlog_item_dao,
            $this->milestone_factory,
            $this->artifacts_in_explicit_backlog_dao,
            $this->unplanned_artifacts_adder,
            $this->add_to_top_backlog_post_action_dao,
            $this->db_transaction_executor
        );

        $project = Mockery::mock(Project::class)->shouldReceive('getID')->andReturn('101')->getMock();
        $user    = Mockery::mock(PFUser::class);

        $this->request = Mockery::mock(Codendi_Request::class);
        $this->request->shouldReceive('getProject')->andReturn($project);
        $this->request->shouldReceive('getCurrentUser')->andReturn($user);
        $this->request->shouldReceive('exist')->with('use-explicit-top-backlog')->andReturnTrue();

        $planning = Mockery::mock(Planning::class);
        $planning->shouldReceive('getBacklogTrackersIds')->andReturn([101, 102]);
        $top_milestone = Mockery::mock(Planning_VirtualTopMilestone::class);
        $top_milestone->shouldReceive('getPlanning')->andReturn($planning);
        $this->milestone_factory->shouldReceive('getVirtualTopMilestone')->andReturn($top_milestone);
    }

    public function testItDoesNothingIfOptionNotProvidedInRequest()
    {
        $request = new Codendi_Request([
            'group_id' => '101'
        ]);

        $this->artifacts_in_explicit_backlog_dao->shouldNotReceive('removeExplicitBacklogOfProject');
        $this->explicit_backlog_dao->shouldNotReceive('setProjectUsingExplicitBacklog');
        $this->milestone_report_criterion_dao->shouldNotReceive('updateAllUnplannedValueToAnyInProject');
        $this->backlog_item_dao->shouldNotReceive('getOpenUnplannedTopBacklogArtifacts');
        $this->unplanned_artifacts_adder->shouldNotReceive('addArtifactToTopBacklogFromIds');
        $this->add_to_top_backlog_post_action_dao->shouldNotReceive('deleteAllPostActionsInProject');

        $this->updater->updateScrumConfiguration($request);
    }

    public function testItDoesNothingIfStillActivated()
    {
        $this->request->shouldReceive('exist')->with('use-explicit-top-backlog')->andReturnTrue();
        $this->request->shouldReceive('get')->with('use-explicit-top-backlog')->andReturn('1');

        $this->artifacts_in_explicit_backlog_dao->shouldNotReceive('removeExplicitBacklogOfProject');
        $this->explicit_backlog_dao->shouldNotReceive('setProjectUsingExplicitBacklog');
        $this->milestone_report_criterion_dao->shouldNotReceive('updateAllUnplannedValueToAnyInProject');
        $this->backlog_item_dao->shouldNotReceive('getOpenUnplannedTopBacklogArtifacts');
        $this->unplanned_artifacts_adder->shouldNotReceive('addArtifactToTopBacklogFromIds');
        $this->add_to_top_backlog_post_action_dao->shouldNotReceive('deleteAllPostActionsInProject');

        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')
            ->once()
            ->with(101)
            ->andReturnTrue();

        $this->updater->updateScrumConfiguration($this->request);
    }

    public function testItDoesNothingIfStillDeactivated()
    {
        $this->request->shouldReceive('get')->with('use-explicit-top-backlog')->andReturn('0');

        $this->artifacts_in_explicit_backlog_dao->shouldNotReceive('removeExplicitBacklogOfProject');
        $this->explicit_backlog_dao->shouldNotReceive('setProjectUsingExplicitBacklog');
        $this->milestone_report_criterion_dao->shouldNotReceive('updateAllUnplannedValueToAnyInProject');
        $this->backlog_item_dao->shouldNotReceive('getOpenUnplannedTopBacklogArtifacts');
        $this->unplanned_artifacts_adder->shouldNotReceive('addArtifactToTopBacklogFromIds');
        $this->add_to_top_backlog_post_action_dao->shouldNotReceive('deleteAllPostActionsInProject');

        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')
            ->once()
            ->with(101)
            ->andReturnFalse();

        $this->updater->updateScrumConfiguration($this->request);
    }

    public function testItActivatesExplicitBacklogManagement()
    {
        $this->request->shouldReceive('get')->with('use-explicit-top-backlog')->andReturn('1');

        $this->artifacts_in_explicit_backlog_dao->shouldNotReceive('removeExplicitBacklogOfProject');
        $this->explicit_backlog_dao->shouldReceive('setProjectIsUsingExplicitBacklog')->once();
        $this->milestone_report_criterion_dao->shouldNotReceive('updateAllUnplannedValueToAnyInProject');
        $this->add_to_top_backlog_post_action_dao->shouldNotReceive('deleteAllPostActionsInProject');
        $this->backlog_item_dao->shouldReceive('getOpenUnplannedTopBacklogArtifacts')->andReturn(
            \TestHelper::arrayToDar(
                ['id' => '201'],
                ['id' => '202']
            )
        );
        $this->unplanned_artifacts_adder->shouldReceive('addArtifactToTopBacklogFromIds')->times(2);

        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')
            ->once()
            ->with(101)
            ->andReturnFalse();

        $this->updater->updateScrumConfiguration($this->request);
    }

    public function testItDeactivatesExplicitBacklogManagement()
    {
        $this->request->shouldReceive('get')->with('use-explicit-top-backlog')->andReturn('0');

        $this->artifacts_in_explicit_backlog_dao->shouldReceive('removeExplicitBacklogOfProject')->once();
        $this->explicit_backlog_dao->shouldNotReceive('setProjectIsUsingExplicitBacklog');
        $this->milestone_report_criterion_dao->shouldReceive('updateAllUnplannedValueToAnyInProject')->once();
        $this->backlog_item_dao->shouldNotReceive('getOpenUnplannedTopBacklogArtifacts');
        $this->unplanned_artifacts_adder->shouldNotReceive('addArtifactToTopBacklogFromIds');
        $this->add_to_top_backlog_post_action_dao->shouldReceive('deleteAllPostActionsInProject')->once();
        $this->add_to_top_backlog_post_action_dao->shouldReceive('isAtLeastOnePostActionDefinedInProject')->once();

        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')
            ->once()
            ->with(101)
            ->andReturnTrue();

        $this->updater->updateScrumConfiguration($this->request);
    }
}
