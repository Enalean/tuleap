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
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Project;
use TemplateRenderer;
use Tuleap\AgileDashboard\Milestone\AllBreadCrumbsForMilestoneBuilder;
use Tuleap\BrowserDetection\DetectedBrowserTest;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Request\NotFoundException;
use Tuleap\Tracker\Artifact\RecentlyVisited\VisitRecorder;

class TestPlanControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\Planning_MilestoneFactory
     */
    private $milestone_factory;
    /**
     * @var TestPlanController
     */
    private $controller;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\PFUser
     */
    private $user;
    /**
     * @var HTTPRequest|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $request;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TestPlanPaneDisplayable
     */
    private $testplan_pane_displayable;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|VisitRecorder
     */
    private $visit_recorder;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TemplateRenderer
     */
    private $renderer;

    protected function setUp(): void
    {
        $this->milestone_factory         = Mockery::mock(\Planning_MilestoneFactory::class);
        $this->testplan_pane_displayable = Mockery::mock(TestPlanPaneDisplayable::class);
        $this->visit_recorder            = Mockery::mock(VisitRecorder::class);
        $this->renderer                  = Mockery::mock(TemplateRenderer::class);

        $this->user    = Mockery::mock(\PFUser::class);
        $this->request = Mockery::mock(HTTPRequest::class);
        $this->request->shouldReceive(['getCurrentUser' => $this->user]);

        $this->controller = new TestPlanController(
            $this->renderer,
            Mockery::spy(AllBreadCrumbsForMilestoneBuilder::class),
            Mockery::spy(IncludeAssets::class),
            Mockery::spy(IncludeAssets::class),
            $this->testplan_pane_displayable,
            $this->visit_recorder,
            $this->milestone_factory,
            Mockery::spy(TestPlanPresenterBuilder::class),
        );
    }

    public function test404IfMilestoneCannotBeFound(): void
    {
        $this->milestone_factory
            ->shouldReceive('getBareMilestoneByArtifactId')
            ->with($this->user, 42)
            ->once()
            ->andReturnNull();

        $this->expectException(NotFoundException::class);

        $this->controller->process($this->request, Mockery::mock(BaseLayout::class), ['id' => 42]);
    }

    public function test404IfNoMilestone(): void
    {
        $this->milestone_factory
            ->shouldReceive('getBareMilestoneByArtifactId')
            ->with($this->user, 42)
            ->once()
            ->andReturn(Mockery::mock(\Planning_NoMilestone::class));

        $this->expectException(NotFoundException::class);

        $this->controller->process($this->request, Mockery::mock(BaseLayout::class), ['id' => 42]);
    }

    public function test404IfVirtualTopMilestone(): void
    {
        $this->milestone_factory
            ->shouldReceive('getBareMilestoneByArtifactId')
            ->with($this->user, 42)
            ->once()
            ->andReturn(Mockery::mock(\Planning_VirtualTopMilestone::class));

        $this->expectException(NotFoundException::class);

        $this->controller->process($this->request, Mockery::mock(BaseLayout::class), ['id' => 42]);
    }

    public function test404IfProjectMilestoneDoesNotMatchRequestedOne(): void
    {
        $another_project = Mockery::mock(Project::class);
        $another_project->shouldReceive('getUnixNameMixedCase')->andReturn('another-project');

        $milestone = Mockery::mock(\Planning_ArtifactMilestone::class);
        $milestone->shouldReceive('getProject')->andReturn($another_project);

        $this->milestone_factory
            ->shouldReceive('getBareMilestoneByArtifactId')
            ->with($this->user, 42)
            ->once()
            ->andReturn($milestone);

        $this->expectException(NotFoundException::class);

        $this->controller->process(
            $this->request,
            Mockery::mock(BaseLayout::class),
            ['id' => 42, 'project_name' => 'my-project']
        );
    }

    public function test404IfProjectDoesNotUseAgiledashboard(): void
    {
        $my_project = Mockery::mock(Project::class);
        $my_project->shouldReceive('getUnixNameMixedCase')->andReturn('my-project');
        $my_project
            ->shouldReceive('getService')
            ->with('plugin_agiledashboard')
            ->once()
            ->andReturnNull();

        $milestone = Mockery::mock(\Planning_ArtifactMilestone::class);
        $milestone->shouldReceive('getProject')->andReturn($my_project);

        $this->milestone_factory
            ->shouldReceive('getBareMilestoneByArtifactId')
            ->with($this->user, 42)
            ->once()
            ->andReturn($milestone);

        $this->expectException(NotFoundException::class);

        $this->controller->process(
            $this->request,
            Mockery::mock(BaseLayout::class),
            ['id' => 42, 'project_name' => 'my-project']
        );
    }

    public function test404IfThePaneIsNotDisplayable(): void
    {
        $my_project = Mockery::mock(Project::class);
        $my_project->shouldReceive('getUnixNameMixedCase')->andReturn('my-project');
        $my_project
            ->shouldReceive('getService')
            ->with('plugin_agiledashboard')
            ->once()
            ->andReturn(Mockery::mock(\Service::class));
        $this->testplan_pane_displayable->shouldReceive('isTestPlanPaneDisplayable')->andReturn(false);

        $milestone = Mockery::mock(\Planning_ArtifactMilestone::class);
        $milestone->shouldReceive('getProject')->andReturn($my_project);

        $this->milestone_factory
            ->shouldReceive('getBareMilestoneByArtifactId')
            ->with($this->user, 42)
            ->once()
            ->andReturn($milestone);

        $this->expectException(NotFoundException::class);

        $this->controller->process(
            $this->request,
            Mockery::mock(BaseLayout::class),
            ['id' => 42, 'project_name' => 'my-project']
        );
    }

    public function testItDisplaysThePage(): void
    {
        $my_project = Mockery::mock(Project::class);
        $my_project->shouldReceive('getUnixNameMixedCase')->andReturn('my-project');
        $my_project
            ->shouldReceive('getService')
            ->with('plugin_agiledashboard')
            ->once()
            ->andReturn(Mockery::spy(\Service::class));
        $this->testplan_pane_displayable->shouldReceive('isTestPlanPaneDisplayable')->andReturn(true);

        $milestone = Mockery::mock(\Planning_ArtifactMilestone::class);
        $milestone->shouldReceive('getProject')->andReturn($my_project);
        $milestone->shouldReceive('getArtifact')->andReturn(Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class));
        $milestone->shouldReceive('getArtifactTitle')->andReturn("Title");

        $this->milestone_factory
            ->shouldReceive('getBareMilestoneByArtifactId')
            ->with($this->user, 42)
            ->once()
            ->andReturn($milestone);

        $this->visit_recorder->shouldReceive('record')->once();

        $this->request->shouldReceive('getFromServer')->andReturn('Some user agent string that is not IE11');

        $this->renderer->shouldReceive('renderToPage')->with('test-plan', Mockery::type(TestPlanPresenter::class));

        $this->controller->process(
            $this->request,
            Mockery::spy(BaseLayout::class),
            ['id' => 42, 'project_name' => 'my-project']
        );
    }

    /**
     * @dataProvider dataProviderUnsupportedBrowsers
     */
    public function testItDisplaysUnsupportedBrowserPageForUnsupportedBrowsers(string $unsupported_browser_user_agent, string $expected_template): void
    {
        $my_project = Mockery::mock(Project::class);
        $my_project->shouldReceive('getUnixNameMixedCase')->andReturn('my-project');
        $my_project
            ->shouldReceive('getService')
            ->with('plugin_agiledashboard')
            ->once()
            ->andReturn(Mockery::spy(\Service::class));
        $this->testplan_pane_displayable->shouldReceive('isTestPlanPaneDisplayable')->andReturn(true);

        $milestone = Mockery::mock(\Planning_ArtifactMilestone::class);
        $milestone->shouldReceive('getProject')->andReturn($my_project);
        $milestone->shouldReceive('getArtifact')->andReturn(Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class));
        $milestone->shouldReceive('getArtifactTitle')->andReturn("Title");

        $this->milestone_factory
            ->shouldReceive('getBareMilestoneByArtifactId')
            ->with($this->user, 42)
            ->once()
            ->andReturn($milestone);

        $this->visit_recorder->shouldReceive('record')->once();

        $this->request->shouldReceive('getFromServer')->andReturn($unsupported_browser_user_agent);

        $this->renderer->shouldReceive('renderToPage')->with($expected_template, Mockery::type(TestPlanPresenter::class));

        $this->controller->process(
            $this->request,
            Mockery::spy(BaseLayout::class),
            ['id' => 42, 'project_name' => 'my-project']
        );
    }

    public function dataProviderUnsupportedBrowsers(): array
    {
        return [
            [DetectedBrowserTest::IE11_USER_AGENT_STRING, 'test-plan-unsupported-browser-ie11'],
            [DetectedBrowserTest::EDGE_LEGACY_USER_AGENT_STRING, 'test-plan-unsupported-browser-edge-legacy'],
        ];
    }
}
