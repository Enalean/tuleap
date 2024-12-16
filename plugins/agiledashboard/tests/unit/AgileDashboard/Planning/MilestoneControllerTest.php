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

declare(strict_types=1);

namespace Tuleap\AgileDashboard\Planning;

use Codendi_Request;
use PHPUnit\Framework\MockObject\MockObject;
use Planning_Milestone;
use Planning_MilestoneController;
use Planning_MilestoneFactory;
use Planning_MilestonePaneFactory;
use ProjectManager;
use Tuleap\AgileDashboard\BreadCrumbDropdown\AgileDashboardCrumbBuilder;
use Tuleap\AgileDashboard\BreadCrumbDropdown\MilestoneCrumbBuilder;
use Tuleap\AgileDashboard\Milestone\AllBreadCrumbsForMilestoneBuilder;
use Tuleap\AgileDashboard\Milestone\HeaderOptionsProvider;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumb;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLink;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\RecentlyVisited\VisitRecorder;

final class MilestoneControllerTest extends TestCase
{
    private string $plugin_path;
    private Planning_Milestone&MockObject $product;
    private Planning_Milestone&MockObject $release;
    private Planning_Milestone&MockObject $sprint;
    private Planning_Milestone&MockObject $nomilestone;
    private Planning_MilestoneFactory $milestone_factory;
    private Planning_MilestoneController $milestone_controller;
    private AgileDashboardCrumbBuilder&MockObject $agile_dashboard_crumb_builder;
    private MilestoneCrumbBuilder&MockObject $milestone_crumb_builder;
    private BreadCrumb $service_breadcrumb;
    private BreadCrumb $top_backlog_breadcrumb;

    public function setUp(): void
    {
        $this->plugin_path       = '/plugin/path';
        $this->milestone_factory = $this->createMock(Planning_MilestoneFactory::class);
        $project_manager         = $this->createMock(ProjectManager::class);

        $this->product = $this->createMock(Planning_Milestone::class);
        $this->release = $this->createMock(Planning_Milestone::class);
        $this->sprint  = $this->createMock(Planning_Milestone::class);
        $this->sprint->method('getArtifact')->willReturn(true);

        $this->nomilestone = $this->createMock(Planning_Milestone::class);
        $this->nomilestone->method('getArtifact')->willReturn(null);

        $current_user = UserTestBuilder::buildWithDefaults();
        $request      = new Codendi_Request([
            'group_id'    => 102,
            'planning_id' => 102,
        ]);
        $request->setCurrentUser($current_user);

        $project = ProjectTestBuilder::aProject()->withId(102)->build();
        $project_manager->method('getProject')->with(102)->willReturn($project);

        $this->agile_dashboard_crumb_builder = $this->createMock(AgileDashboardCrumbBuilder::class);
        $this->milestone_crumb_builder       = $this->createMock(MilestoneCrumbBuilder::class);

        $this->service_breadcrumb     = new BreadCrumb(new BreadCrumbLink('Backlog', '/fake_url'));
        $this->top_backlog_breadcrumb = new BreadCrumb(new BreadCrumbLink('Top backlog', '/fake_url'));

        $this->milestone_controller = new Planning_MilestoneController(
            $request,
            $this->milestone_factory,
            $project_manager,
            $this->createMock(Planning_MilestonePaneFactory::class),
            $this->createMock(VisitRecorder::class),
            new AllBreadCrumbsForMilestoneBuilder(
                $this->agile_dashboard_crumb_builder,
                $this->milestone_crumb_builder,
            ),
            $this->createMock(HeaderOptionsProvider::class),
        );
    }

    public function testItHasOnlyTheServiceBreadCrumbsWhenThereIsNoMilestone(): void
    {
        $this->milestone_factory->method('getBareMilestone')->willReturn($this->nomilestone);
        $this->agile_dashboard_crumb_builder->method('build')->willReturn($this->service_breadcrumb);
        $this->milestone_crumb_builder->expects(self::never())->method('build');

        $breadcrumbs = $this->milestone_controller->getBreadcrumbs();

        $expected = [$this->service_breadcrumb];

        self::assertEquals($expected, $breadcrumbs->getBreadcrumbs());
    }

    public function testItIncludesBreadcrumbsForParentMilestones(): void
    {
        $product_breadcrumb = new BreadCrumb(new BreadCrumbLink('Product X', 'fake_url'));
        $release_breadcrumb = new BreadCrumb(new BreadCrumbLink('Release 1.0', 'fake_url'));
        $sprint_breadcrumb  = new BreadCrumb(new BreadCrumbLink('Sprint 1', 'fake_url'));

        $this->sprint->method('getAncestors')->willReturn([$this->release, $this->product]);
        $this->milestone_factory->method('getBareMilestone')->willReturn($this->sprint);
        $this->agile_dashboard_crumb_builder->method('build')->willReturn($this->service_breadcrumb);
        $this->milestone_crumb_builder->method('build')->willReturnOnConsecutiveCalls(
            $product_breadcrumb,
            $release_breadcrumb,
            $sprint_breadcrumb
        );

        $breadcrumbs = $this->milestone_controller->getBreadcrumbs();

        $expected = [
            $this->service_breadcrumb,
            $product_breadcrumb,
            $release_breadcrumb,
            $sprint_breadcrumb,
        ];

        self::assertEquals($expected, $breadcrumbs->getBreadcrumbs());
    }
}
