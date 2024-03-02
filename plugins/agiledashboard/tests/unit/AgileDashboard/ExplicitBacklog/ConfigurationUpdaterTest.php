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
use PHPUnit\Framework\MockObject\MockObject;
use Planning;
use Planning_MilestoneFactory;
use Planning_VirtualTopMilestone;
use Psr\EventDispatcher\EventDispatcherInterface;
use TestHelper;
use Tuleap\AgileDashboard\Planning\PlanningAdministrationDelegation;
use Tuleap\AgileDashboard\Workflow\AddToTopBacklogPostActionDao;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Test\PHPUnit\TestCase;

class ConfigurationUpdaterTest extends TestCase
{
    use GlobalResponseMock;

    private ConfigurationUpdater $updater;
    private ExplicitBacklogDao&MockObject $explicit_backlog_dao;
    private MilestoneReportCriterionDao&MockObject $milestone_report_criterion_dao;
    private AgileDashboard_BacklogItemDao&MockObject $backlog_item_dao;
    private ArtifactsInExplicitBacklogDao&MockObject $artifacts_in_explicit_backlog_dao;
    private Codendi_Request&MockObject $request;
    private UnplannedArtifactsAdder&MockObject $unplanned_artifacts_adder;
    private AddToTopBacklogPostActionDao&MockObject $add_to_top_backlog_post_action_dao;
    private EventDispatcherInterface $event_dispatcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->explicit_backlog_dao               = $this->createMock(ExplicitBacklogDao::class);
        $this->milestone_report_criterion_dao     = $this->createMock(MilestoneReportCriterionDao::class);
        $this->backlog_item_dao                   = $this->createMock(AgileDashboard_BacklogItemDao::class);
        $milestone_factory                        = $this->createMock(Planning_MilestoneFactory::class);
        $this->artifacts_in_explicit_backlog_dao  = $this->createMock(ArtifactsInExplicitBacklogDao::class);
        $this->unplanned_artifacts_adder          = $this->createMock(UnplannedArtifactsAdder::class);
        $this->add_to_top_backlog_post_action_dao = $this->createMock(AddToTopBacklogPostActionDao::class);
        $db_transaction_executor                  = new DBTransactionExecutorPassthrough();
        $this->event_dispatcher                   = new class implements EventDispatcherInterface {
            public bool $is_planning_administration_delegated = false;

            public function dispatch(object $event): object
            {
                if ($event instanceof PlanningAdministrationDelegation && $this->is_planning_administration_delegated) {
                    $event->enablePlanningAdministrationDelegation();
                }
                return $event;
            }
        };

        $this->updater = new ConfigurationUpdater(
            $this->explicit_backlog_dao,
            $this->milestone_report_criterion_dao,
            $this->backlog_item_dao,
            $milestone_factory,
            $this->artifacts_in_explicit_backlog_dao,
            $this->unplanned_artifacts_adder,
            $this->add_to_top_backlog_post_action_dao,
            $db_transaction_executor,
            $this->event_dispatcher
        );

        $project = ProjectTestBuilder::aProject()->withId(101)->build();
        $user    = UserTestBuilder::buildWithDefaults();

        $this->request = $this->createMock(Codendi_Request::class);
        $this->request->method('getProject')->willReturn($project);
        $this->request->method('getCurrentUser')->willReturn($user);
        $this->request->method('exist')->with('use-explicit-top-backlog')->willReturn(true);

        $planning = $this->createMock(Planning::class);
        $planning->method('getBacklogTrackersIds')->willReturn([101, 102]);
        $top_milestone = $this->createMock(Planning_VirtualTopMilestone::class);
        $top_milestone->method('getPlanning')->willReturn($planning);
        $milestone_factory->method('getVirtualTopMilestone')->willReturn($top_milestone);
    }

    public function testItDoesNothingIfOptionNotProvidedInRequest(): void
    {
        $request = new Codendi_Request([
            'group_id' => '101',
        ]);

        $this->artifacts_in_explicit_backlog_dao->expects(self::never())->method('removeExplicitBacklogOfProject');
        $this->explicit_backlog_dao->expects(self::never())->method('setProjectIsUsingExplicitBacklog');
        $this->milestone_report_criterion_dao->expects(self::never())->method('updateAllUnplannedValueToAnyInProject');
        $this->backlog_item_dao->expects(self::never())->method('getOpenUnplannedTopBacklogArtifacts');
        $this->unplanned_artifacts_adder->expects(self::never())->method('addArtifactToTopBacklogFromIds');
        $this->add_to_top_backlog_post_action_dao->expects(self::never())->method('deleteAllPostActionsInProject');

        $this->updater->updateScrumConfiguration($request);
    }

    public function testItDoesNothingIfStillActivated(): void
    {
        $this->request->method('exist')->with('use-explicit-top-backlog')->willReturn(true);
        $this->request->method('get')->with('use-explicit-top-backlog')->willReturn('1');

        $this->artifacts_in_explicit_backlog_dao->expects(self::never())->method('removeExplicitBacklogOfProject');
        $this->explicit_backlog_dao->expects(self::never())->method('setProjectIsUsingExplicitBacklog');
        $this->milestone_report_criterion_dao->expects(self::never())->method('updateAllUnplannedValueToAnyInProject');
        $this->backlog_item_dao->expects(self::never())->method('getOpenUnplannedTopBacklogArtifacts');
        $this->unplanned_artifacts_adder->expects(self::never())->method('addArtifactToTopBacklogFromIds');
        $this->add_to_top_backlog_post_action_dao->expects(self::never())->method('deleteAllPostActionsInProject');

        $this->explicit_backlog_dao->expects(self::once())->method('isProjectUsingExplicitBacklog')
            ->with(101)
            ->willReturn(true);

        $this->updater->updateScrumConfiguration($this->request);
    }

    public function testItDoesNothingIfStillDeactivated(): void
    {
        $this->request->method('get')->with('use-explicit-top-backlog')->willReturn('0');

        $this->artifacts_in_explicit_backlog_dao->expects(self::never())->method('removeExplicitBacklogOfProject');
        $this->explicit_backlog_dao->expects(self::never())->method('setProjectIsUsingExplicitBacklog');
        $this->milestone_report_criterion_dao->expects(self::never())->method('updateAllUnplannedValueToAnyInProject');
        $this->backlog_item_dao->expects(self::never())->method('getOpenUnplannedTopBacklogArtifacts');
        $this->unplanned_artifacts_adder->expects(self::never())->method('addArtifactToTopBacklogFromIds');
        $this->add_to_top_backlog_post_action_dao->expects(self::never())->method('deleteAllPostActionsInProject');

        $this->explicit_backlog_dao->expects(self::once())->method('isProjectUsingExplicitBacklog')
            ->with(101)
            ->willReturn(false);

        $this->updater->updateScrumConfiguration($this->request);
    }

    public function testItActivatesExplicitBacklogManagement(): void
    {
        $this->request->method('get')->with('use-explicit-top-backlog')->willReturn('1');

        $this->artifacts_in_explicit_backlog_dao->expects(self::never())->method('removeExplicitBacklogOfProject');
        $this->explicit_backlog_dao->expects(self::once())->method('setProjectIsUsingExplicitBacklog');
        $this->milestone_report_criterion_dao->expects(self::never())->method('updateAllUnplannedValueToAnyInProject');
        $this->add_to_top_backlog_post_action_dao->expects(self::never())->method('deleteAllPostActionsInProject');
        $this->backlog_item_dao->method('getOpenUnplannedTopBacklogArtifacts')->willReturn(
            TestHelper::arrayToDar(
                ['id' => '201'],
                ['id' => '202']
            )
        );
        $this->unplanned_artifacts_adder->expects(self::exactly(2))->method('addArtifactToTopBacklogFromIds');

        $this->explicit_backlog_dao->expects(self::once())->method('isProjectUsingExplicitBacklog')
            ->with(101)
            ->willReturn(false);

        $this->updater->updateScrumConfiguration($this->request);
    }

    public function testItDeactivatesExplicitBacklogManagement(): void
    {
        $this->request->method('get')->with('use-explicit-top-backlog')->willReturn('0');

        $this->artifacts_in_explicit_backlog_dao->expects(self::once())->method('removeExplicitBacklogOfProject');
        $this->explicit_backlog_dao->expects(self::never())->method('setProjectIsUsingExplicitBacklog');
        $this->milestone_report_criterion_dao->expects(self::once())->method('updateAllUnplannedValueToAnyInProject');
        $this->backlog_item_dao->expects(self::never())->method('getOpenUnplannedTopBacklogArtifacts');
        $this->unplanned_artifacts_adder->expects(self::never())->method('addArtifactToTopBacklogFromIds');
        $this->add_to_top_backlog_post_action_dao->expects(self::once())->method('deleteAllPostActionsInProject');
        $this->add_to_top_backlog_post_action_dao->expects(self::once())->method('isAtLeastOnePostActionDefinedInProject');

        $this->explicit_backlog_dao->expects(self::once())->method('isProjectUsingExplicitBacklog')
            ->with(101)
            ->willReturn(true);

        $this->updater->updateScrumConfiguration($this->request);
    }

    public function testItAlwaysActivatesExplicitBacklogManagementWhenPlanningAdministrationIsDelegatedToAnotherPlugin(): void
    {
        $this->request->method('get')->with('use-explicit-top-backlog')->willReturn('0');
        $this->event_dispatcher->is_planning_administration_delegated = true;

        $this->artifacts_in_explicit_backlog_dao->expects(self::never())->method('removeExplicitBacklogOfProject');
        $this->explicit_backlog_dao->expects(self::once())->method('setProjectIsUsingExplicitBacklog');
        $this->milestone_report_criterion_dao->expects(self::never())->method('updateAllUnplannedValueToAnyInProject');
        $this->add_to_top_backlog_post_action_dao->expects(self::never())->method('deleteAllPostActionsInProject');
        $this->backlog_item_dao->method('getOpenUnplannedTopBacklogArtifacts')->willReturn(
            TestHelper::arrayToDar(
                ['id' => '201'],
                ['id' => '202']
            )
        );
        $this->unplanned_artifacts_adder->expects(self::exactly(2))->method('addArtifactToTopBacklogFromIds');

        $this->explicit_backlog_dao->expects(self::once())->method('isProjectUsingExplicitBacklog')
            ->with(101)
            ->willReturn(false);

        $this->updater->updateScrumConfiguration($this->request);
    }
}
