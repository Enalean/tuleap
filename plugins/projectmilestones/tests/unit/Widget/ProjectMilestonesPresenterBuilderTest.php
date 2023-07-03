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
use PFUser;
use Planning;
use Planning_Milestone;
use Planning_MilestoneFactory;
use Planning_VirtualTopMilestone;
use Project;
use Tracker;
use Tuleap\AgileDashboard\ExplicitBacklog\ArtifactsInExplicitBacklogDao;
use Tuleap\AgileDashboard\ExplicitBacklog\ExplicitBacklogDao;
use Tuleap\AgileDashboard\FormElement\Burnup\CountElementsModeChecker;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframe;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeBrokenConfigurationException;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\TrackerColor;
use Tuleap\Project\ProjectAccessChecker;
use Project_AccessProjectNotFoundException;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use AgileDashboardPlugin;

final class ProjectMilestonesPresenterBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ProjectMilestonesPresenterBuilder $builder;
    private \PHPUnit\Framework\MockObject\MockObject&HTTPRequest $http_request;
    private Planning_MilestoneFactory&\PHPUnit\Framework\MockObject\MockObject $planning_milestone_factory;
    private Project&\PHPUnit\Framework\MockObject\MockObject $project;
    private \PHPUnit\Framework\MockObject\MockObject&PFUser $john_doe;
    private Planning_VirtualTopMilestone&\PHPUnit\Framework\MockObject\MockObject $planning_virtual_top_milestone;
    private AgileDashboard_Milestone_Backlog_IBacklogItemCollection&\PHPUnit\Framework\MockObject\MockObject $agileDashboard_milestone_backlog_item_collection;
    private AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory&\PHPUnit\Framework\MockObject\MockObject $agiledashboard_milestone_backlog_item_collection_factory;
    private \PHPUnit\Framework\MockObject\MockObject&AgileDashboard_Milestone_Backlog_BacklogFactory $agiledashboard_milestone_backlog_factory;
    private AgileDashboard_Milestone_Backlog_Backlog&\PHPUnit\Framework\MockObject\MockObject $agiledashboard_milestone_backlog;
    private ExplicitBacklogDao&\PHPUnit\Framework\MockObject\MockObject $explicit_backlog_dao;
    private \PHPUnit\Framework\MockObject\MockObject&ArtifactsInExplicitBacklogDao $artifacts_in_explicit_backlog_dao;
    private Planning&\PHPUnit\Framework\MockObject\MockObject $root_planning;
    private Tracker&\PHPUnit\Framework\MockObject\MockObject $tracker;
    private \PHPUnit\Framework\MockObject\MockObject&SemanticTimeframeBuilder $semantic_timeframe_builder;
    private SemanticTimeframe&\PHPUnit\Framework\MockObject\MockObject $semantic_timeframe;
    private CountElementsModeChecker&\PHPUnit\Framework\MockObject\MockObject $count_elements_mode_checker;
    private ProjectAccessChecker&\PHPUnit\Framework\MockObject\MockObject $project_access_checker;

    public function setUp(): void
    {
        parent::setUp();
        $this->http_request                                             = $this->createMock(HTTPRequest::class);
        $this->planning_milestone_factory                               = $this->createMock(Planning_MilestoneFactory::class);
        $this->project                                                  = $this->createMock(Project::class);
        $this->john_doe                                                 = $this->createMock(PFUser::class);
        $this->planning_virtual_top_milestone                           = $this->createMock(Planning_VirtualTopMilestone::class);
        $this->agileDashboard_milestone_backlog_item_collection         = $this->createMock(AgileDashboard_Milestone_Backlog_IBacklogItemCollection::class);
        $this->agiledashboard_milestone_backlog_item_collection_factory = $this->createMock(AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory::class);
        $this->agiledashboard_milestone_backlog_factory                 = $this->createMock(AgileDashboard_Milestone_Backlog_BacklogFactory::class);
        $this->agiledashboard_milestone_backlog                         = $this->createMock(AgileDashboard_Milestone_Backlog_Backlog::class);
        $this->explicit_backlog_dao                                     = $this->createMock(ExplicitBacklogDao::class);
        $this->artifacts_in_explicit_backlog_dao                        = $this->createMock(ArtifactsInExplicitBacklogDao::class);
        $this->root_planning                                            = $this->createMock(Planning::class);
        $this->tracker                                                  = $this->createMock(Tracker::class);
        $this->semantic_timeframe_builder                               = $this->createMock(SemanticTimeframeBuilder::class);
        $this->semantic_timeframe                                       = $this->createMock(SemanticTimeframe::class);
        $this->count_elements_mode_checker                              = $this->createMock(CountElementsModeChecker::class);
        $this->project_access_checker                                   = $this->createMock(ProjectAccessChecker::class);

        $this->project->method('getID')->willReturn(101);
        $this->project->method('getPublicName')->willReturn('My Project');

        $this->root_planning->method('getPlanningTracker')->willReturn($this->tracker);
        $this->tracker->method('getName')->willReturn("Releases");

        $this->http_request->method('getProject')->willReturn($this->project);
        $this->http_request->method('getCurrentUser')->willReturn($this->john_doe);

        $this->planning_milestone_factory
            ->method('getVirtualTopMilestone')
            ->with($this->john_doe, $this->project)
            ->willReturn($this->planning_virtual_top_milestone);

        $this->semantic_timeframe_builder->method('getSemantic')->willReturn($this->semantic_timeframe);

        $this->builder = new ProjectMilestonesPresenterBuilder(
            $this->http_request,
            $this->agiledashboard_milestone_backlog_factory,
            $this->agiledashboard_milestone_backlog_item_collection_factory,
            $this->planning_milestone_factory,
            $this->explicit_backlog_dao,
            $this->artifacts_in_explicit_backlog_dao,
            $this->semantic_timeframe_builder,
            $this->count_elements_mode_checker,
            $this->project_access_checker
        );
    }

    public function testGetZeroUpcomingReleaseWhenThereAreNoFutureMilestone(): void
    {
        $this->mockMilestoneBacklog();

        $this->mockAgiledashboardBacklogFactory($this->agiledashboard_milestone_backlog_factory);

        $this->mockAgiledashboardBacklogItemFactory($this->agiledashboard_milestone_backlog_item_collection_factory);

        $this->explicit_backlog_dao->expects(self::once())->method('isProjectUsingExplicitBacklog')->willReturn(false);

        $this->agileDashboard_milestone_backlog_item_collection->expects(self::once())->method('count')->willReturn(5);

        $this->planning_milestone_factory->expects(self::once())->method('getAllFutureMilestones')->willReturn([]);

        $this->mockTimeframe($this->semantic_timeframe);

        $this->tracker->method('getChildren')->willReturn([$this->tracker]);
        $this->tracker->method('userCanView')->willReturn(true);

        $this->count_elements_mode_checker->expects(self::once())->method("burnupMustUseCountElementsMode")->willReturn(true);

        $this->http_request->method('getFromServer')->willReturn('Some user-agent string');

        $this->project_access_checker->expects(self::once())->method("checkUserCanAccessProject");

        $this->project->expects(self::once())->method('usesService')->with(AgileDashboardPlugin::PLUGIN_SHORTNAME)->willReturn(true);

        $built_presenter = $this->builder->getProjectMilestonePresenter($this->project, $this->root_planning);

        self::assertEquals(0, $built_presenter->nb_upcoming_releases);
    }

    public function testGetUpcomingReleasesWhenThereAreFutureMilestones(): void
    {
        $this->mockMilestoneBacklog();

        $this->mockAgiledashboardBacklogFactory($this->agiledashboard_milestone_backlog_factory);

        $this->mockAgiledashboardBacklogItemFactory($this->agiledashboard_milestone_backlog_item_collection_factory);

        $this->explicit_backlog_dao->expects(self::once())->method('isProjectUsingExplicitBacklog')->willReturn(false);

        $this->agileDashboard_milestone_backlog_item_collection->expects(self::once())->method('count')->willReturn(5);

        $this->mockTimeframe($this->semantic_timeframe);

        $this->planning_milestone_factory
            ->expects(self::once())
            ->method('getAllFutureMilestones')
            ->willReturn([$this->createMock(Planning_Milestone::class), $this->createMock(Planning_Milestone::class), $this->createMock(Planning_Milestone::class)]);

        $this->tracker->method('getChildren')->willReturn([$this->tracker]);
        $this->tracker->method('userCanView')->willReturn(true);

        $this->count_elements_mode_checker->expects(self::once())->method("burnupMustUseCountElementsMode")->willReturn(true);

        $this->http_request->method('getFromServer')->willReturn('Some user-agent string');

        $this->project_access_checker->expects(self::once())->method("checkUserCanAccessProject");

        $this->project->expects(self::once())->method('usesService')->with(AgileDashboardPlugin::PLUGIN_SHORTNAME)->willReturn(true);

        $built_presenter = $this->builder->getProjectMilestonePresenter($this->project, $this->root_planning);

        self::assertEquals(3, $built_presenter->nb_upcoming_releases);
    }

    public function testGetNumberBacklogItem(): void
    {
        $this->mockMilestoneBacklog();

        $this->mockAgiledashboardBacklogFactory($this->agiledashboard_milestone_backlog_factory);

        $this->mockAgiledashboardBacklogItemFactory($this->agiledashboard_milestone_backlog_item_collection_factory);

        $this->explicit_backlog_dao->expects(self::once())->method('isProjectUsingExplicitBacklog')->willReturn(false);

        $this->agileDashboard_milestone_backlog_item_collection->method('count')->willReturn(5);

        $this->mockTimeframe($this->semantic_timeframe);

        $this->planning_milestone_factory
            ->expects(self::once())
            ->method('getAllFutureMilestones')
            ->willReturn([$this->createMock(Planning_Milestone::class), $this->createMock(Planning_Milestone::class), $this->createMock(Planning_Milestone::class)]);

        $this->tracker->method('getChildren')->willReturn([$this->tracker]);
        $this->tracker->method('userCanView')->willReturn(true);

        $this->count_elements_mode_checker->expects(self::once())->method("burnupMustUseCountElementsMode")->willReturn(true);

        $this->http_request->method('getFromServer')->willReturn('Some user-agent string');

        $this->project_access_checker->expects(self::once())->method("checkUserCanAccessProject");

        $this->project->expects(self::once())->method('usesService')->with(AgileDashboardPlugin::PLUGIN_SHORTNAME)->willReturn(true);

        $built_presenter = $this->builder->getProjectMilestonePresenter($this->project, $this->root_planning);

        self::assertEquals(5, $built_presenter->nb_backlog_items);
    }

    public function testGetTrackersId(): void
    {
        $this->agiledashboard_milestone_backlog_factory->expects(self::once())->method('getBacklog')->willReturn($this->agiledashboard_milestone_backlog);
        $this->agiledashboard_milestone_backlog
            ->expects(self::once())
            ->method('getDescendantTrackers')
            ->willReturn([$this->aTracker(122, 'Bug', 'fiesta-red'), $this->aTracker(124, 'Story', 'deep-blue')]);

        $this->mockAgiledashboardBacklogFactory($this->agiledashboard_milestone_backlog_factory);

        $this->mockAgiledashboardBacklogItemFactory($this->agiledashboard_milestone_backlog_item_collection_factory);

        $this->explicit_backlog_dao->expects(self::once())->method('isProjectUsingExplicitBacklog')->willReturn(false);

        $this->agileDashboard_milestone_backlog_item_collection->expects(self::once())->method('count')->willReturn(0);

        $this->mockTimeframe($this->semantic_timeframe);

        $this->planning_milestone_factory
            ->expects(self::once())
            ->method('getAllFutureMilestones')
            ->willReturn([$this->createMock(Planning_Milestone::class), $this->createMock(Planning_Milestone::class), $this->createMock(Planning_Milestone::class)]);

        $this->tracker->method('getChildren')->willReturn([$this->tracker]);
        $this->tracker->method('userCanView')->willReturn(true);

        $this->count_elements_mode_checker->expects(self::once())->method("burnupMustUseCountElementsMode")->willReturn(true);

        $this->http_request->method('getFromServer')->willReturn('Some user-agent string');

        $this->project_access_checker->expects(self::once())->method("checkUserCanAccessProject");

        $this->project->expects(self::once())->method('usesService')->with(AgileDashboardPlugin::PLUGIN_SHORTNAME)->willReturn(true);

        $built_presenter = $this->builder->getProjectMilestonePresenter($this->project, $this->root_planning);
        $tracker_json    = '[{"id":122,"color_name":"fiesta-red","label":"Bug"},{"id":124,"color_name":"deep-blue","label":"Story"}]';

        self::assertEqualsCanonicalizing($tracker_json, $built_presenter->json_trackers_agile_dashboard);
    }

    public function testGetNumberItemsOfExplicitBacklogAndNotTopBacklog(): void
    {
        $this->mockMilestoneBacklog();

        $this->agiledashboard_milestone_backlog_factory
            ->expects(self::never())
            ->method('getSelfBacklog')
            ->with($this->planning_virtual_top_milestone);

        $this->agiledashboard_milestone_backlog_item_collection_factory
            ->expects(self::never())
            ->method('getUnassignedOpenCollection')
            ->willReturn($this->agileDashboard_milestone_backlog_item_collection);

        $this->explicit_backlog_dao->expects(self::once())->method('isProjectUsingExplicitBacklog')->willReturn(true);

        $this->artifacts_in_explicit_backlog_dao->expects(self::once())->method('getNumberOfItemsInExplicitBacklog')->willReturn(50);

        $this->agileDashboard_milestone_backlog_item_collection->method('count')->willReturn(5);

        $this->mockTimeframe($this->semantic_timeframe);

        $this->planning_milestone_factory
            ->expects(self::once())
            ->method('getAllFutureMilestones')
            ->willReturn([$this->createMock(Planning_Milestone::class), $this->createMock(Planning_Milestone::class), $this->createMock(Planning_Milestone::class)]);

        $this->tracker->method('getChildren')->willReturn([$this->tracker]);
        $this->tracker->method('userCanView')->willReturn(true);

        $this->count_elements_mode_checker->expects(self::once())->method("burnupMustUseCountElementsMode")->willReturn(true);

        $this->http_request->method('getFromServer')->willReturn('Some user-agent string');

        $this->project_access_checker->expects(self::once())->method("checkUserCanAccessProject");

        $this->project->expects(self::once())->method('usesService')->with(AgileDashboardPlugin::PLUGIN_SHORTNAME)->willReturn(true);

        $built_presenter = $this->builder->getProjectMilestonePresenter($this->project, $this->root_planning);

        self::assertEquals(50, $built_presenter->nb_backlog_items);
    }

    public function testGetLabelOfTrackerPlanning(): void
    {
        $this->mockMilestoneBacklog();

        $this->mockAgiledashboardBacklogFactory($this->agiledashboard_milestone_backlog_factory);

        $this->mockAgiledashboardBacklogItemFactory($this->agiledashboard_milestone_backlog_item_collection_factory);

        $this->explicit_backlog_dao->expects(self::once())->method('isProjectUsingExplicitBacklog')->willReturn(false);

        $this->agileDashboard_milestone_backlog_item_collection->expects(self::once())->method('count')->willReturn(5);
        $this->mockTimeframe($this->semantic_timeframe);

        $this->planning_milestone_factory
            ->expects(self::once())
            ->method('getAllFutureMilestones')
            ->willReturn([$this->createMock(Planning_Milestone::class), $this->createMock(Planning_Milestone::class), $this->createMock(Planning_Milestone::class)]);

        $this->tracker->method('getChildren')->willReturn([$this->tracker]);
        $this->tracker->method('userCanView')->willReturn(true);

        $this->count_elements_mode_checker->expects(self::once())->method("burnupMustUseCountElementsMode")->willReturn(true);

        $this->http_request->method('getFromServer')->willReturn('Some user-agent string');

        $this->project_access_checker->expects(self::once())->method("checkUserCanAccessProject");

        $this->project->expects(self::once())->method('usesService')->with(AgileDashboardPlugin::PLUGIN_SHORTNAME)->willReturn(true);

        $built_presenter = $this->builder->getProjectMilestonePresenter($this->project, $this->root_planning);

        self::assertEquals('Releases', $built_presenter->label_tracker_planning);
    }

    public function testIsNotTimeframeDurationField(): void
    {
        $this->mockMilestoneBacklog();

        $this->mockAgiledashboardBacklogFactory($this->agiledashboard_milestone_backlog_factory);

        $this->mockAgiledashboardBacklogItemFactory($this->agiledashboard_milestone_backlog_item_collection_factory);

        $this->explicit_backlog_dao->expects(self::once())->method('isProjectUsingExplicitBacklog')->willReturn(false);

        $this->agileDashboard_milestone_backlog_item_collection->expects(self::once())->method('count')->willReturn(0);

        $this->semantic_timeframe->method('getDurationField')->willReturn(null);
        $start = $this->createMock(\Tracker_FormElement_Field_Date::class);
        $start->method('getLabel')->willReturn('start');
        $this->semantic_timeframe->method('getStartDateField')->willReturn($start);
        $end = $this->createMock(\Tracker_FormElement_Field_Date::class);
        $end->method('getLabel')->willReturn('end');
        $this->semantic_timeframe->method('getEndDateField')->willReturn($end);

        $this->planning_milestone_factory
            ->expects(self::once())
            ->method('getAllFutureMilestones')
            ->willReturn([$this->createMock(Planning_Milestone::class), $this->createMock(Planning_Milestone::class), $this->createMock(Planning_Milestone::class)]);

        $this->tracker->method('getChildren')->willReturn([$this->tracker]);
        $this->tracker->method('userCanView')->willReturn(true);

        $this->count_elements_mode_checker->expects(self::once())->method("burnupMustUseCountElementsMode")->willReturn(true);

        $this->http_request->method('getFromServer')->willReturn('Some user-agent string');

        $this->project_access_checker->expects(self::once())->method("checkUserCanAccessProject");

        $this->project->expects(self::once())->method('usesService')->with(AgileDashboardPlugin::PLUGIN_SHORTNAME)->willReturn(true);

        $built_presenter = $this->builder->getProjectMilestonePresenter($this->project, $this->root_planning);

        self::assertFalse($built_presenter->is_timeframe_duration);
        self::assertEquals($built_presenter->label_timeframe, 'end');
    }

    public function testIsNotTimeframeEndDateField(): void
    {
        $this->mockMilestoneBacklog();

        $this->mockAgiledashboardBacklogFactory($this->agiledashboard_milestone_backlog_factory);

        $this->mockAgiledashboardBacklogItemFactory($this->agiledashboard_milestone_backlog_item_collection_factory);

        $this->explicit_backlog_dao->expects(self::once())->method('isProjectUsingExplicitBacklog')->willReturn(false);

        $this->agileDashboard_milestone_backlog_item_collection->expects(self::once())->method('count')->willReturn(0);

        $duration = $this->createMock(\Tracker_FormElement_Field_Numeric::class);
        $duration->method('getLabel')->willReturn('duration');
        $this->semantic_timeframe->method('getDurationField')->willReturn($duration);
        $start = $this->createMock(\Tracker_FormElement_Field_Date::class);
        $start->method('getLabel')->willReturn('start');
        $this->semantic_timeframe->method('getStartDateField')->willReturn($start);
        $this->semantic_timeframe->method('getEndDateField')->willReturn(null);

        $this->planning_milestone_factory
            ->expects(self::once())
            ->method('getAllFutureMilestones')
            ->willReturn([$this->createMock(Planning_Milestone::class), $this->createMock(Planning_Milestone::class), $this->createMock(Planning_Milestone::class)]);

        $this->tracker->method('getChildren')->willReturn([$this->tracker]);
        $this->tracker->method('userCanView')->willReturn(true);

        $this->count_elements_mode_checker->expects(self::once())->method("burnupMustUseCountElementsMode")->willReturn(true);

        $this->http_request->method('getFromServer')->willReturn('Some user-agent string');

        $this->project_access_checker->expects(self::once())->method("checkUserCanAccessProject");

        $this->project->expects(self::once())->method('usesService')->with(AgileDashboardPlugin::PLUGIN_SHORTNAME)->willReturn(true);

        $built_presenter = $this->builder->getProjectMilestonePresenter($this->project, $this->root_planning);

        self::assertTrue($built_presenter->is_timeframe_duration);
        self::assertEquals($built_presenter->label_timeframe, 'duration');
    }

    public function testThrowExceptionWhenNoTimeframeEndDateAndDurationField(): void
    {
        $this->mockMilestoneBacklog();

        $this->mockAgiledashboardBacklogFactory($this->agiledashboard_milestone_backlog_factory);

        $this->mockAgiledashboardBacklogItemFactory($this->agiledashboard_milestone_backlog_item_collection_factory);

        $this->explicit_backlog_dao->expects(self::once())->method('isProjectUsingExplicitBacklog')->willReturn(false);

        $this->agileDashboard_milestone_backlog_item_collection->expects(self::once())->method('count')->willReturn(0);

        $this->semantic_timeframe->method('getDurationField')->willReturn(null);
        $start = $this->createMock(\Tracker_FormElement_Field_Date::class);
        $start->method('getLabel')->willReturn('start');
        $this->semantic_timeframe->method('getStartDateField')->willReturn($start);
        $this->semantic_timeframe->method('getEndDateField')->willReturn(null);
        $this->semantic_timeframe->expects(self::once())->method('getTracker')->willReturn(TrackerTestBuilder::aTracker()->withId(100)->build());

        $this->planning_milestone_factory
            ->expects(self::once())
            ->method('getAllFutureMilestones')
            ->willReturn([$this->createMock(Planning_Milestone::class), $this->createMock(Planning_Milestone::class), $this->createMock(Planning_Milestone::class)]);

        $this->tracker->method('getChildren')->willReturn([$this->tracker]);
        $this->tracker->method('userCanView')->willReturn(true);

        $this->http_request->method('getFromServer')->willReturn('Some user-agent string');

        $this->project_access_checker->expects(self::once())->method("checkUserCanAccessProject");

        $this->project->expects(self::once())->method('usesService')->with(AgileDashboardPlugin::PLUGIN_SHORTNAME)->willReturn(true);

        $this->expectException(TimeframeBrokenConfigurationException::class);
        $this->builder->getProjectMilestonePresenter($this->project, $this->root_planning);
    }

    public function testGetIfUserCanViewSubMilestoneTracker(): void
    {
        $this->mockMilestoneBacklog();

        $this->mockAgiledashboardBacklogFactory($this->agiledashboard_milestone_backlog_factory);

        $this->mockAgiledashboardBacklogItemFactory($this->agiledashboard_milestone_backlog_item_collection_factory);

        $this->explicit_backlog_dao->expects(self::once())->method('isProjectUsingExplicitBacklog')->willReturn(false);

        $this->agileDashboard_milestone_backlog_item_collection->expects(self::once())->method('count')->willReturn(0);

        $this->mockTimeframe($this->semantic_timeframe);

        $this->planning_milestone_factory
            ->expects(self::once())
            ->method('getAllFutureMilestones')
            ->willReturn([$this->createMock(Planning_Milestone::class), $this->createMock(Planning_Milestone::class), $this->createMock(Planning_Milestone::class)]);

        $this->tracker->method('getChildren')->willReturn([$this->tracker]);
        $this->tracker->method('userCanView')->willReturn(false);

        $this->count_elements_mode_checker->expects(self::once())->method("burnupMustUseCountElementsMode")->willReturn(true);

        $this->http_request->method('getFromServer')->willReturn('Some user-agent string');

        $this->project_access_checker->expects(self::once())->method("checkUserCanAccessProject");

        $this->project->expects(self::once())->method('usesService')->with(AgileDashboardPlugin::PLUGIN_SHORTNAME)->willReturn(true);

        $built_presenter = $this->builder->getProjectMilestonePresenter($this->project, $this->root_planning);

        self::assertFalse($built_presenter->user_can_view_sub_milestones_planning);
    }

    public function testGetTheFirstSubmilestonePlanningLikeInAD(): void
    {
        $this->mockMilestoneBacklog();

        $this->mockAgiledashboardBacklogFactory($this->agiledashboard_milestone_backlog_factory);

        $this->mockAgiledashboardBacklogItemFactory($this->agiledashboard_milestone_backlog_item_collection_factory);

        $this->explicit_backlog_dao->expects(self::once())->method('isProjectUsingExplicitBacklog')->willReturn(false);

        $this->agileDashboard_milestone_backlog_item_collection->expects(self::once())->method('count')->willReturn(0);

        $this->mockTimeframe($this->semantic_timeframe);

        $this->planning_milestone_factory
            ->expects(self::once())
            ->method('getAllFutureMilestones')
            ->willReturn([$this->createMock(Planning_Milestone::class), $this->createMock(Planning_Milestone::class), $this->createMock(Planning_Milestone::class)]);

        $this->tracker->method('getChildren')->willReturn([$this->tracker, $this->createMock(Tracker::class)]);
        $this->tracker->method('userCanView')->willReturn(true);

        $this->count_elements_mode_checker->expects(self::once())->method("burnupMustUseCountElementsMode")->willReturn(true);

        $this->http_request->method('getFromServer')->willReturn('Some user-agent string');

        $this->project_access_checker->expects(self::once())->method("checkUserCanAccessProject");

        $this->project->expects(self::once())->method('usesService')->with(AgileDashboardPlugin::PLUGIN_SHORTNAME)->willReturn(true);

        $built_presenter = $this->builder->getProjectMilestonePresenter($this->project, $this->root_planning);

        self::assertTrue($built_presenter->user_can_view_sub_milestones_planning);
    }

    public function testUserCanSeeSubmilestonePlanningIfDontExist(): void
    {
        $this->mockMilestoneBacklog();

        $this->mockAgiledashboardBacklogFactory($this->agiledashboard_milestone_backlog_factory);

        $this->mockAgiledashboardBacklogItemFactory($this->agiledashboard_milestone_backlog_item_collection_factory);

        $this->explicit_backlog_dao->expects(self::once())->method('isProjectUsingExplicitBacklog')->willReturn(false);

        $this->agileDashboard_milestone_backlog_item_collection->expects(self::once())->method('count')->willReturn(0);

        $this->mockTimeframe($this->semantic_timeframe);

        $this->planning_milestone_factory
            ->expects(self::once())
            ->method('getAllFutureMilestones')
            ->willReturn([$this->createMock(Planning_Milestone::class), $this->createMock(Planning_Milestone::class), $this->createMock(Planning_Milestone::class)]);

        $this->tracker->method('getChildren')->willReturn([]);

        $this->count_elements_mode_checker->expects(self::once())->method("burnupMustUseCountElementsMode")->willReturn(true);

        $this->http_request->method('getFromServer')->willReturn('Some user-agent string');

        $this->project_access_checker->expects(self::once())->method("checkUserCanAccessProject");

        $this->project->expects(self::once())->method('usesService')->with(AgileDashboardPlugin::PLUGIN_SHORTNAME)->willReturn(true);

        $built_presenter = $this->builder->getProjectMilestonePresenter($this->project, $this->root_planning);

        self::assertFalse($built_presenter->user_can_view_sub_milestones_planning);
    }

    public function testBurnupUseEffortMode(): void
    {
        $this->mockMilestoneBacklog();

        $this->mockAgiledashboardBacklogFactory($this->agiledashboard_milestone_backlog_factory);

        $this->mockAgiledashboardBacklogItemFactory($this->agiledashboard_milestone_backlog_item_collection_factory);

        $this->explicit_backlog_dao->expects(self::once())->method('isProjectUsingExplicitBacklog')->willReturn(false);

        $this->agileDashboard_milestone_backlog_item_collection->expects(self::once())->method('count')->willReturn(0);

        $this->mockTimeframe($this->semantic_timeframe);

        $this->planning_milestone_factory
            ->expects(self::once())
            ->method('getAllFutureMilestones')
            ->willReturn([$this->createMock(Planning_Milestone::class), $this->createMock(Planning_Milestone::class), $this->createMock(Planning_Milestone::class)]);

        $this->tracker->method('getChildren')->willReturn([]);

        $this->count_elements_mode_checker->expects(self::once())->method("burnupMustUseCountElementsMode")->willReturn(false);

        $this->http_request->method('getFromServer')->willReturn('Some user-agent string');

        $this->project_access_checker->expects(self::once())->method("checkUserCanAccessProject");

        $this->project->expects(self::once())->method('usesService')->with(AgileDashboardPlugin::PLUGIN_SHORTNAME)->willReturn(true);

        $built_presenter = $this->builder->getProjectMilestonePresenter($this->project, $this->root_planning);

        self::assertEquals($built_presenter->burnup_mode, "effort");
    }

    public function testBurnupUseCountMode(): void
    {
        $this->mockMilestoneBacklog();

        $this->mockAgiledashboardBacklogFactory($this->agiledashboard_milestone_backlog_factory);

        $this->mockAgiledashboardBacklogItemFactory($this->agiledashboard_milestone_backlog_item_collection_factory);

        $this->explicit_backlog_dao->expects(self::once())->method('isProjectUsingExplicitBacklog')->willReturn(false);

        $this->agileDashboard_milestone_backlog_item_collection->expects(self::once())->method('count')->willReturn(0);

        $this->mockTimeframe($this->semantic_timeframe);

        $this->planning_milestone_factory
            ->expects(self::once())
            ->method('getAllFutureMilestones')
            ->willReturn([$this->createMock(Planning_Milestone::class), $this->createMock(Planning_Milestone::class), $this->createMock(Planning_Milestone::class)]);

        $this->tracker->method('getChildren')->willReturn([]);

        $this->count_elements_mode_checker->expects(self::once())->method("burnupMustUseCountElementsMode")->willReturn(true);

        $this->http_request->method('getFromServer')->willReturn('Some user-agent string');

        $this->project_access_checker->expects(self::once())->method("checkUserCanAccessProject");

        $this->project->expects(self::once())->method('usesService')->with(AgileDashboardPlugin::PLUGIN_SHORTNAME)->willReturn(true);

        $built_presenter = $this->builder->getProjectMilestonePresenter($this->project, $this->root_planning);

        self::assertEquals($built_presenter->burnup_mode, "count");
    }

    public function testThrowExceptionWhenUserCantAccessToProject(): void
    {
        $this->http_request->method('getFromServer')->willReturn('Some user-agent string');
        $this->project_access_checker->expects(self::once())->method("checkUserCanAccessProject")->willThrowException(new Project_AccessProjectNotFoundException());
        $this->expectException(ProjectMilestonesException::class);
        $this->expectExceptionMessage(ProjectMilestonesException::buildUserNotAccessToProject()->getTranslatedMessage());
        $this->builder->getProjectMilestonePresenter($this->project, $this->root_planning);
    }

    public function testThrowExceptionWhenUserCantAccessToAPrivateProject(): void
    {
        $this->http_request->method('getFromServer')->willReturn('Some user-agent string');
        $this->project_access_checker->expects(self::once())->method("checkUserCanAccessProject")->willThrowException(new \Project_AccessPrivateException());
        $this->expectException(ProjectMilestonesException::class);
        $this->expectExceptionMessage(ProjectMilestonesException::buildUserNotAccessToPrivateProject()->getTranslatedMessage());
        $this->builder->getProjectMilestonePresenter($this->project, $this->root_planning);
    }

    public function testThrowExceptionWhenNoProject(): void
    {
        $this->http_request->method('getFromServer')->willReturn('Some user-agent string');
        $this->expectException(ProjectMilestonesException::class);
        $this->expectExceptionMessage(ProjectMilestonesException::buildProjectDontExist()->getTranslatedMessage());
        $this->builder->getProjectMilestonePresenter(null, $this->root_planning);
    }

    public function testThrowExceptionWhenNoRootPlanning(): void
    {
        $this->http_request->method('getFromServer')->willReturn('Some user-agent string');
        $this->project_access_checker->expects(self::once())->method("checkUserCanAccessProject");
        $this->project->expects(self::once())->method('usesService')->with(AgileDashboardPlugin::PLUGIN_SHORTNAME)->willReturn(true);
        $this->expectException(ProjectMilestonesException::class);
        $this->expectExceptionMessage(ProjectMilestonesException::buildRootPlanningDontExist()->getTranslatedMessage());
        $this->builder->getProjectMilestonePresenter($this->project, null);

        $this->http_request->method("getCurrentUser")->willReturn($this->john_doe);
    }

    public function testThrowExceptionWhenNoServiceAgileDashboard(): void
    {
        $this->http_request->method('getFromServer')->willReturn('Some user-agent string');
        $this->project_access_checker->expects(self::once())->method("checkUserCanAccessProject");
        $this->project->expects(self::once())->method('usesService')->with(AgileDashboardPlugin::PLUGIN_SHORTNAME)->willReturn(false);
        $this->expectException(ProjectMilestonesException::class);
        $this->expectExceptionMessage(ProjectMilestonesException::buildNoAgileDashboardPlugin()->getTranslatedMessage());
        $this->builder->getProjectMilestonePresenter($this->project, $this->root_planning);

        $this->http_request->method("getCurrentUser")->willReturn($this->john_doe);
    }

    private function aTracker(int $id, string $name, string $color): Tracker
    {
        return TrackerTestBuilder::aTracker()->withId($id)->withName($name)->withColor(TrackerColor::fromName($color))->build();
    }

    private function mockMilestoneBacklog(): void
    {
        $this->agiledashboard_milestone_backlog_factory->expects(self::once())->method('getBacklog')->willReturn($this->agiledashboard_milestone_backlog);
        $this->agiledashboard_milestone_backlog->expects(self::once())->method('getDescendantTrackers')->willReturn([]);
    }

    private function mockAgiledashboardBacklogFactory(\PHPUnit\Framework\MockObject\MockObject&AgileDashboard_Milestone_Backlog_BacklogFactory $factory): void
    {
        $factory->expects(self::once())->method('getSelfBacklog')
            ->with($this->planning_virtual_top_milestone)
            ->willReturn($this->agiledashboard_milestone_backlog);
    }

    private function mockAgiledashboardBacklogItemFactory(
        AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory&\PHPUnit\Framework\MockObject\MockObject $agiledashboard_milestone_backlog_item_collection_factory,
    ): void {
        $agiledashboard_milestone_backlog_item_collection_factory->expects(self::once())->method('getUnassignedOpenCollection')
            ->with($this->john_doe, $this->planning_virtual_top_milestone, $this->agiledashboard_milestone_backlog, false)
            ->willReturn($this->agileDashboard_milestone_backlog_item_collection);
    }

    private function mockTimeframe(SemanticTimeframe&\PHPUnit\Framework\MockObject\MockObject $semantic_timeframe): void
    {
        $duration = $this->createMock(\Tracker_FormElement_Field_Numeric::class);
        $duration->method('getLabel')->willReturn('duration');

        $start = $this->createMock(\Tracker_FormElement_Field_Date::class);
        $start->method('getLabel')->willReturn('start');

        $end = $this->createMock(\Tracker_FormElement_Field_Date::class);
        $end->method('getLabel')->willReturn('end');

        $semantic_timeframe->method('getDurationField')->willReturn($duration);
        $semantic_timeframe->method('getStartDateField')->willReturn($start);
        $semantic_timeframe->method('getEndDateField')->willReturn($end);
    }
}
