<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\Kanban;

use HTTPRequest;
use Project;
use TemplateRendererFactory;
use TrackerFactory;
use Tuleap\Kanban\NewDropdown\NewDropdownCurrentContextSectionForKanbanProvider;
use Tuleap\Kanban\RecentlyVisited\RecentlyVisitedKanbanDao;
use Tuleap\Kanban\Service\KanbanService;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumb;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbCollection;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLink;
use Tuleap\Layout\CssAssetWithoutVariantDeclinaisons;
use Tuleap\Layout\HeaderConfigurationBuilder;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Project\ServiceInstrumentation;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\NotFoundException;

final class ShowKanbanController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    public function __construct(
        private readonly KanbanFactory $kanban_factory,
        private readonly TrackerFactory $tracker_factory,
        private readonly KanbanPermissionsManager $permissions_manager,
        private readonly BreadCrumbBuilder $kanban_crumb_builder,
        private readonly RecentlyVisitedKanbanDao $recently_visited_dao,
        private readonly NewDropdownCurrentContextSectionForKanbanProvider $current_context_section_for_kanban_provider,
    ) {
    }

    private function getBreadcrumbs(\PFUser $user, Project $project, Kanban $kanban): BreadCrumbCollection
    {
        $breadcrumbs = new BreadCrumbCollection();
        $breadcrumbs->addBreadCrumb(new BreadCrumb(
            new BreadCrumbLink(
                dgettext('tuleap-kanban', 'Kanban'),
                Service\KanbanServiceHomepageUrlBuilder::getUrl($project),
            )
        ));
        $breadcrumbs->addBreadCrumb($this->kanban_crumb_builder->build($user, $kanban));

        return $breadcrumbs;
    }

    #[\Override]
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        ServiceInstrumentation::increment(KanbanService::INSTRUMENTATION_NAME);

        $kanban_id = (int) $variables['id'];
        $user      = $request->getCurrentUser();

        try {
            $kanban = $this->kanban_factory->getKanban($user, $kanban_id);
            if (! $user->isAnonymous()) {
                $this->recently_visited_dao->save((int) $user->getId(), $kanban_id, \Tuleap\Request\RequestTime::getTimestamp());
            }

            $tracker = $this->tracker_factory->getTrackerById($kanban->getTrackerId());
            if ($tracker === null) {
                throw new \RuntimeException('Tracker does not exist');
            }

            $project              = $tracker->getProject();
            $user_is_kanban_admin = $this->permissions_manager->userCanAdministrate($user, $project);

            $filter_tracker_report_id = (int) $request->get('tracker_report_id');
            $dashboard_widget_id      = 0;

            $renderer = TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../../templates/');

            $service         = $this->getService($project);
            $current_section = $this->current_context_section_for_kanban_provider->getSectionByKanbanId(
                $kanban_id,
                $request->getCurrentUser()
            );

            $header_options = HeaderConfigurationBuilder::get($kanban->getName())
                ->inProjectWithActivePromotedItem(
                    $project,
                    KanbanService::SERVICE_SHORTNAME,
                    $kanban->getPromotedKanbanId()
                )
                ->withBodyClass(['reduce-help-button', 'kanban-body'])
                ->withNewDropdownLinkSection($current_section)
                ->build();

            $kanban_assets = new IncludeAssets(
                __DIR__ . '/../../scripts/kanban/frontend-assets',
                '/assets/kanban/kanban'
            );
            $provider      = new KanbanJavascriptDependenciesProvider($kanban_assets);
            foreach ($provider->getDependencies() as $dependency) {
                $layout->includeFooterJavascriptFile($dependency['file']);
            }
            $layout->addCssAsset(
                new CssAssetWithoutVariantDeclinaisons($kanban_assets, 'kanban-style')
            );

            $service->displayHeader(
                $kanban->getName(),
                $this->getBreadcrumbs($user, $project, $kanban),
                [],
                $header_options
            );
            $renderer->renderToPage(
                'kanban',
                new KanbanPresenter(
                    $kanban,
                    $user,
                    $user_is_kanban_admin,
                    $user->getShortLocale(),
                    (int) $tracker->getGroupId(),
                    $dashboard_widget_id,
                    $filter_tracker_report_id,
                )
            );
            $service->displayFooter();
        } catch (KanbanCannotAccessException | KanbanNotFoundException) {
            throw new NotFoundException(dgettext('tuleap-kanban', 'Kanban not found.'));
        }
    }

    private function getService(Project $project): \Service
    {
        $tracker_service = $project->getService(\trackerPlugin::SERVICE_SHORTNAME);
        if (! $tracker_service) {
            throw new NotFoundException();
        }

        $kanban_service = $project->getService(KanbanService::SERVICE_SHORTNAME);
        if ($kanban_service) {
            return $kanban_service;
        }

        throw new NotFoundException();
    }
}
