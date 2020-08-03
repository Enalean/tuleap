<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\MultiProjectBacklog\Aggregator;

use HTTPRequest;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Planning;
use PlanningFactory;
use Project;
use ProjectManager;
use Service;
use TemplateRenderer;
use Tuleap\AgileDashboard\BreadCrumbDropdown\AdministrationCrumbBuilder;
use Tuleap\AgileDashboard\BreadCrumbDropdown\AgileDashboardCrumbBuilder;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumb;
use Tuleap\Layout\IncludeAssets;
use Tuleap\MultiProjectBacklog\Aggregator\PlannableItems\PlannableItemsCollectionBuilder;
use Tuleap\MultiProjectBacklog\Aggregator\PlannableItems\Presenter\PlannableItemsPerContributorPresenterCollection;
use Tuleap\MultiProjectBacklog\Aggregator\PlannableItems\Presenter\PlannableItemsPerContributorPresenterCollectionBuilder;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class ReadOnlyAggregatorAdminViewControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var ReadOnlyAggregatorAdminViewController
     */
    private $controller;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ProjectManager
     */
    private $project_manager;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PlanningFactory
     */
    private $planning_factory;

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
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PlannableItemsPerContributorPresenterCollectionBuilder
     */
    private $per_contributor_presenter_collection_builder;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|IncludeAssets
     */
    private $include_assests;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project_manager                              = Mockery::mock(ProjectManager::class);
        $this->planning_factory                             = Mockery::mock(PlanningFactory::class);
        $this->service_crumb_builder                        = Mockery::mock(AgileDashboardCrumbBuilder::class);
        $this->administration_crumb_builder                 = Mockery::mock(AdministrationCrumbBuilder::class);
        $this->template_renderer                            = Mockery::mock(TemplateRenderer::class);
        $this->plannable_items_collection_builder           = Mockery::mock(PlannableItemsCollectionBuilder::class);
        $this->per_contributor_presenter_collection_builder = Mockery::mock(PlannableItemsPerContributorPresenterCollectionBuilder::class);
        $this->include_assests                              = Mockery::mock(IncludeAssets::class);

        $this->controller = new ReadOnlyAggregatorAdminViewController(
            $this->project_manager,
            $this->planning_factory,
            $this->service_crumb_builder,
            $this->administration_crumb_builder,
            $this->template_renderer,
            $this->plannable_items_collection_builder,
            $this->per_contributor_presenter_collection_builder,
            $this->include_assests
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

        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getID')->andReturn(143);
        $project->shouldReceive('isError')->andReturnFalse();
        $project->shouldReceive('getPublicName')->andReturn('Project 01');

        $this->project_manager->shouldReceive('getProjectByCaseInsensitiveUnixName')
            ->once()
            ->with('proj01')
            ->andReturn($project);

        $service = Mockery::mock(Service::class);
        $project->shouldReceive('getService')->once()->with('plugin_agiledashboard')->andReturn($service);

        $user = Mockery::mock(PFUser::class);
        $request->shouldReceive('getCurrentUser')->once()->andReturn($user);

        $user->shouldReceive('isAdmin')->with(143)->andReturnTrue();

        $planning = Mockery::mock(Planning::class);
        $this->planning_factory->shouldReceive('getPlanning')->with(43)->andReturn($planning);

        $planning->shouldReceive('getGroupId')->andReturn(143);
        $planning->shouldReceive('getId')->andReturn(43);
        $planning->shouldReceive('getName')->andReturn('Planning');

        $root_planning = Mockery::mock(Planning::class);
        $this->planning_factory->shouldReceive('getRootPlanning')->with($user, 143)->andReturn($root_planning);

        $root_planning->shouldReceive('getId')->andReturn(43);

        $this->plannable_items_collection_builder->shouldReceive('buildCollection')->once();

        $collection = Mockery::mock(PlannableItemsPerContributorPresenterCollection::class);
        $this->per_contributor_presenter_collection_builder->shouldReceive('buildPresenterCollectionFromObjectCollection')
            ->once()
            ->andReturn($collection);

        $collection->shouldReceive('getPlannableItemsPerContributorPresenters')->once();

        $this->service_crumb_builder->shouldReceive('build')->once()->andReturn(Mockery::mock(BreadCrumb::class));
        $this->administration_crumb_builder->shouldReceive('build')->once()->andReturn(Mockery::mock(BreadCrumb::class));

        $service->shouldReceive('displayHeader')->once();
        $layout->shouldReceive('footer')->once();
        $layout->shouldReceive('addCssAsset')->once();
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

        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getID')->andReturn(143);
        $project->shouldReceive('isError')->andReturnFalse();

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
            'id' => '43',
            'project_name' => 'proj01'
        ];

        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getID')->andReturn(143);
        $project->shouldReceive('isError')->andReturnFalse();

        $this->project_manager->shouldReceive('getProjectByCaseInsensitiveUnixName')
            ->once()
            ->with('proj01')
            ->andReturn($project);

        $service = Mockery::mock(Service::class);
        $project->shouldReceive('getService')->once()->with('plugin_agiledashboard')->andReturn($service);

        $user = Mockery::mock(PFUser::class);
        $request->shouldReceive('getCurrentUser')->once()->andReturn($user);

        $user->shouldReceive('isAdmin')->with(143)->andReturnFalse();

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

    public function testItThrowsAnExceptionIfProvidedPlanningDoesNotExist(): void
    {
        $request   = Mockery::mock(HTTPRequest::class);
        $layout    = Mockery::mock(BaseLayout::class);
        $variables = [
            'id' => '43',
            'project_name' => 'proj01'
        ];

        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getID')->andReturn(143);
        $project->shouldReceive('isError')->andReturnFalse();

        $this->project_manager->shouldReceive('getProjectByCaseInsensitiveUnixName')
            ->once()
            ->with('proj01')
            ->andReturn($project);

        $service = Mockery::mock(Service::class);
        $project->shouldReceive('getService')->once()->with('plugin_agiledashboard')->andReturn($service);

        $user = Mockery::mock(PFUser::class);
        $request->shouldReceive('getCurrentUser')->once()->andReturn($user);

        $user->shouldReceive('isAdmin')->with(143)->andReturnTrue();

        $this->planning_factory->shouldReceive('getPlanning')->with(43)->andReturnNull();

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

    public function testItThrowsAnExceptionIfProvidedPlanningDoesNotBelongToProject(): void
    {
        $request   = Mockery::mock(HTTPRequest::class);
        $layout    = Mockery::mock(BaseLayout::class);
        $variables = [
            'id' => '43',
            'project_name' => 'proj01'
        ];

        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getID')->andReturn(143);
        $project->shouldReceive('isError')->andReturnFalse();

        $this->project_manager->shouldReceive('getProjectByCaseInsensitiveUnixName')
            ->once()
            ->with('proj01')
            ->andReturn($project);

        $service = Mockery::mock(Service::class);
        $project->shouldReceive('getService')->once()->with('plugin_agiledashboard')->andReturn($service);

        $user = Mockery::mock(PFUser::class);
        $request->shouldReceive('getCurrentUser')->once()->andReturn($user);

        $user->shouldReceive('isAdmin')->with(143)->andReturnTrue();

        $planning = Mockery::mock(Planning::class);
        $this->planning_factory->shouldReceive('getPlanning')->with(43)->andReturn($planning);

        $planning->shouldReceive('getGroupId')->andReturn(144);

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

    public function testItThrowsAnExceptionIfThereIsNoRootPlanningInProject(): void
    {
        $request   = Mockery::mock(HTTPRequest::class);
        $layout    = Mockery::mock(BaseLayout::class);
        $variables = [
            'id' => '43',
            'project_name' => 'proj01'
        ];

        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getID')->andReturn(143);
        $project->shouldReceive('isError')->andReturnFalse();

        $this->project_manager->shouldReceive('getProjectByCaseInsensitiveUnixName')
            ->once()
            ->with('proj01')
            ->andReturn($project);

        $service = Mockery::mock(Service::class);
        $project->shouldReceive('getService')->once()->with('plugin_agiledashboard')->andReturn($service);

        $user = Mockery::mock(PFUser::class);
        $request->shouldReceive('getCurrentUser')->once()->andReturn($user);

        $user->shouldReceive('isAdmin')->with(143)->andReturnTrue();

        $planning = Mockery::mock(Planning::class);
        $this->planning_factory->shouldReceive('getPlanning')->with(43)->andReturn($planning);

        $planning->shouldReceive('getGroupId')->andReturn(143);
        $planning->shouldReceive('getId')->andReturn(43);

        $this->planning_factory->shouldReceive('getRootPlanning')->with($user, 143)->andReturnNull();

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
            'id' => '43',
            'project_name' => 'proj01'
        ];

        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getID')->andReturn(143);
        $project->shouldReceive('isError')->andReturnFalse();

        $this->project_manager->shouldReceive('getProjectByCaseInsensitiveUnixName')
            ->once()
            ->with('proj01')
            ->andReturn($project);

        $service = Mockery::mock(Service::class);
        $project->shouldReceive('getService')->once()->with('plugin_agiledashboard')->andReturn($service);

        $user = Mockery::mock(PFUser::class);
        $request->shouldReceive('getCurrentUser')->once()->andReturn($user);

        $user->shouldReceive('isAdmin')->with(143)->andReturnTrue();

        $planning = Mockery::mock(Planning::class);
        $this->planning_factory->shouldReceive('getPlanning')->with(43)->andReturn($planning);

        $planning->shouldReceive('getGroupId')->andReturn(143);
        $planning->shouldReceive('getId')->andReturn(43);

        $root_planning = Mockery::mock(Planning::class);
        $this->planning_factory->shouldReceive('getRootPlanning')->with($user, 143)->andReturn($root_planning);

        $root_planning->shouldReceive('getId')->andReturn(44);

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
}
