<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

namespace Tuleap\ProjectMilestones\Widget;

use AgileDashboard_Milestone_Backlog_Backlog;
use AgileDashboard_Milestone_Backlog_BacklogFactory;
use AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory;
use AgileDashboard_Milestone_Backlog_IBacklogItemCollection;
use HTTPRequest;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Planning;
use Planning_Milestone;
use Planning_MilestoneFactory;
use Planning_VirtualTopMilestone;
use Project;
use Tracker;
use TrackerFactory;
use Tuleap\AgileDashboard\ExplicitBacklog\ArtifactsInExplicitBacklogDao;
use Tuleap\AgileDashboard\ExplicitBacklog\ExplicitBacklogDao;
use Tuleap\AgileDashboard\FormElement\Burnup\CountElementsModeChecker;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframe;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeBrokenConfigurationException;
use Tuleap\Tracker\TrackerColor;
use Tuleap\Project\ProjectAccessChecker;
use Project_AccessProjectNotFoundException;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use ForgeConfig;

class ProjectMilestonesPresenterBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var HTTPRequest|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $http_request;
    /**
     * @var ProjectMilestonesPresenterBuilder
     */
    private $builder;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Project
     */
    private $project;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PFUser
     */
    private $john_doe;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Planning_MilestoneFactory
     */
    private $planning_milestone_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Planning_VirtualTopMilestone
     */
    private $planning_virtual_top_milestone;
    /**
     * @var AgileDashboard_Milestone_Backlog_IBacklogItemCollection|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $agileDashboard_milestone_backlog_item_collection;
    /**
     * @var AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $agiledashboard_milestone_backlog_item_collection_factory;
    /**
     * @var AgileDashboard_Milestone_Backlog_BacklogFactory|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $agiledashboard_milestone_backlog_factory;
    /**
     * @var AgileDashboard_Milestone_Backlog_Backlog|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $agiledashboard_milestone_backlog;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ExplicitBacklogDao
     */
    private $explicit_backlog_dao;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ArtifactsInExplicitBacklogDao
     */
    private $artifacts_in_explicit_backlog_dao;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Planning
     */
    private $root_planning;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker
     */
    private $tracker;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|SemanticTimeframe
     */
    private $semantic_timeframe;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|CountElementsModeChecker
     */
    private $count_elements_mode_checker;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ProjectAccessChecker
     */
    private $project_access_checker;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|SemanticTimeframeBuilder
     */
    private $semantic_timeframe_builder;

    public function setUp(): void
    {
        parent::setUp();
        $this->http_request                                             = Mockery::mock(HTTPRequest::class);
        $this->planning_milestone_factory                               = Mockery::mock(Planning_MilestoneFactory::class);
        $this->project                                                  = Mockery::mock(Project::class, ['getID' => 101]);
        $this->john_doe                                                 = Mockery::mock(PFUser::class);
        $this->planning_virtual_top_milestone                           = Mockery::mock(Planning_VirtualTopMilestone::class);
        $this->agileDashboard_milestone_backlog_item_collection         = Mockery::mock(AgileDashboard_Milestone_Backlog_IBacklogItemCollection::class);
        $this->agiledashboard_milestone_backlog_item_collection_factory = Mockery::mock(AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory::class);
        $this->agiledashboard_milestone_backlog_factory                 = Mockery::mock(AgileDashboard_Milestone_Backlog_BacklogFactory::class);
        $this->agiledashboard_milestone_backlog                         = Mockery::mock(AgileDashboard_Milestone_Backlog_Backlog::class);
        $this->tracker_factory                                          = Mockery::mock(TrackerFactory::class);
        $this->explicit_backlog_dao                                     = Mockery::mock(ExplicitBacklogDao::class);
        $this->artifacts_in_explicit_backlog_dao                        = Mockery::mock(ArtifactsInExplicitBacklogDao::class);
        $this->root_planning                                            = Mockery::mock(Planning::class);
        $this->tracker                                                  = Mockery::mock(Tracker::class);
        $this->semantic_timeframe_builder                               = Mockery::mock(SemanticTimeframeBuilder::class);
        $this->semantic_timeframe                                       = Mockery::mock(SemanticTimeframe::class);
        $this->count_elements_mode_checker                              = Mockery::mock(CountElementsModeChecker::class);
        $this->project_access_checker                                   = Mockery::mock(ProjectAccessChecker::class);

        $this->root_planning->shouldReceive('getPlanningTracker')->andReturn($this->tracker);
        $this->tracker->shouldReceive('getName')->andReturn("Releases");

        $this->http_request->shouldReceive('getProject')->andReturn($this->project);
        $this->http_request->shouldReceive('getCurrentUser')->andReturn($this->john_doe);

        $this->planning_milestone_factory
            ->shouldReceive('getVirtualTopMilestone')
            ->withArgs([$this->john_doe, $this->project])
            ->andReturn($this->planning_virtual_top_milestone);

        $this->semantic_timeframe_builder->shouldReceive('getSemantic')->andReturn($this->semantic_timeframe);

        $this->builder = new ProjectMilestonesPresenterBuilder(
            $this->http_request,
            $this->agiledashboard_milestone_backlog_factory,
            $this->agiledashboard_milestone_backlog_item_collection_factory,
            $this->planning_milestone_factory,
            $this->tracker_factory,
            $this->explicit_backlog_dao,
            $this->artifacts_in_explicit_backlog_dao,
            $this->semantic_timeframe_builder,
            $this->count_elements_mode_checker,
            $this->project_access_checker
        );
    }

    public function testGetZeroUpcomingReleaseWhenThereAreNoFutureMilestone(): void
    {
        $this->mockPlanningTopMilestoneEmpty($this->planning_virtual_top_milestone);

        $this->mockAgiledashboardBacklogFactory($this->agiledashboard_milestone_backlog_factory);

        $this->mockAgiledashboardBacklogItemFactory($this->agiledashboard_milestone_backlog_item_collection_factory);

        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')->andReturn(false)->once();

        $this->agileDashboard_milestone_backlog_item_collection->shouldReceive('count')->once()->andReturn(5);

        $this->planning_milestone_factory->shouldReceive('getAllFutureMilestones')->once()->andReturn([]);

        $this->mockTimeframe($this->semantic_timeframe);

        $this->tracker->shouldReceive('getChildren')->andReturn([$this->tracker]);
        $this->tracker->shouldReceive('userCanView')->andReturn(true);

        $this->count_elements_mode_checker->shouldReceive("burnupMustUseCountElementsMode")->once()->andReturn(true);

        $this->http_request->shouldReceive("getBrowser")->andReturn(Mockery::mock(\Browser::class, ["isIE11" => false]));

        $this->project_access_checker->shouldReceive("checkUserCanAccessProject")->once();

        $built_presenter = $this->builder->getProjectMilestonePresenter($this->project, $this->root_planning);

        $this->assertEquals(0, $built_presenter->nb_upcoming_releases);
    }

    public function testGetUpcomingReleasesWhenThereAreFutureMilestones(): void
    {
        $this->mockPlanningTopMilestoneEmpty($this->planning_virtual_top_milestone);

        $this->mockAgiledashboardBacklogFactory($this->agiledashboard_milestone_backlog_factory);

        $this->mockAgiledashboardBacklogItemFactory($this->agiledashboard_milestone_backlog_item_collection_factory);

        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')->andReturn(false)->once();

        $this->agileDashboard_milestone_backlog_item_collection->shouldReceive('count')->once()->andReturn(5);

        $this->mockTimeframe($this->semantic_timeframe);

        $this->planning_milestone_factory
            ->shouldReceive('getAllFutureMilestones')
            ->once()
            ->andReturn([Mockery::mock(Planning_Milestone::class), Mockery::mock(Planning_Milestone::class), Mockery::mock(Planning_Milestone::class)]);

        $this->tracker->shouldReceive('getChildren')->andReturn([$this->tracker]);
        $this->tracker->shouldReceive('userCanView')->andReturn(true);

        $this->count_elements_mode_checker->shouldReceive("burnupMustUseCountElementsMode")->once()->andReturn(true);

        $this->http_request->shouldReceive("getBrowser")->andReturn(Mockery::mock(\Browser::class, ["isIE11" => false]));

        $this->project_access_checker->shouldReceive("checkUserCanAccessProject")->once();

        $built_presenter = $this->builder->getProjectMilestonePresenter($this->project, $this->root_planning);

        $this->assertEquals(3, $built_presenter->nb_upcoming_releases);
    }

    public function testGetNumberBacklogItem(): void
    {
        $this->mockPlanningTopMilestoneEmpty($this->planning_virtual_top_milestone);

        $this->mockAgiledashboardBacklogFactory($this->agiledashboard_milestone_backlog_factory);

        $this->mockAgiledashboardBacklogItemFactory($this->agiledashboard_milestone_backlog_item_collection_factory);

        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')->andReturn(false)->once();

        $this->agileDashboard_milestone_backlog_item_collection->shouldReceive('count')->andReturn(5);

        $this->mockTimeframe($this->semantic_timeframe);

        $this->planning_milestone_factory
            ->shouldReceive('getAllFutureMilestones')
            ->once()
            ->andReturn([Mockery::mock(Planning_Milestone::class), Mockery::mock(Planning_Milestone::class), Mockery::mock(Planning_Milestone::class)]);

        $this->tracker->shouldReceive('getChildren')->andReturn([$this->tracker]);
        $this->tracker->shouldReceive('userCanView')->andReturn(true);

        $this->count_elements_mode_checker->shouldReceive("burnupMustUseCountElementsMode")->once()->andReturn(true);

        $this->http_request->shouldReceive("getBrowser")->andReturn(Mockery::mock(\Browser::class, ["isIE11" => false]));

        $this->project_access_checker->shouldReceive("checkUserCanAccessProject")->once();

        $built_presenter = $this->builder->getProjectMilestonePresenter($this->project, $this->root_planning);

        $this->assertEquals(5, $built_presenter->nb_backlog_items);
    }

    public function testGetTrackersId(): void
    {
        $this->planning_virtual_top_milestone
            ->shouldReceive('getPlanning')
            ->once()
            ->andReturn(Mockery::mock(Planning::class, ['getBacklogTrackersIds' => [122, 124]]));

        $this->tracker_factory->shouldReceive('getTrackerById')->once()->withArgs([122])->andReturn($this->mockAnArtifact('Bug', 'fiesta-red'));
        $this->tracker_factory->shouldReceive('getTrackerById')->once()->withArgs([124])->andReturn($this->mockAnArtifact('Story', 'deep-blue'));

        $this->mockAgiledashboardBacklogFactory($this->agiledashboard_milestone_backlog_factory);

        $this->mockAgiledashboardBacklogItemFactory($this->agiledashboard_milestone_backlog_item_collection_factory);

        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')->andReturn(false)->once();

        $this->agileDashboard_milestone_backlog_item_collection->shouldReceive('count')->once()->andReturn(0);

        $this->mockTimeframe($this->semantic_timeframe);

        $this->planning_milestone_factory
            ->shouldReceive('getAllFutureMilestones')
            ->once()
            ->andReturn([Mockery::mock(Planning_Milestone::class), Mockery::mock(Planning_Milestone::class), Mockery::mock(Planning_Milestone::class)]);

        $this->tracker->shouldReceive('getChildren')->andReturn([$this->tracker]);
        $this->tracker->shouldReceive('userCanView')->andReturn(true);

        $this->count_elements_mode_checker->shouldReceive("burnupMustUseCountElementsMode")->once()->andReturn(true);

        $this->http_request->shouldReceive("getBrowser")->andReturn(Mockery::mock(\Browser::class, ["isIE11" => false]));

        $this->project_access_checker->shouldReceive("checkUserCanAccessProject")->once();

        $built_presenter = $this->builder->getProjectMilestonePresenter($this->project, $this->root_planning);
        $tracker_json    = '[{"id":122,"color_name":"fiesta-red","label":"Bug"},{"id":124,"color_name":"deep-blue","label":"Story"}]';

        $this->assertEqualsCanonicalizing($tracker_json, $built_presenter->json_trackers_agile_dashboard);
    }

    public function testGetNumberItemsOfExplicitBacklogAndNotTopBacklog(): void
    {
        $this->mockPlanningTopMilestoneEmpty($this->planning_virtual_top_milestone);

        $this->agiledashboard_milestone_backlog_factory
            ->shouldReceive('getSelfBacklog')
            ->withArgs([$this->planning_virtual_top_milestone])
            ->never();

        $this->agiledashboard_milestone_backlog_item_collection_factory
            ->shouldReceive('getUnassignedOpenCollection')
            ->andReturn($this->agileDashboard_milestone_backlog_item_collection)
            ->never();

        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')->andReturn(true)->once();

        $this->artifacts_in_explicit_backlog_dao->shouldReceive('getNumberOfItemsInExplicitBacklog')->andReturn(50)->once();

        $this->agileDashboard_milestone_backlog_item_collection->shouldReceive('count')->andReturn(5);

        $this->mockTimeframe($this->semantic_timeframe);

        $this->planning_milestone_factory
            ->shouldReceive('getAllFutureMilestones')
            ->once()
            ->andReturn([Mockery::mock(Planning_Milestone::class), Mockery::mock(Planning_Milestone::class), Mockery::mock(Planning_Milestone::class)]);

        $this->tracker->shouldReceive('getChildren')->andReturn([$this->tracker]);
        $this->tracker->shouldReceive('userCanView')->andReturn(true);

        $this->count_elements_mode_checker->shouldReceive("burnupMustUseCountElementsMode")->once()->andReturn(true);

        $this->http_request->shouldReceive("getBrowser")->andReturn(Mockery::mock(\Browser::class, ["isIE11" => false]));

        $this->project_access_checker->shouldReceive("checkUserCanAccessProject")->once();

        $built_presenter = $this->builder->getProjectMilestonePresenter($this->project, $this->root_planning);

        $this->assertEquals(50, $built_presenter->nb_backlog_items);
    }

    public function testGetLabelOfTrackerPlanning(): void
    {
        $this->mockPlanningTopMilestoneEmpty($this->planning_virtual_top_milestone);

        $this->mockAgiledashboardBacklogFactory($this->agiledashboard_milestone_backlog_factory);

        $this->mockAgiledashboardBacklogItemFactory($this->agiledashboard_milestone_backlog_item_collection_factory);

        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')->andReturn(false)->once();

        $this->agileDashboard_milestone_backlog_item_collection->shouldReceive('count')->once()->andReturn(5);
        $this->mockTimeframe($this->semantic_timeframe);

        $this->planning_milestone_factory
            ->shouldReceive('getAllFutureMilestones')
            ->once()
            ->andReturn([Mockery::mock(Planning_Milestone::class), Mockery::mock(Planning_Milestone::class), Mockery::mock(Planning_Milestone::class)]);

        $this->tracker->shouldReceive('getChildren')->andReturn([$this->tracker]);
        $this->tracker->shouldReceive('userCanView')->andReturn(true);

        $this->count_elements_mode_checker->shouldReceive("burnupMustUseCountElementsMode")->once()->andReturn(true);

        $this->http_request->shouldReceive("getBrowser")->andReturn(Mockery::mock(\Browser::class, ["isIE11" => false]));

        $this->project_access_checker->shouldReceive("checkUserCanAccessProject")->once();

        $built_presenter = $this->builder->getProjectMilestonePresenter($this->project, $this->root_planning);

        $this->assertEquals('Releases', $built_presenter->label_tracker_planning);
    }

    public function testIsNotTimeframeDurationField(): void
    {
        $this->mockPlanningTopMilestoneEmpty($this->planning_virtual_top_milestone);

        $this->mockAgiledashboardBacklogFactory($this->agiledashboard_milestone_backlog_factory);

        $this->mockAgiledashboardBacklogItemFactory($this->agiledashboard_milestone_backlog_item_collection_factory);

        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')->andReturn(false)->once();

        $this->agileDashboard_milestone_backlog_item_collection->shouldReceive('count')->once()->andReturn(0);

        $this->semantic_timeframe->shouldReceive('getDurationField')->andReturn(null);
        $this->semantic_timeframe->shouldReceive('getStartDateField')->andReturn(Mockery::mock(\Tracker_FormElement_Field_Date::class, ['getLabel' => 'start']));
        $this->semantic_timeframe->shouldReceive('getEndDateField')->andReturn(Mockery::mock(\Tracker_FormElement_Field_Date::class, ['getLabel' => 'end']));

        $this->planning_milestone_factory
            ->shouldReceive('getAllFutureMilestones')
            ->once()
            ->andReturn([Mockery::mock(Planning_Milestone::class), Mockery::mock(Planning_Milestone::class), Mockery::mock(Planning_Milestone::class)]);

        $this->tracker->shouldReceive('getChildren')->andReturn([$this->tracker]);
        $this->tracker->shouldReceive('userCanView')->andReturn(true);

        $this->count_elements_mode_checker->shouldReceive("burnupMustUseCountElementsMode")->once()->andReturn(true);

        $this->http_request->shouldReceive("getBrowser")->andReturn(Mockery::mock(\Browser::class, ["isIE11" => false]));

        $this->project_access_checker->shouldReceive("checkUserCanAccessProject")->once();

        $built_presenter = $this->builder->getProjectMilestonePresenter($this->project, $this->root_planning);

        $this->assertFalse($built_presenter->is_timeframe_duration);
        $this->assertEquals($built_presenter->label_timeframe, 'end');
    }

    public function testIsNotTimeframeEndDateField(): void
    {
        $this->mockPlanningTopMilestoneEmpty($this->planning_virtual_top_milestone);

        $this->mockAgiledashboardBacklogFactory($this->agiledashboard_milestone_backlog_factory);

        $this->mockAgiledashboardBacklogItemFactory($this->agiledashboard_milestone_backlog_item_collection_factory);

        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')->andReturn(false)->once();

        $this->agileDashboard_milestone_backlog_item_collection->shouldReceive('count')->once()->andReturn(0);

        $this->semantic_timeframe->shouldReceive('getDurationField')->andReturn(Mockery::mock(\Tracker_FormElement_Field_Numeric::class, ['getLabel' => 'duration']));
        $this->semantic_timeframe->shouldReceive('getStartDateField')->andReturn(Mockery::mock(\Tracker_FormElement_Field_Date::class, ['getLabel' => 'start']));
        $this->semantic_timeframe->shouldReceive('getEndDateField')->andReturn(null);

        $this->planning_milestone_factory
            ->shouldReceive('getAllFutureMilestones')
            ->once()
            ->andReturn([Mockery::mock(Planning_Milestone::class), Mockery::mock(Planning_Milestone::class), Mockery::mock(Planning_Milestone::class)]);

        $this->tracker->shouldReceive('getChildren')->andReturn([$this->tracker]);
        $this->tracker->shouldReceive('userCanView')->andReturn(true);

        $this->count_elements_mode_checker->shouldReceive("burnupMustUseCountElementsMode")->once()->andReturn(true);

        $this->http_request->shouldReceive("getBrowser")->andReturn(Mockery::mock(\Browser::class, ["isIE11" => false]));

        $this->project_access_checker->shouldReceive("checkUserCanAccessProject")->once();

        $built_presenter = $this->builder->getProjectMilestonePresenter($this->project, $this->root_planning);

        $this->assertTrue($built_presenter->is_timeframe_duration);
        $this->assertEquals($built_presenter->label_timeframe, 'duration');
    }

    public function testThrowExceptionWhenNoTimeframeEndDateAndDurationField(): void
    {
        $this->mockPlanningTopMilestoneEmpty($this->planning_virtual_top_milestone);

        $this->mockAgiledashboardBacklogFactory($this->agiledashboard_milestone_backlog_factory);

        $this->mockAgiledashboardBacklogItemFactory($this->agiledashboard_milestone_backlog_item_collection_factory);

        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')->andReturn(false)->once();

        $this->agileDashboard_milestone_backlog_item_collection->shouldReceive('count')->once()->andReturn(0);

        $this->semantic_timeframe->shouldReceive('getDurationField')->andReturn(null);
        $this->semantic_timeframe->shouldReceive('getStartDateField')->andReturn(Mockery::mock(\Tracker_FormElement_Field_Date::class, ['getLabel' => 'start']));
        $this->semantic_timeframe->shouldReceive('getEndDateField')->andReturn(null);
        $this->semantic_timeframe->shouldReceive('getTracker')->once()->andReturn(Mockery::mock(Tracker::class, ['getId' => 100]));

        $this->planning_milestone_factory
            ->shouldReceive('getAllFutureMilestones')
            ->once()
            ->andReturn([Mockery::mock(Planning_Milestone::class), Mockery::mock(Planning_Milestone::class), Mockery::mock(Planning_Milestone::class)]);

        $this->tracker->shouldReceive('getChildren')->andReturn([$this->tracker]);
        $this->tracker->shouldReceive('userCanView')->andReturn(true);

        $this->http_request->shouldReceive("getBrowser")->andReturn(Mockery::mock(\Browser::class, ["isIE11" => false]));

        $this->project_access_checker->shouldReceive("checkUserCanAccessProject")->once();

        $this->expectException(TimeframeBrokenConfigurationException::class);
        $this->builder->getProjectMilestonePresenter($this->project, $this->root_planning);
    }

    public function testGetIfUserCanViewSubMilestoneTracker(): void
    {
        $this->mockPlanningTopMilestoneEmpty($this->planning_virtual_top_milestone);

        $this->mockAgiledashboardBacklogFactory($this->agiledashboard_milestone_backlog_factory);

        $this->mockAgiledashboardBacklogItemFactory($this->agiledashboard_milestone_backlog_item_collection_factory);

        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')->andReturn(false)->once();

        $this->agileDashboard_milestone_backlog_item_collection->shouldReceive('count')->once()->andReturn(0);

        $this->mockTimeframe($this->semantic_timeframe);

        $this->planning_milestone_factory
            ->shouldReceive('getAllFutureMilestones')
            ->once()
            ->andReturn([Mockery::mock(Planning_Milestone::class), Mockery::mock(Planning_Milestone::class), Mockery::mock(Planning_Milestone::class)]);

        $this->tracker->shouldReceive('getChildren')->andReturn([$this->tracker]);
        $this->tracker->shouldReceive('userCanView')->andReturn(false);

        $this->count_elements_mode_checker->shouldReceive("burnupMustUseCountElementsMode")->once()->andReturn(true);

        $this->http_request->shouldReceive("getBrowser")->andReturn(Mockery::mock(\Browser::class, ["isIE11" => false]));

        $this->project_access_checker->shouldReceive("checkUserCanAccessProject")->once();

        $built_presenter = $this->builder->getProjectMilestonePresenter($this->project, $this->root_planning);

        $this->assertFalse($built_presenter->user_can_view_sub_milestones_planning);
    }

    public function testGetTheFirstSubmilestonePlanningLikeInAD(): void
    {
        $this->mockPlanningTopMilestoneEmpty($this->planning_virtual_top_milestone);

        $this->mockAgiledashboardBacklogFactory($this->agiledashboard_milestone_backlog_factory);

        $this->mockAgiledashboardBacklogItemFactory($this->agiledashboard_milestone_backlog_item_collection_factory);

        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')->andReturn(false)->once();

        $this->agileDashboard_milestone_backlog_item_collection->shouldReceive('count')->once()->andReturn(0);

        $this->mockTimeframe($this->semantic_timeframe);

        $this->planning_milestone_factory
            ->shouldReceive('getAllFutureMilestones')
            ->once()
            ->andReturn([Mockery::mock(Planning_Milestone::class), Mockery::mock(Planning_Milestone::class), Mockery::mock(Planning_Milestone::class)]);

        $this->tracker->shouldReceive('getChildren')->andReturn([$this->tracker, Mockery::mock(Tracker::class)]);
        $this->tracker->shouldReceive('userCanView')->andReturn(true);

        $this->count_elements_mode_checker->shouldReceive("burnupMustUseCountElementsMode")->once()->andReturn(true);

        $this->http_request->shouldReceive("getBrowser")->andReturn(Mockery::mock(\Browser::class, ["isIE11" => false]));

        $this->project_access_checker->shouldReceive("checkUserCanAccessProject")->once();

        $built_presenter = $this->builder->getProjectMilestonePresenter($this->project, $this->root_planning);

        $this->assertTrue($built_presenter->user_can_view_sub_milestones_planning);
    }

    public function testUserCanSeeSubmilestonePlanningIfDontExist(): void
    {
        $this->mockPlanningTopMilestoneEmpty($this->planning_virtual_top_milestone);

        $this->mockAgiledashboardBacklogFactory($this->agiledashboard_milestone_backlog_factory);

        $this->mockAgiledashboardBacklogItemFactory($this->agiledashboard_milestone_backlog_item_collection_factory);

        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')->andReturn(false)->once();

        $this->agileDashboard_milestone_backlog_item_collection->shouldReceive('count')->once()->andReturn(0);

        $this->mockTimeframe($this->semantic_timeframe);

        $this->planning_milestone_factory
            ->shouldReceive('getAllFutureMilestones')
            ->once()
            ->andReturn([Mockery::mock(Planning_Milestone::class), Mockery::mock(Planning_Milestone::class), Mockery::mock(Planning_Milestone::class)]);

        $this->tracker->shouldReceive('getChildren')->andReturn([]);

        $this->count_elements_mode_checker->shouldReceive("burnupMustUseCountElementsMode")->once()->andReturn(true);

        $this->http_request->shouldReceive("getBrowser")->andReturn(Mockery::mock(\Browser::class, ["isIE11" => false]));

        $this->project_access_checker->shouldReceive("checkUserCanAccessProject")->once();

        $built_presenter = $this->builder->getProjectMilestonePresenter($this->project, $this->root_planning);

        $this->assertFalse($built_presenter->user_can_view_sub_milestones_planning);
    }

    public function testBurnupUseEffortMode(): void
    {
        $this->mockPlanningTopMilestoneEmpty($this->planning_virtual_top_milestone);

        $this->mockAgiledashboardBacklogFactory($this->agiledashboard_milestone_backlog_factory);

        $this->mockAgiledashboardBacklogItemFactory($this->agiledashboard_milestone_backlog_item_collection_factory);

        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')->andReturn(false)->once();

        $this->agileDashboard_milestone_backlog_item_collection->shouldReceive('count')->once()->andReturn(0);

        $this->mockTimeframe($this->semantic_timeframe);

        $this->planning_milestone_factory
            ->shouldReceive('getAllFutureMilestones')
            ->once()
            ->andReturn([Mockery::mock(Planning_Milestone::class), Mockery::mock(Planning_Milestone::class), Mockery::mock(Planning_Milestone::class)]);

        $this->tracker->shouldReceive('getChildren')->andReturn([]);

        $this->count_elements_mode_checker->shouldReceive("burnupMustUseCountElementsMode")->once()->andReturn(false);

        $this->http_request->shouldReceive("getBrowser")->andReturn(Mockery::mock(\Browser::class, ["isIE11" => false]));

        $this->project_access_checker->shouldReceive("checkUserCanAccessProject")->once();

        $built_presenter = $this->builder->getProjectMilestonePresenter($this->project, $this->root_planning);

        $this->assertEquals($built_presenter->burnup_mode, "effort");
    }

    public function testBurnupUseCountMode(): void
    {
        $this->mockPlanningTopMilestoneEmpty($this->planning_virtual_top_milestone);

        $this->mockAgiledashboardBacklogFactory($this->agiledashboard_milestone_backlog_factory);

        $this->mockAgiledashboardBacklogItemFactory($this->agiledashboard_milestone_backlog_item_collection_factory);

        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')->andReturn(false)->once();

        $this->agileDashboard_milestone_backlog_item_collection->shouldReceive('count')->once()->andReturn(0);

        $this->mockTimeframe($this->semantic_timeframe);

        $this->planning_milestone_factory
            ->shouldReceive('getAllFutureMilestones')
            ->once()
            ->andReturn([Mockery::mock(Planning_Milestone::class), Mockery::mock(Planning_Milestone::class), Mockery::mock(Planning_Milestone::class)]);

        $this->tracker->shouldReceive('getChildren')->andReturn([]);

        $this->count_elements_mode_checker->shouldReceive("burnupMustUseCountElementsMode")->once()->andReturn(true);

        $this->http_request->shouldReceive("getBrowser")->andReturn(Mockery::mock(\Browser::class, ["isIE11" => false]));

        $this->project_access_checker->shouldReceive("checkUserCanAccessProject")->once();

        $built_presenter = $this->builder->getProjectMilestonePresenter($this->project, $this->root_planning);

        $this->assertEquals($built_presenter->burnup_mode, "count");
    }

    public function testUserCanSeeTTM(): void
    {
        $this->mockPlanningTopMilestoneEmpty($this->planning_virtual_top_milestone);

        $this->mockAgiledashboardBacklogFactory($this->agiledashboard_milestone_backlog_factory);

        $this->mockAgiledashboardBacklogItemFactory($this->agiledashboard_milestone_backlog_item_collection_factory);

        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')->andReturn(false)->once();

        $this->agileDashboard_milestone_backlog_item_collection->shouldReceive('count')->once()->andReturn(0);

        $this->mockTimeframe($this->semantic_timeframe);

        $this->planning_milestone_factory
            ->shouldReceive('getAllFutureMilestones')
            ->once()
            ->andReturn([Mockery::mock(Planning_Milestone::class), Mockery::mock(Planning_Milestone::class), Mockery::mock(Planning_Milestone::class)]);

        $this->tracker->shouldReceive('getChildren')->andReturn([]);

        $this->count_elements_mode_checker->shouldReceive("burnupMustUseCountElementsMode")->once()->andReturn(true);

        $this->http_request->shouldReceive("getBrowser")->andReturn(Mockery::mock(\Browser::class, ["isIE11" => false]));

        $this->project_access_checker->shouldReceive("checkUserCanAccessProject")->once();

        ForgeConfig::set("project_milestones_activate_ttm", 1);

        $built_presenter = $this->builder->getProjectMilestonePresenter($this->project, $this->root_planning);

        $this->assertTrue($built_presenter->project_milestone_activate_ttm);
    }

    public function testThrowExceptionWhenIsIE11(): void
    {
        $this->http_request->shouldReceive("getBrowser")->andReturn(Mockery::mock(\Browser::class, ["isIE11" => true]));
        $this->expectException(ProjectMilestonesException::class);
        $this->expectExceptionMessage(ProjectMilestonesException::buildBrowserIsIE11()->getTranslatedMessage());
        $this->builder->getProjectMilestonePresenter($this->project, $this->root_planning);
    }

    public function testThrowExceptionWhenUserCantAccessToProject(): void
    {
        $this->http_request->shouldReceive("getBrowser")->andReturn(Mockery::mock(\Browser::class, ["isIE11" => false]));
        $this->project_access_checker->shouldReceive("checkUserCanAccessProject")->once()->andThrow(Project_AccessProjectNotFoundException::class);
        $this->expectException(ProjectMilestonesException::class);
        $this->expectExceptionMessage(ProjectMilestonesException::buildUserNotAccessToProject()->getTranslatedMessage());
        $this->builder->getProjectMilestonePresenter($this->project, $this->root_planning);
    }

    public function testThrowExceptionWhenUserCantAccessToAPrivateProject(): void
    {
        $this->http_request->shouldReceive("getBrowser")->andReturn(Mockery::mock(\Browser::class, ["isIE11" => false]));
        $this->project_access_checker->shouldReceive("checkUserCanAccessProject")->once()->andThrow(\Project_AccessPrivateException::class);
        $this->expectException(ProjectMilestonesException::class);
        $this->expectExceptionMessage(ProjectMilestonesException::buildUserNotAccessToPrivateProject()->getTranslatedMessage());
        $this->builder->getProjectMilestonePresenter($this->project, $this->root_planning);
    }

    public function testThrowExceptionWhenNoProject(): void
    {
        $this->http_request->shouldReceive("getBrowser")->andReturn(Mockery::mock(\Browser::class, ["isIE11" => false]));
        $this->expectException(ProjectMilestonesException::class);
        $this->expectExceptionMessage(ProjectMilestonesException::buildProjectDontExist()->getTranslatedMessage());
        $this->builder->getProjectMilestonePresenter(null, $this->root_planning);
    }

    public function testThrowExceptionWhenNoRootPlanning(): void
    {
        $this->http_request->shouldReceive("getBrowser")->andReturn(Mockery::mock(\Browser::class, ["isIE11" => false]));
        $this->project_access_checker->shouldReceive("checkUserCanAccessProject")->once();
        $this->expectException(ProjectMilestonesException::class);
        $this->expectExceptionMessage(ProjectMilestonesException::buildRootPlanningDontExist()->getTranslatedMessage());
        $this->builder->getProjectMilestonePresenter($this->project, null);

        $this->http_request->shouldReceive("getCurrentUser")->andReturn($this->john_doe);
    }

    private function mockAnArtifact(string $name, string $color)
    {
        $artifact = Mockery::mock(\Artifact::class);
        $artifact->shouldReceive('getName')->once()->andReturn($name);
        $artifact->shouldReceive('getColor')->once()->andReturn(Mockery::mock(TrackerColor::fromName($color)));
        return $artifact;
    }

    private function mockPlanningTopMilestoneEmpty($planning_virtual_top_milestone): void
    {
        $planning_virtual_top_milestone
            ->shouldReceive('getPlanning')
            ->once()
            ->andReturn(Mockery::mock(Planning::class, ['getBacklogTrackersIds' => []]));
    }

    private function mockAgiledashboardBacklogFactory($factory): void
    {
        $factory->shouldReceive('getSelfBacklog')
            ->withArgs([$this->planning_virtual_top_milestone])
            ->andReturn($this->agiledashboard_milestone_backlog)
            ->once();
    }

    private function mockAgiledashboardBacklogItemFactory($agiledashboard_milestone_backlog_item_collection_factory): void
    {
        $agiledashboard_milestone_backlog_item_collection_factory->shouldReceive('getUnassignedOpenCollection')
            ->withArgs([$this->john_doe, $this->planning_virtual_top_milestone, $this->agiledashboard_milestone_backlog, false])
            ->andReturn($this->agileDashboard_milestone_backlog_item_collection)
            ->once();
    }

    private function mockTimeframe($semantic_timeframe): void
    {
        $semantic_timeframe->shouldReceive('getDurationField')->andReturn(Mockery::mock(\Tracker_FormElement_Field_Numeric::class, ['getLabel' => 'duration']));
        $semantic_timeframe->shouldReceive('getStartDateField')->andReturn(Mockery::mock(\Tracker_FormElement_Field_Date::class, ['getLabel' => 'start']));
        $semantic_timeframe->shouldReceive('getEndDateField')->andReturn(Mockery::mock(\Tracker_FormElement_Field_Date::class, ['getLabel' => 'end']));
    }
}
