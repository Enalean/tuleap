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

namespace Tuleap\ReleaseWidget\Widget;

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
use PlanningFactory;
use Project;
use TrackerFactory;
use Tuleap\Tracker\TrackerColor;

class ProjectReleasePresenterBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var HTTPRequest|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $http_request;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PlanningFactory
     */
    private $planning_factory;
    /**
     * @var ProjectReleasePresenterBuilder
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

    public function setUp(): void
    {
        parent::setUp();
        $this->http_request                                             = Mockery::mock(HTTPRequest::class);
        $this->planning_factory                                         = Mockery::mock(PlanningFactory::class);
        $this->planning_milestone_factory                               = Mockery::mock(Planning_MilestoneFactory::class);
        $this->project                                                  = Mockery::mock(Project::class, ['getID' => 101]);
        $this->john_doe                                                 = Mockery::mock(PFUser::class);
        $this->planning_virtual_top_milestone                           = Mockery::mock(Planning_VirtualTopMilestone::class);
        $this->agileDashboard_milestone_backlog_item_collection         = Mockery::mock(AgileDashboard_Milestone_Backlog_IBacklogItemCollection::class);
        $this->agiledashboard_milestone_backlog_item_collection_factory = Mockery::mock(AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory::class);
        $this->agiledashboard_milestone_backlog_factory                 = Mockery::mock(AgileDashboard_Milestone_Backlog_BacklogFactory::class);
        $this->agiledashboard_milestone_backlog                         = Mockery::mock(AgileDashboard_Milestone_Backlog_Backlog::class);
        $this->tracker_factory                                          = Mockery::mock(TrackerFactory::class);

        $this->http_request->shouldReceive('getProject')->andReturn($this->project);
        $this->http_request->shouldReceive('getCurrentUser')->andReturn($this->john_doe);

        $this->planning_milestone_factory
            ->shouldReceive('getVirtualTopMilestone')
            ->once()
            ->withArgs([$this->john_doe, $this->project])
            ->andReturn($this->planning_virtual_top_milestone);

        $this->builder = new ProjectReleasePresenterBuilder(
            $this->http_request,
            $this->planning_factory,
            $this->agiledashboard_milestone_backlog_factory,
            $this->agiledashboard_milestone_backlog_item_collection_factory,
            $this->planning_milestone_factory,
            $this->tracker_factory
        );
    }

    public function testGetZeroUpcomingReleaseWhenThereAreNoFutureMilestone(): void
    {
        $this->planning_virtual_top_milestone
            ->shouldReceive('getPlanning')
            ->once()
            ->andReturn(Mockery::mock(Planning::class, ['getBacklogTrackersIds' => []]));

        $this->agiledashboard_milestone_backlog_factory
            ->shouldReceive('getSelfBacklog')
            ->withArgs([$this->planning_virtual_top_milestone])
            ->andReturn($this->agiledashboard_milestone_backlog)
            ->once();

        $this->agiledashboard_milestone_backlog_item_collection_factory
            ->shouldReceive('getUnassignedOpenCollection')
            ->withArgs([$this->john_doe, $this->planning_virtual_top_milestone, $this->agiledashboard_milestone_backlog, false])
            ->andReturn($this->agileDashboard_milestone_backlog_item_collection)
            ->once();

        $this->planning_factory->shouldReceive('getRootPlanning')->once()->andReturn(Mockery::mock(Planning::class));

        $this->agileDashboard_milestone_backlog_item_collection->shouldReceive('count')->once()->andReturn(5);

        $this->planning_milestone_factory->shouldReceive('getAllFutureMilestones')->once()->andReturn([]);

        $built_presenter = $this->builder->getProjectReleasePresenter(false);

        $this->assertEquals(0, $built_presenter->nb_upcoming_releases);
    }

    public function testGetZeroUpcomingReleasesIfThereIsNotRootPlanning(): void
    {
        $this->planning_virtual_top_milestone
            ->shouldReceive('getPlanning')
            ->once()
            ->andReturn(Mockery::mock(Planning::class, ['getBacklogTrackersIds' => []]));

        $this->agiledashboard_milestone_backlog_factory
            ->shouldReceive('getSelfBacklog')
            ->withArgs([$this->planning_virtual_top_milestone])
            ->andReturn($this->agiledashboard_milestone_backlog)
            ->once();

        $this->agiledashboard_milestone_backlog_item_collection_factory
            ->shouldReceive('getUnassignedOpenCollection')
            ->withArgs([$this->john_doe, $this->planning_virtual_top_milestone, $this->agiledashboard_milestone_backlog, false])
            ->andReturn($this->agileDashboard_milestone_backlog_item_collection)
            ->once();

        $this->planning_factory->shouldReceive('getRootPlanning')->once()->andReturnFalse();

        $this->agileDashboard_milestone_backlog_item_collection->shouldReceive('count')->once()->andReturn(5);

        $built_presenter = $this->builder->getProjectReleasePresenter(false);

        $this->assertEquals(0, $built_presenter->nb_upcoming_releases);
    }

    public function testGetUpcomingReleasesWhenThereAreFutureMilestones(): void
    {
        $this->planning_virtual_top_milestone
            ->shouldReceive('getPlanning')
            ->once()
            ->andReturn(Mockery::mock(Planning::class, ['getBacklogTrackersIds' => []]));

        $this->agiledashboard_milestone_backlog_factory
            ->shouldReceive('getSelfBacklog')
            ->withArgs([$this->planning_virtual_top_milestone])
            ->andReturn($this->agiledashboard_milestone_backlog)
            ->once();

        $this->agiledashboard_milestone_backlog_item_collection_factory
            ->shouldReceive('getUnassignedOpenCollection')
            ->withArgs([$this->john_doe, $this->planning_virtual_top_milestone, $this->agiledashboard_milestone_backlog, false])
            ->andReturn($this->agileDashboard_milestone_backlog_item_collection)
            ->once();

        $this->planning_factory->shouldReceive('getRootPlanning')->once()->andReturn(Mockery::mock(Planning::class));

        $this->agileDashboard_milestone_backlog_item_collection->shouldReceive('count')->once()->andReturn(5);

        $this->planning_milestone_factory
            ->shouldReceive('getAllFutureMilestones')
            ->once()
            ->andReturn([Mockery::mock(Planning_Milestone::class), Mockery::mock(Planning_Milestone::class), Mockery::mock(Planning_Milestone::class)]);

        $built_presenter = $this->builder->getProjectReleasePresenter(false);

        $this->assertEquals(3, $built_presenter->nb_upcoming_releases);
    }

    public function testGetNumberBacklogItem(): void
    {
        $this->planning_virtual_top_milestone
            ->shouldReceive('getPlanning')
            ->once()
            ->andReturn(Mockery::mock(Planning::class, ['getBacklogTrackersIds' => []]));

        $this->agiledashboard_milestone_backlog_factory
            ->shouldReceive('getSelfBacklog')
            ->withArgs([$this->planning_virtual_top_milestone])
            ->andReturn($this->agiledashboard_milestone_backlog)
            ->once();

        $this->agiledashboard_milestone_backlog_item_collection_factory
            ->shouldReceive('getUnassignedOpenCollection')
            ->withArgs([$this->john_doe, $this->planning_virtual_top_milestone, $this->agiledashboard_milestone_backlog, false])
            ->andReturn($this->agileDashboard_milestone_backlog_item_collection)
            ->once();

        $this->planning_factory->shouldReceive('getRootPlanning')->once()->andReturnFalse();

        $this->agileDashboard_milestone_backlog_item_collection->shouldReceive('count')->andReturn(5);

        $this->planning_milestone_factory
            ->shouldReceive('getAllFutureMilestones')
            ->never()
            ->andReturn([]);

        $built_presenter = $this->builder->getProjectReleasePresenter(false);

        $this->assertEquals(5, $built_presenter->nb_backlog_items);
    }

    public function testIsIE11(): void
    {
        $this->planning_virtual_top_milestone
            ->shouldReceive('getPlanning')
            ->once()
            ->andReturn(Mockery::mock(Planning::class, ['getBacklogTrackersIds' => []]));

        $this->agiledashboard_milestone_backlog_factory
            ->shouldReceive('getSelfBacklog')
            ->withArgs([$this->planning_virtual_top_milestone])
            ->andReturn($this->agiledashboard_milestone_backlog)
            ->once();

        $this->agiledashboard_milestone_backlog_item_collection_factory
            ->shouldReceive('getUnassignedOpenCollection')
            ->withArgs([$this->john_doe, $this->planning_virtual_top_milestone, $this->agiledashboard_milestone_backlog, false])
            ->andReturn($this->agileDashboard_milestone_backlog_item_collection)
            ->once();

        $this->planning_factory->shouldReceive('getRootPlanning')->once()->andReturnFalse();

        $this->agileDashboard_milestone_backlog_item_collection->shouldReceive('count')->once()->andReturn(0);

        $this->planning_milestone_factory
            ->shouldReceive('getAllFutureMilestones')
            ->never()
            ->andReturn([]);

        $built_presenter = $this->builder->getProjectReleasePresenter(true);

        $this->assertTrue($built_presenter->is_IE11);
    }

    public function testGetTrackersId(): void
    {
        $this->planning_virtual_top_milestone
            ->shouldReceive('getPlanning')
            ->once()
            ->andReturn(Mockery::mock(Planning::class, ['getBacklogTrackersIds' => [122, 124]]));

        $this->tracker_factory->shouldReceive('getTrackerById')->once()->withArgs([122])->andReturn($this->mockAnArtifact('Bug', 'fiesta-red'));
        $this->tracker_factory->shouldReceive('getTrackerById')->once()->withArgs([124])->andReturn($this->mockAnArtifact('Story', 'deep-blue'));

        $this->agiledashboard_milestone_backlog_factory
            ->shouldReceive('getSelfBacklog')
            ->withArgs([$this->planning_virtual_top_milestone])
            ->andReturn($this->agiledashboard_milestone_backlog)
            ->once();

        $this->agiledashboard_milestone_backlog_item_collection_factory
            ->shouldReceive('getUnassignedOpenCollection')
            ->withArgs([$this->john_doe, $this->planning_virtual_top_milestone, $this->agiledashboard_milestone_backlog, false])
            ->andReturn($this->agileDashboard_milestone_backlog_item_collection)
            ->once();

        $this->planning_factory->shouldReceive('getRootPlanning')->once()->andReturnFalse();

        $this->agileDashboard_milestone_backlog_item_collection->shouldReceive('count')->once()->andReturn(0);

        $this->planning_milestone_factory
            ->shouldReceive('getAllFutureMilestones')
            ->never();

        $built_presenter = $this->builder->getProjectReleasePresenter(false);
        $tracker_json = '[{"id":122,"color_name":"fiesta-red","label":"Bug"},{"id":124,"color_name":"deep-blue","label":"Story"}]';

        $this->assertEqualsCanonicalizing($tracker_json, $built_presenter->json_trackers_agile_dashboard);
    }

    private function mockAnArtifact(string $name, string $color)
    {
        $artifact = Mockery::mock(\Artifact::class);
        $artifact->shouldReceive('getName')->once()->andReturn($name);
        $artifact->shouldReceive('getColor')->once()->andReturn(Mockery::mock(TrackerColor::fromName($color)));
        return $artifact;
    }
}
