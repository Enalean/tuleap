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
use Planning_ArtifactMilestone;
use Planning_Milestone;
use Planning_MilestoneController;
use Planning_MilestoneFactory;
use Planning_MilestonePaneFactory;
use Planning_VirtualTopMilestone;
use ProjectManager;
use Tuleap\AgileDashboard\BreadCrumbDropdown\AgileDashboardCrumbBuilder;
use Tuleap\AgileDashboard\BreadCrumbDropdown\MilestoneCrumbBuilder;
use Tuleap\AgileDashboard\Milestone\AllBreadCrumbsForMilestoneBuilder;
use Tuleap\AgileDashboard\Milestone\HeaderOptionsProvider;
use Tuleap\AgileDashboard\Milestone\PaginatedMilestones;
use Tuleap\AgileDashboard\Test\Builders\PlanningBuilder;
use Tuleap\GlobalLanguageMock;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumb;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLink;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLinkCollection;
use Tuleap\Layout\BreadCrumbDropdown\SubItemsUnlabelledSection;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\RecentlyVisited\VisitRecorder;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MilestoneControllerTest extends TestCase
{
    use GlobalLanguageMock;

    private Planning_Milestone $product;
    private Planning_Milestone $release;
    private Planning_Milestone $sprint;
    private Planning_Milestone $nomilestone;
    private Planning_MilestoneFactory&MockObject $milestone_factory;
    private Planning_MilestonePaneFactory&MockObject $pane_factory;
    private Planning_MilestoneController $milestone_controller;
    private BreadCrumb $service_breadcrumb;
    private BreadCrumb $top_backlog_breadcrumb;

    public function setUp(): void
    {
        $GLOBALS['Language']->method('getText')->willReturnCallback(static fn(string $domain, string $text) => $text);

        $this->milestone_factory = $this->createMock(Planning_MilestoneFactory::class);
        $project_manager         = $this->createMock(ProjectManager::class);
        $project                 = ProjectTestBuilder::aProject()->withId(102)->build();
        $project_manager->method('getProject')->with(102)->willReturn($project);

        $planning      = PlanningBuilder::aPlanning(102)->build();
        $this->product = new Planning_ArtifactMilestone(
            $project,
            $planning,
            ArtifactTestBuilder::anArtifact(1)->withTitle('Product X')->build(),
        );
        $this->release = new Planning_ArtifactMilestone(
            $project,
            $planning,
            ArtifactTestBuilder::anArtifact(2)->withTitle('Release 1.0')->build(),
        );
        $this->sprint  = new Planning_ArtifactMilestone(
            $project,
            $planning,
            ArtifactTestBuilder::anArtifact(3)->withTitle('Sprint 1')->build(),
        );

        $this->nomilestone = new Planning_VirtualTopMilestone($project, $planning);

        $request = new Codendi_Request([
            'group_id'    => 102,
            'planning_id' => 102,
        ]);
        $request->setCurrentUser(UserTestBuilder::anActiveUser()->withMemberOf($project)->build());

        $this->pane_factory           = $this->createMock(Planning_MilestonePaneFactory::class);
        $this->service_breadcrumb     = new BreadCrumb(new BreadCrumbLink('Backlog', '/plugins/agiledashboard/?group_id=102&action=show-top&pane=topplanning-v2'));
        $this->top_backlog_breadcrumb = new BreadCrumb(new BreadCrumbLink('Top backlog', '/fake_url'));

        $this->milestone_controller = new Planning_MilestoneController(
            $request,
            $this->milestone_factory,
            $project_manager,
            $this->pane_factory,
            $this->createMock(VisitRecorder::class),
            new AllBreadCrumbsForMilestoneBuilder(
                new AgileDashboardCrumbBuilder(),
                new MilestoneCrumbBuilder(
                    '/plugin/path',
                    $this->pane_factory,
                    $this->milestone_factory,
                ),
            ),
            $this->createMock(HeaderOptionsProvider::class),
        );
    }

    public function testItHasOnlyTheServiceBreadCrumbsWhenThereIsNoMilestone(): void
    {
        $this->milestone_factory->method('getBareMilestone')->willReturn($this->nomilestone);

        $breadcrumbs = $this->milestone_controller->getBreadcrumbs();

        $expected = [$this->service_breadcrumb];

        self::assertEquals($expected, $breadcrumbs->getBreadcrumbs());
    }

    public function testItIncludesBreadcrumbsForParentMilestones(): void
    {
        $product_breadcrumb = new BreadCrumb(new BreadCrumbLink('Product X', '/plugin/path/?planning_id=34&pane=details&action=show&group_id=102&aid=1'));
        $release_breadcrumb = new BreadCrumb(new BreadCrumbLink('Release 1.0', '/plugin/path/?planning_id=34&pane=details&action=show&group_id=102&aid=2'));
        $sprint_breadcrumb  = new BreadCrumb(new BreadCrumbLink('Sprint 1', '/plugin/path/?planning_id=34&pane=details&action=show&group_id=102&aid=3'));

        $product_breadcrumb->getSubItems()->addSection(new SubItemsUnlabelledSection(new BreadCrumbLinkCollection(
            [new BreadCrumbLink('Artifact', '/plugins/tracker/?aid=1')]
        )));
        $release_breadcrumb->getSubItems()->addSection(new SubItemsUnlabelledSection(new BreadCrumbLinkCollection(
            [new BreadCrumbLink('Artifact', '/plugins/tracker/?aid=2')]
        )));
        $sprint_breadcrumb->getSubItems()->addSection(new SubItemsUnlabelledSection(new BreadCrumbLinkCollection(
            [new BreadCrumbLink('Artifact', '/plugins/tracker/?aid=3')]
        )));

        $this->sprint->setAncestors([$this->release, $this->product]);
        $this->milestone_factory->method('getBareMilestone')->willReturn($this->sprint);
        $this->milestone_factory->expects(self::exactly(3))->method('addMilestoneAncestors');
        $this->milestone_factory->method('getPaginatedSiblingMilestones')->willReturn(new PaginatedMilestones([], 0));
        $this->pane_factory->expects(self::exactly(3))->method('getListOfPaneInfo')->willReturn([]);

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
