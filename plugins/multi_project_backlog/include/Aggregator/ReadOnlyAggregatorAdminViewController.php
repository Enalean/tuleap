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

use AgileDashboardPlugin;
use HTTPRequest;
use PFUser;
use PlanningFactory;
use Project;
use ProjectManager;
use Service;
use TemplateRenderer;
use Tuleap\AgileDashboard\BreadCrumbDropdown\AdministrationCrumbBuilder;
use Tuleap\AgileDashboard\BreadCrumbDropdown\AgileDashboardCrumbBuilder;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbCollection;
use Tuleap\MultiProjectBacklog\Aggregator\PlannableItems\PlannableItemsCollectionBuilder;
use Tuleap\MultiProjectBacklog\Aggregator\PlannableItems\Presenter\PlannableItemsPerContributorPresenterCollectionBuilder;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithProject;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

final class ReadOnlyAggregatorAdminViewController implements DispatchableWithRequest, DispatchableWithProject, DispatchableWithBurningParrot
{
    /**
     * @var ProjectManager
     */
    private $project_manager;

    /**
     * @var PlanningFactory
     */
    private $planning_factory;

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
     * @var PlannableItemsPerContributorPresenterCollectionBuilder
     */
    private $per_project_presenter_collection_builder;

    public function __construct(
        ProjectManager $project_manager,
        PlanningFactory $planning_factory,
        AgileDashboardCrumbBuilder $service_crumb_builder,
        AdministrationCrumbBuilder $administration_crumb_builder,
        TemplateRenderer $template_renderer,
        PlannableItemsCollectionBuilder $plannable_items_collection_builder,
        PlannableItemsPerContributorPresenterCollectionBuilder $plannable_items_per_project_presenter_collection_builder
    ) {
        $this->project_manager                          = $project_manager;
        $this->planning_factory                         = $planning_factory;
        $this->service_crumb_builder                    = $service_crumb_builder;
        $this->administration_crumb_builder             = $administration_crumb_builder;
        $this->template_renderer                        = $template_renderer;
        $this->plannable_items_collection_builder       = $plannable_items_collection_builder;
        $this->per_project_presenter_collection_builder = $plannable_items_per_project_presenter_collection_builder;
    }

    public function getProject(array $variables): Project
    {
        $project = $this->project_manager->getProjectByCaseInsensitiveUnixName($variables['project_name']);
        if (! $project || $project->isError()) {
            throw new NotFoundException(dgettext("tuleap-multi_project_backlog", "Project not found."));
        }

        return $project;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $project = $this->getProject($variables);

        $service = $project->getService(AgileDashboardPlugin::PLUGIN_SHORTNAME);
        if ($service === null) {
            throw new NotFoundException(dgettext("tuleap-multi_project_backlog", "AgileDashboard service not used in project."));
        }

        $user = $request->getCurrentUser();

        if (! $user->isAdmin((int) $project->getID())) {
            throw new ForbiddenException(dgettext("tuleap-multi_project_backlog", "You are not AgileDashboard admin."));
        }

        $planning = $this->planning_factory->getPlanning((int) $variables['id']);

        if ($planning === null) {
            throw new NotFoundException(dgettext("tuleap-multi_project_backlog", "This planning does not exist."));
        }

        if ((int) $planning->getGroupId() !== (int) $project->getID()) {
            throw new NotFoundException(dgettext("tuleap-multi_project_backlog", "This planning does not belong to the project."));
        }

        $project_root_planning = $this->planning_factory->getRootPlanning(
            $user,
            (int) $project->getID()
        );

        if (! $project_root_planning) {
            throw new NotFoundException(dgettext("tuleap-multi_project_backlog", "There is no root planning in the project."));
        }

        if ((int) $planning->getId() !== (int) $project_root_planning->getId()) {
            throw new NotFoundException(dgettext("tuleap-multi_project_backlog", "This planning is not the root planning of the project."));
        }

        $this->displayHeader($service, $user, $project);

        $plannable_items_collection           = $this->plannable_items_collection_builder->buildCollection($project);
        $plannable_items_presenter_collection = $this->per_project_presenter_collection_builder
            ->buildPresenterCollectionFromObjectCollection($user, $plannable_items_collection);

        $aggregator_admin_presenter = new AggregatorAdminPresenter(
            $project,
            $plannable_items_presenter_collection,
            $planning->getName()
        );

        $this->template_renderer->renderToPage(
            'aggregator-admin',
            $aggregator_admin_presenter
        );

        $layout->footer([]);
    }

    /**
     * @throws NotFoundException
     */
    private function displayHeader(Service $service, PFUser $user, Project $project): void
    {
        $service->displayHeader(
            dgettext("tuleap-multi_project_backlog", "Edit"),
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
