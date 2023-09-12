<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Planning;

use ForgeConfig;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Planning_Milestone;
use Planning_MilestoneController;
use Tuleap\AgileDashboard\BreadCrumbDropdown\AgileDashboardCrumbBuilder;
use Tuleap\AgileDashboard\BreadCrumbDropdown\MilestoneCrumbBuilder;
use Tuleap\AgileDashboard\BreadCrumbDropdown\VirtualTopMilestoneCrumbBuilder;
use Tuleap\AgileDashboard\Milestone\AllBreadCrumbsForMilestoneBuilder;
use Tuleap\AgileDashboard\Milestone\HeaderOptionsProvider;
use Tuleap\Kanban\CheckSplitKanbanConfiguration;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumb;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLink;
use Tuleap\Test\Stubs\EventDispatcherStub;
use Tuleap\Tracker\Artifact\RecentlyVisited\VisitRecorder;

class MilestoneControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var string */
    private $plugin_path;

    /** @var Planning_Milestone */
    private $product;

    /** @var Planning_Milestone */
    private $release;

    /** @var Planning_Milestone */
    private $sprint;

    /** @var Planning_Milestone */
    private $nomilestone;

    /** @var \Planning_MilestoneFactory */
    private $milestone_factory;

    /** @var \ProjectManager */
    private $project_manager;

    /** @var \Project */
    private $project;

    /** @var Planning_MilestoneController */
    private $milestone_controller;

    /** @var \Codendi_Request */
    private $request;

    /** @var \Planning_MilestonePaneFactory */
    private $pane_factory;

    /** @var \PFUser */
    private $current_user;

    /** @var AgileDashboardCrumbBuilder */
    private $agile_dashboard_crumb_builder;

    /** @var VirtualTopMilestoneCrumbBuilder */
    private $top_milestone_crumb_builder;

    /** @var MilestoneCrumbBuilder */
    private $milestone_crumb_builder;

    /** @var BreadCrumb */
    private $service_breadcrumb;

    /** @var BreadCrumb */
    private $top_backlog_breadcrumb;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|VisitRecorder
     */
    private $visit_recorder;
    /**
     * @var AllBreadCrumbsForMilestoneBuilder
     */
    private $crumb_builder;

    public function setUp(): void
    {
        parent::setUp();
        ForgeConfig::store();
        ForgeConfig::set('codendi_dir', __DIR__ . '/../../../../../..');

        $this->pane_factory = Mockery::mock(\Planning_MilestonePaneFactory::class);

        $this->plugin_path       = '/plugin/path';
        $this->milestone_factory = Mockery::mock(\Planning_MilestoneFactory::class);
        $this->project_manager   = Mockery::mock(\ProjectManager::class);

        $this->product = Mockery::mock(Planning_Milestone::class);
        $this->release = Mockery::mock(Planning_Milestone::class);
        $this->sprint  = Mockery::mock(Planning_Milestone::class);
        $this->sprint->shouldReceive('getArtifact')->andReturn(true);

        $this->nomilestone = Mockery::mock(Planning_Milestone::class);
        $this->nomilestone->shouldReceive('getArtifact')->andReturn(null);

        $this->current_user = Mockery::mock(\PFUser::class);
        $this->request      = Mockery::mock(\Codendi_Request::class);
        $this->request->shouldReceive('get', 'group_id')->andReturn(102);
        $this->request->shouldReceive('getCurrentUser')->andReturn($this->current_user);

        $this->project = Mockery::mock(\Project::class);
        $this->project_manager->shouldReceive('getProject', 102)->andReturn($this->project);

        $this->agile_dashboard_crumb_builder = Mockery::mock(AgileDashboardCrumbBuilder::class);
        $this->top_milestone_crumb_builder   = Mockery::mock(VirtualTopMilestoneCrumbBuilder::class);
        $this->milestone_crumb_builder       = Mockery::mock(MilestoneCrumbBuilder::class);

        $this->crumb_builder = new AllBreadCrumbsForMilestoneBuilder(
            $this->agile_dashboard_crumb_builder,
            $this->top_milestone_crumb_builder,
            $this->milestone_crumb_builder,
            new CheckSplitKanbanConfiguration(EventDispatcherStub::withIdentityCallback()),
        );

        $this->service_breadcrumb     = new BreadCrumb(
            new BreadCrumbLink('Backlog', '/fake_url')
        );
        $this->top_backlog_breadcrumb = new BreadCrumb(
            new BreadCrumbLink('Top backlog', '/fake_url')
        );

        $this->visit_recorder = Mockery::mock(VisitRecorder::class);

        $this->milestone_controller = new Planning_MilestoneController(
            $this->request,
            $this->milestone_factory,
            $this->project_manager,
            $this->pane_factory,
            $this->visit_recorder,
            $this->crumb_builder,
            Mockery::mock(HeaderOptionsProvider::class),
        );
    }

    public function tearDown(): void
    {
        ForgeConfig::restore();
        parent::tearDown();
    }

    public function testItHasOnlyTheServiceBreadCrumbsWhenThereIsNoMilestone()
    {
        $this->milestone_factory->shouldReceive('getBareMilestone')->andReturn($this->nomilestone);
        $this->agile_dashboard_crumb_builder->shouldReceive('build')->andReturn($this->service_breadcrumb);
        $this->top_milestone_crumb_builder->shouldReceive('build')->andReturn($this->top_backlog_breadcrumb);
        $this->milestone_crumb_builder->shouldNotReceive('build');

        $breadcrumbs = $this->milestone_controller->getBreadcrumbs($this->plugin_path);

        $expected = [$this->service_breadcrumb];

        $this->assertEquals($expected, $breadcrumbs->getBreadcrumbs());
    }

    public function testItIncludesBreadcrumbsForParentMilestones()
    {
        $product_breadcrumb = new BreadCrumb(new BreadCrumbLink('Product X', 'fake_url'));
        $release_breadcrumb = new BreadCrumb(new BreadCrumbLink('Release 1.0', 'fake_url'));
        $sprint_breadcrumb  = new BreadCrumb(new BreadCrumbLink('Sprint 1', 'fake_url'));

        $this->sprint->shouldReceive('getAncestors')->andReturn([$this->release, $this->product]);
        $this->milestone_factory->shouldReceive('getBareMilestone')->andReturn($this->sprint);
        $this->agile_dashboard_crumb_builder->shouldReceive('build')->andReturn($this->service_breadcrumb);
        $this->top_milestone_crumb_builder->shouldReceive('build')->andReturn($this->top_backlog_breadcrumb);
        $this->milestone_crumb_builder->shouldReceive('build')->andReturn(
            $product_breadcrumb,
            $release_breadcrumb,
            $sprint_breadcrumb
        );

        $breadcrumbs = $this->milestone_controller->getBreadcrumbs($this->plugin_path);

        $expected = [
            $this->service_breadcrumb,
            $product_breadcrumb,
            $release_breadcrumb,
            $sprint_breadcrumb,
        ];

        $this->assertEquals($expected, $breadcrumbs->getBreadcrumbs());
    }
}
