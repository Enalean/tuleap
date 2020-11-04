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

use AgileDashboardPlugin;
use HTTPRequest;
use PFUser;
use Project;
use ProjectManager;
use Service;
use TemplateRenderer;
use Tuleap\AgileDashboard\BreadCrumbDropdown\AdministrationCrumbBuilder;
use Tuleap\AgileDashboard\BreadCrumbDropdown\AgileDashboardCrumbBuilder;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbCollection;
use Tuleap\Layout\CssAsset;
use Tuleap\Layout\CssAssetWithoutVariantDeclinaisons;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithProject;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\ScaledAgile\Program\Administration\PlannableItems\PlannableItemsCollectionBuilder;
use Tuleap\ScaledAgile\Program\Administration\PlannableItems\Presenter\PlannableItemsPerTeamPresenterCollectionBuilder;
use Tuleap\ScaledAgile\Adapter\Program\PlanningAdapter;
use Tuleap\ScaledAgile\Program\PlanningConfiguration\PlanningFotFoundException;
use Tuleap\ScaledAgile\Program\PlanningConfiguration\TopPlanningNotFoundInProjectException;
use Tuleap\ScaledAgile\Adapter\ProjectDataAdapter;
use Tuleap\ScaledAgile\TrackerNotFoundException;

final class ReadOnlyProgramAdminViewController implements DispatchableWithRequest, DispatchableWithProject, DispatchableWithBurningParrot
{
    /**
     * @var ProjectManager
     */
    private $project_manager;

    /**
     * @var PlanningAdapter
     */
    private $planning_adapter;

    /**
     * @var AgileDashboardCrumbBuilder
     */
    private $service_crumb_builder;

    /**
     * @var AdministrationCrumbBuilder
     */
    private $administration_crumb_builder;

    /**
     * @var TemplateRenderer
     */
    private $template_renderer;

    /**
     * @var PlannableItemsCollectionBuilder
     */
    private $plannable_items_collection_builder;

    /**
     * @var PlannableItemsPerTeamPresenterCollectionBuilder
     */
    private $per_team_presenter_collection_builder;

    /**
     * @var IncludeAssets
     */
    private $assets_scaled_agile;
    /**
     * @var IncludeAssets
     */
    private $assets_agile_dashboard;

    public function __construct(
        ProjectManager $project_manager,
        PlanningAdapter $planning_adapter,
        AgileDashboardCrumbBuilder $service_crumb_builder,
        AdministrationCrumbBuilder $administration_crumb_builder,
        TemplateRenderer $template_renderer,
        PlannableItemsCollectionBuilder $plannable_items_collection_builder,
        PlannableItemsPerTeamPresenterCollectionBuilder $per_team_presenter_collection_builder,
        IncludeAssets $assets_scaled_agile,
        IncludeAssets $assets_agile_dashboard
    ) {
        $this->project_manager                       = $project_manager;
        $this->planning_adapter                      = $planning_adapter;
        $this->service_crumb_builder                 = $service_crumb_builder;
        $this->administration_crumb_builder          = $administration_crumb_builder;
        $this->template_renderer                     = $template_renderer;
        $this->plannable_items_collection_builder    = $plannable_items_collection_builder;
        $this->per_team_presenter_collection_builder = $per_team_presenter_collection_builder;
        $this->assets_scaled_agile                   = $assets_scaled_agile;
        $this->assets_agile_dashboard                = $assets_agile_dashboard;
    }

    public function getProject(array $variables): Project
    {
        $project = $this->project_manager->getProjectByCaseInsensitiveUnixName($variables['project_name']);
        if (! $project || $project->isError()) {
            throw new NotFoundException(dgettext("tuleap-scaled_agile", "Project not found."));
        }

        return $project;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $project = $this->getProject($variables);

        $service = $project->getService(AgileDashboardPlugin::PLUGIN_SHORTNAME);
        if ($service === null) {
            throw new NotFoundException(dgettext("tuleap-scaled_agile", "AgileDashboard service not used in project."));
        }

        $project_data = ProjectDataAdapter::build($project);

        $user = $request->getCurrentUser();

        if (! $user->isAdmin($project_data->getId())) {
            throw new ForbiddenException(dgettext("tuleap-scaled_agile", "You are not AgileDashboard admin."));
        }

        try {
            $planning = $this->planning_adapter->buildPlanningById((int) $variables['id']);
        } catch (PlanningFotFoundException $e) {
            throw new NotFoundException(dgettext("tuleap-scaled_agile", "This planning does not exist."));
        }

        if ((int) $planning->getProjectData()->getId() !== $project_data->getId()) {
            throw new NotFoundException(dgettext("tuleap-scaled_agile", "This planning does not belong to the project."));
        }

        try {
            $project_root_planning = $this->planning_adapter->buildRootPlanning(
                $user,
                $project_data->getID()
            );
        } catch (TopPlanningNotFoundInProjectException $e) {
            throw new NotFoundException(dgettext("tuleap-scaled_agile", "There is no root planning in the project."));
        }

        if ($planning->getId() !== $project_root_planning->getId()) {
            throw new NotFoundException(
                dgettext("tuleap-scaled_agile", "This planning is not the root planning of the project.")
            );
        }

        $layout->addCssAsset(new CssAsset($this->assets_agile_dashboard, 'administration'));
        $layout->addCssAsset(new CssAssetWithoutVariantDeclinaisons($this->assets_scaled_agile, 'program-admin-style'));

        $this->displayHeader($service, $user, $project);

        try {
            $plannable_items_collection = $this->plannable_items_collection_builder->buildCollection(
                $project_root_planning
            );
        } catch (TrackerNotFoundException $exception) {
            throw new NotFoundException(
                dgettext("tuleap-scaled_agile", "One of the planning tracker can not be found")
            );
        }

        $plannable_items_presenter_collection = $this->per_team_presenter_collection_builder
            ->buildPresenterCollectionFromObjectCollection($user, $plannable_items_collection);

        $program_admin_presenter = new ProgramAdminPresenter(
            $project_data,
            $plannable_items_presenter_collection,
            $planning->getName()
        );

        $this->template_renderer->renderToPage(
            'program-admin',
            $program_admin_presenter
        );

        $layout->footer([]);
    }

    /**
     * @throws NotFoundException
     */
    private function displayHeader(Service $service, PFUser $user, Project $project): void
    {
        $service->displayHeader(
            dgettext("tuleap-scaled_agile", "Edit"),
            $this->getBreadcrumbs($user, $project),
            [],
            []
        );
    }

    private function getBreadcrumbs(PFUser $user, Project $project): BreadCrumbCollection
    {
        $breadcrumbs = new BreadCrumbCollection();
        $breadcrumbs->addBreadCrumb(
            $this->service_crumb_builder->build(
                $user,
                $project
            )
        );
        $breadcrumbs->addBreadCrumb(
            $this->administration_crumb_builder->build($project)
        );

        return $breadcrumbs;
    }
}
