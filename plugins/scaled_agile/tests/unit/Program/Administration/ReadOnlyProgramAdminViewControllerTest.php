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

namespace Tuleap\ScaledAgile\Program\Administration;

use HTTPRequest;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Planning;
use Project;
use ProjectManager;
use Service;
use TemplateRenderer;
use Tuleap\AgileDashboard\BreadCrumbDropdown\AdministrationCrumbBuilder;
use Tuleap\AgileDashboard\BreadCrumbDropdown\AgileDashboardCrumbBuilder;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumb;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\ScaledAgile\Program\Administration\PlannableItems\PlannableItemsCollectionBuilder;
use Tuleap\ScaledAgile\Program\Administration\PlannableItems\Presenter\PlannableItemsPerTeamPresenterCollection;
use Tuleap\ScaledAgile\Program\Administration\PlannableItems\Presenter\PlannableItemsPerTeamPresenterCollectionBuilder;
use Tuleap\ScaledAgile\Adapter\Program\PlanningAdapter;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class ReadOnlyProgramAdminViewControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\PlanningFactory
     */
    private $planning_factory;

    /**
     * @var ReadOnlyProgramAdminViewController
     */
    private $controller;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ProjectManager
     */
    private $project_manager;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|AgileDashboardCrumbBuilder
     */
    private $service_crumb_builder;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|AdministrationCrumbBuilder
     */
    private $administration_crumb_builder;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TemplateRenderer
     */
    private $template_renderer;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PlannableItemsCollectionBuilder
     */
    private $plannable_items_collection_builder;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PlannableItemsPerTeamPresenterCollectionBuilder
     */
    private $per_team_presenter_collection_builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project_manager                       = Mockery::mock(ProjectManager::class);
        $this->planning_factory                      = Mockery::mock(\PlanningFactory::class);
        $planning_adapter                            = new PlanningAdapter($this->planning_factory);
        $this->service_crumb_builder                 = Mockery::mock(AgileDashboardCrumbBuilder::class);
        $this->administration_crumb_builder          = Mockery::mock(AdministrationCrumbBuilder::class);
        $this->template_renderer                     = Mockery::mock(TemplateRenderer::class);
        $this->plannable_items_collection_builder    = Mockery::mock(PlannableItemsCollectionBuilder::class);
        $this->per_team_presenter_collection_builder = Mockery::mock(
            PlannableItemsPerTeamPresenterCollectionBuilder::class
        );

        $this->controller = new ReadOnlyProgramAdminViewController(
            $this->project_manager,
            $planning_adapter,
            $this->service_crumb_builder,
            $this->administration_crumb_builder,
            $this->template_renderer,
            $this->plannable_items_collection_builder,
            $this->per_team_presenter_collection_builder,
            Mockery::mock(IncludeAssets::class),
            Mockery::mock(IncludeAssets::class)
        );
    }

    public function testItDisplaysThePage(): void
    {
        $request   = Mockery::mock(HTTPRequest::class);
        $layout    = Mockery::mock(BaseLayout::class);
        $variables = [
            'id' => '43',
            'project_name' => 'proj01'
        ];

        $project = $this->getMockedProject();

        $this->project_manager->shouldReceive('getProjectByCaseInsensitiveUnixName')
            ->once()
            ->with('proj01')
            ->andReturn($project);

        $service = Mockery::mock(Service::class);
        $project->shouldReceive('getService')->once()->with('plugin_agiledashboard')->andReturn($service);

        $user = Mockery::mock(PFUser::class);
        $request->shouldReceive('getCurrentUser')->once()->andReturn($user);

        $user->shouldReceive('isAdmin')->with($project->getID())->andReturnTrue();

        $planning_tracker = TrackerTestBuilder::aTracker()->withId(1)->withProject($project)->build();
        $planning         = new Planning(43, 'Planning', $project->getID(), '', [302, 504]);
        $planning->setPlanningTracker($planning_tracker);
        $this->planning_factory->shouldReceive('getPlanning')->with(43)->andReturn($planning);

        $root_tracker  = TrackerTestBuilder::aTracker()->withId(1)->withProject($project)->build();
        $root_planning = new Planning(43, 'Release O1 Planning', $project->getID(), '', []);
        $root_planning->setPlanningTracker($root_tracker);
        $this->planning_factory->shouldReceive('getRootPlanning')->with($user, $project->getID())->andReturn($root_planning);

        $this->plannable_items_collection_builder->shouldReceive('buildCollection')->once();

        $collection = Mockery::mock(PlannableItemsPerTeamPresenterCollection::class);
        $this->per_team_presenter_collection_builder->shouldReceive('buildPresenterCollectionFromObjectCollection')
            ->once()
            ->andReturn($collection);

        $collection->shouldReceive('getPlannableItemsPerTeamPresenters')->once();

        $this->service_crumb_builder->shouldReceive('build')->once()->andReturn(Mockery::mock(BreadCrumb::class));
        $this->administration_crumb_builder->shouldReceive('build')->once()->andReturn(Mockery::mock(BreadCrumb::class));

        $service->shouldReceive('displayHeader')->once();
        $layout->shouldReceive('footer')->once();
        $layout->shouldReceive('addCssAsset')->twice();
        $this->template_renderer->shouldReceive('renderToPage')->once();

        $this->controller->process(
            $request,
            $layout,
            $variables
        );
    }

    public function testItThrowsAnExceptionIfProjectNotFound(): void
    {
        $request   = Mockery::mock(HTTPRequest::class);
        $layout    = Mockery::mock(BaseLayout::class);
        $variables = [
            'id' => '43',
            'project_name' => 'proj01'
        ];

        $this->project_manager->shouldReceive('getProjectByCaseInsensitiveUnixName')
            ->once()
            ->with('proj01')
            ->andReturnNull();

        $layout->shouldReceive('footer')->never();
        $this->template_renderer->shouldReceive('renderToPage')->never();

        $this->expectException(NotFoundException::class);

        $this->controller->process(
            $request,
            $layout,
            $variables
        );
    }

    public function testItThrowsAnExceptionIfProjectDoesNotUseADService(): void
    {
        $request   = Mockery::mock(HTTPRequest::class);
        $layout    = Mockery::mock(BaseLayout::class);
        $variables = [
            'id' => '43',
            'project_name' => 'proj01'
        ];

        $project = $this->getMockedProject();

        $this->project_manager->shouldReceive('getProjectByCaseInsensitiveUnixName')
            ->once()
            ->with('proj01')
            ->andReturn($project);

        $project->shouldReceive('getService')->once()->with('plugin_agiledashboard')->andReturnNull();

        $layout->shouldReceive('footer')->never();
        $layout->shouldReceive('addCssAsset')->never();
        $this->template_renderer->shouldReceive('renderToPage')->never();

        $this->expectException(NotFoundException::class);

        $this->controller->process(
            $request,
            $layout,
            $variables
        );
    }

    public function testItThrowsAnExceptionIfUserIsNotProjectAdmin(): void
    {
        $request   = Mockery::mock(HTTPRequest::class);
        $layout    = Mockery::mock(BaseLayout::class);
        $variables = [
            'id'           => '43',
            'project_name' => 'proj01'
        ];

        $project = $this->getMockedProject();

        $this->project_manager->shouldReceive('getProjectByCaseInsensitiveUnixName')
            ->once()
            ->with('proj01')
            ->andReturn($project);

        $service = Mockery::mock(Service::class);
        $project->shouldReceive('getService')->once()->with('plugin_agiledashboard')->andReturn($service);

        $user = Mockery::mock(PFUser::class);
        $request->shouldReceive('getCurrentUser')->once()->andReturn($user);

        $user->shouldReceive('isAdmin')->with($project->getID())->andReturnFalse();

        $service->shouldReceive('displayHeader')->never();
        $layout->shouldReceive('footer')->never();
        $layout->shouldReceive('addCssAsset')->never();
        $this->template_renderer->shouldReceive('renderToPage')->never();

        $this->expectException(ForbiddenException::class);

        $this->controller->process(
            $request,
            $layout,
            $variables
        );
    }

    public function testItThrowsAnExceptionIfProvidedPlanningDoesNotBelongToProject(): void
    {
        $request   = Mockery::mock(HTTPRequest::class);
        $layout    = Mockery::mock(BaseLayout::class);
        $variables = [
            'id'           => '43',
            'project_name' => 'proj01'
        ];

        $project = $this->getMockedProject();

        $this->project_manager->shouldReceive('getProjectByCaseInsensitiveUnixName')
            ->once()
            ->with('proj01')
            ->andReturn($project);

        $service = Mockery::mock(Service::class);
        $project->shouldReceive('getService')->once()->with('plugin_agiledashboard')->andReturn($service);

        $user = Mockery::mock(PFUser::class);
        $request->shouldReceive('getCurrentUser')->once()->andReturn($user);

        $user->shouldReceive('isAdmin')->with($project->getID())->andReturnTrue();

        $other_project    = new Project(['group_id' => 666, 'group_name' => 'Other', 'unix_group_name' => 'other']);
        $planning_tracker = TrackerTestBuilder::aTracker()->withId(1)->withProject($other_project)->build();
        $planning         = new Planning(43, 'Planning', [302, 504], $project->getID(), '');
        $planning->setPlanningTracker($planning_tracker);
        $this->planning_factory->shouldReceive('getPlanning')->with(43)->andReturn($planning);

        $service->shouldReceive('displayHeader')->never();
        $layout->shouldReceive('footer')->never();
        $layout->shouldReceive('addCssAsset')->never();
        $this->template_renderer->shouldReceive('renderToPage')->never();

        $this->expectException(NotFoundException::class);

        $this->controller->process(
            $request,
            $layout,
            $variables
        );
    }

    public function testItThrowsAnExceptionIfProvidedPlanningIsNotRootPlanningInProject(): void
    {
        $request   = Mockery::mock(HTTPRequest::class);
        $layout    = Mockery::mock(BaseLayout::class);
        $variables = [
            'id'           => '43',
            'project_name' => 'proj01'
        ];

        $project = $this->getMockedProject();

        $this->project_manager->shouldReceive('getProjectByCaseInsensitiveUnixName')
            ->once()
            ->with('proj01')
            ->andReturn($project);

        $service = Mockery::mock(Service::class);
        $project->shouldReceive('getService')->once()->with('plugin_agiledashboard')->andReturn($service);

        $user = Mockery::mock(PFUser::class);
        $request->shouldReceive('getCurrentUser')->once()->andReturn($user);

        $user->shouldReceive('isAdmin')->with($project->getID())->andReturnTrue();

        $planning_tracker = TrackerTestBuilder::aTracker()->withId(1)->withProject($project)->build();
        $planning         = new Planning(43, 'Planning', [302, 504], $project->getID(), '');
        $planning->setPlanningTracker($planning_tracker);
        $this->planning_factory->shouldReceive('getPlanning')->with(43)->andReturn($planning);

        $root_tracker  = TrackerTestBuilder::aTracker()->withId(2)->withProject($project)->build();
        $root_planning = new Planning(44, 'Planning', $project->getId(), '', [302, 504]);
        $root_planning->setPlanningTracker($root_tracker);
        $this->planning_factory->shouldReceive('getRootPlanning')->with($user, $project->getID())->andReturn($root_planning);

        $service->shouldReceive('displayHeader')->never();
        $layout->shouldReceive('footer')->never();
        $layout->shouldReceive('addCssAsset')->never();
        $this->template_renderer->shouldReceive('renderToPage')->never();

        $this->expectException(NotFoundException::class);

        $this->controller->process(
            $request,
            $layout,
            $variables
        );
    }

    /**
     * @return Mockery\LegacyMockInterface|Mockery\MockInterface|Project
     */
    private function getMockedProject()
    {
        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getID')->andReturn(143);
        $project->shouldReceive('isError')->andReturnFalse();
        $project->shouldReceive('getPublicName')->andReturn('Project 01');
        $project->shouldReceive('getUnixName')->andReturn('project_01');

        return $project;
    }
}
