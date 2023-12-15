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

namespace Tuleap\TestPlan;

use HTTPRequest;
use Project;
use TemplateRenderer;
use Tuleap\AgileDashboard\Milestone\AllBreadCrumbsForMilestoneBuilder;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Layout\NewDropdown\NewDropdownLinkSectionPresenter;
use Tuleap\Option\Option;
use Tuleap\Request\NotFoundException;
use Tuleap\Tracker\Artifact\RecentlyVisited\VisitRecorder;

final class TestPlanControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\Planning_MilestoneFactory
     */
    private mixed $milestone_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&TestPlanPaneDisplayable
     */
    private mixed $testplan_pane_displayable;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&VisitRecorder
     */
    private mixed $visit_recorder;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&TemplateRenderer
     */
    private mixed $renderer;
    /**
     * @var \PFUser&\PHPUnit\Framework\MockObject\MockObject
     */
    private mixed $user;
    /**
     * @var HTTPRequest&\PHPUnit\Framework\MockObject\MockObject
     */
    private mixed $request;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&IncludeAssets
     */
    private mixed $agiledashboard_asset;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&AllBreadCrumbsForMilestoneBuilder
     */
    private mixed $bread_crumbs_builder;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&TestPlanPresenterBuilder
     */
    private mixed $presenter_builder;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&IncludeAssets
     */
    private mixed $testplan_asset;

    private TestPlanController $controller;

    protected function setUp(): void
    {
        $this->milestone_factory         = $this->createMock(\Planning_MilestoneFactory::class);
        $this->testplan_pane_displayable = $this->createMock(TestPlanPaneDisplayable::class);
        $this->visit_recorder            = $this->createMock(VisitRecorder::class);
        $this->renderer                  = $this->createMock(TemplateRenderer::class);

        $this->user    = $this->createMock(\PFUser::class);
        $this->request = $this->createMock(HTTPRequest::class);
        $this->request->method('getCurrentUser')->willReturn($this->user);

        $header_options_provider = $this->createMock(TestPlanHeaderOptionsProvider::class);
        $header_options_provider->method('getCurrentContextSection')->willReturn(Option::nothing(NewDropdownLinkSectionPresenter::class));

        $this->bread_crumbs_builder = $this->createMock(AllBreadCrumbsForMilestoneBuilder::class);
        $this->agiledashboard_asset = $this->createMock(IncludeAssets::class);
        $this->testplan_asset       = $this->createMock(IncludeAssets::class);
        $this->presenter_builder    = $this->createMock(TestPlanPresenterBuilder::class);

        $this->controller = new TestPlanController(
            $this->renderer,
            $this->bread_crumbs_builder,
            $this->testplan_asset,
            $this->testplan_pane_displayable,
            $this->visit_recorder,
            $this->milestone_factory,
            $this->presenter_builder,
            $header_options_provider,
        );
    }

    public function test404IfMilestoneCannotBeFound(): void
    {
        $this->milestone_factory
            ->expects(self::once())
            ->method('getBareMilestoneByArtifactId')
            ->with($this->user, 42)
            ->willReturn(null);

        $this->expectException(NotFoundException::class);

        $this->controller->process($this->request, $this->createMock(BaseLayout::class), ['id' => 42]);
    }

    public function test404IfNoMilestone(): void
    {
        $this->milestone_factory
            ->expects(self::once())
            ->method('getBareMilestoneByArtifactId')
            ->with($this->user, 42)
            ->willReturn($this->createMock(\Planning_NoMilestone::class));

        $this->expectException(NotFoundException::class);

        $this->controller->process($this->request, $this->createMock(BaseLayout::class), ['id' => 42]);
    }

    public function test404IfVirtualTopMilestone(): void
    {
        $this->milestone_factory
            ->expects(self::once())
            ->method('getBareMilestoneByArtifactId')
            ->with($this->user, 42)
            ->willReturn($this->createMock(\Planning_VirtualTopMilestone::class));

        $this->expectException(NotFoundException::class);

        $this->controller->process($this->request, $this->createMock(BaseLayout::class), ['id' => 42]);
    }

    public function test404IfProjectMilestoneDoesNotMatchRequestedOne(): void
    {
        $another_project = $this->createMock(Project::class);
        $another_project->method('getUnixNameMixedCase')->willReturn('another-project');

        $milestone = $this->createMock(\Planning_ArtifactMilestone::class);
        $milestone->method('getProject')->willReturn($another_project);

        $this->milestone_factory
            ->expects(self::once())
            ->method('getBareMilestoneByArtifactId')
            ->with($this->user, 42)
            ->willReturn($milestone);

        $this->expectException(NotFoundException::class);

        $this->controller->process(
            $this->request,
            $this->createMock(BaseLayout::class),
            ['id' => 42, 'project_name' => 'my-project']
        );
    }

    public function test404IfProjectDoesNotUseAgiledashboard(): void
    {
        $my_project = $this->createMock(Project::class);
        $my_project->method('getUnixNameMixedCase')->willReturn('my-project');
        $my_project
            ->expects(self::once())
            ->method('getService')
            ->with('plugin_agiledashboard')
            ->willReturn(null);

        $milestone = $this->createMock(\Planning_ArtifactMilestone::class);
        $milestone->method('getProject')->willReturn($my_project);

        $this->milestone_factory
            ->expects(self::once())
            ->method('getBareMilestoneByArtifactId')
            ->with($this->user, 42)
            ->willReturn($milestone);

        $this->expectException(NotFoundException::class);

        $this->controller->process(
            $this->request,
            $this->createMock(BaseLayout::class),
            ['id' => 42, 'project_name' => 'my-project']
        );
    }

    public function test404IfThePaneIsNotDisplayable(): void
    {
        $my_project = $this->createMock(Project::class);
        $my_project->method('getUnixNameMixedCase')->willReturn('my-project');
        $my_project
            ->expects(self::once())
            ->method('getService')
            ->with('plugin_agiledashboard')
            ->willReturn($this->createMock(\Service::class));
        $this->testplan_pane_displayable->method('isTestPlanPaneDisplayable')->willReturn(false);

        $milestone = $this->createMock(\Planning_ArtifactMilestone::class);
        $milestone->method('getProject')->willReturn($my_project);

        $this->milestone_factory
            ->expects(self::once())
            ->method('getBareMilestoneByArtifactId')
            ->with($this->user, 42)
            ->willReturn($milestone);

        $this->expectException(NotFoundException::class);

        $this->controller->process(
            $this->request,
            $this->createMock(BaseLayout::class),
            ['id' => 42, 'project_name' => 'my-project']
        );
    }

    public function testItDisplaysThePage(): void
    {
        $service = $this->createMock(\Service::class);
        $service->method('displayHeader');
        $service->method('displayFooter');

        $my_project = $this->createMock(Project::class);
        $my_project->method('getUnixNameMixedCase')->willReturn('my-project');
        $my_project
            ->expects(self::once())
            ->method('getService')
            ->with('plugin_agiledashboard')
            ->willReturn($service);

        $this->testplan_pane_displayable->method('isTestPlanPaneDisplayable')->willReturn(true);

        $milestone = $this->createMock(\Planning_ArtifactMilestone::class);
        $milestone->method('getProject')->willReturn($my_project);
        $milestone->method('getArtifact')->willReturn($this->createMock(\Tuleap\Tracker\Artifact\Artifact::class));
        $milestone->method('getArtifactTitle')->willReturn("Title");
        $milestone->method('getPromotedMilestoneId')->willReturn('myid');

        $this->milestone_factory
            ->expects(self::once())
            ->method('getBareMilestoneByArtifactId')
            ->with($this->user, 42)
            ->willReturn($milestone);

        $this->visit_recorder->expects(self::once())->method('record');

        $this->renderer->method('renderToPage')->with('test-plan', self::isInstanceOf(TestPlanPresenter::class));

        $this->agiledashboard_asset->method('getFileURL');
        $this->testplan_asset->method('getFileURL');

        $base_layout = $this->createMock(BaseLayout::class);
        $base_layout->method('includeFooterJavascriptFile');
        $base_layout->method('addCssAsset');

        $this->bread_crumbs_builder->method('getBreadcrumbs');
        $this->presenter_builder->method('getPresenter');

        $this->controller->process(
            $this->request,
            $base_layout,
            ['id' => 42, 'project_name' => 'my-project']
        );
    }
}
