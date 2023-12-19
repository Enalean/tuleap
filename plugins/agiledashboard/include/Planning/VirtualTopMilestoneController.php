<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

use Tuleap\AgileDashboard\AgileDashboard\Milestone\Backlog\RecentlyVisitedTopBacklogDao;
use Tuleap\AgileDashboard\BaseController;
use Tuleap\AgileDashboard\BreadCrumbDropdown\AgileDashboardCrumbBuilder;
use Tuleap\AgileDashboard\CSRFSynchronizerTokenProvider;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbCollection;
use Tuleap\Layout\HeaderConfigurationBuilder;
use Tuleap\Option\Option;

/**
 * Handles the HTTP actions related to a planning milestone.
 */
final class VirtualTopMilestoneController extends BaseController
{
    /** @var Option<\Planning_VirtualTopMilestone> */
    private Option $milestone;
    private \Project $project;

    /**
     * Instanciates a new controller.
     *
     * TODO:
     *   - pass $request to actions (e.g. show).
     *
     */
    public function __construct(
        \Codendi_Request $request,
        \Planning_MilestoneFactory $milestone_factory,
        \ProjectManager $project_manager,
        private readonly VirtualTopMilestonePresenterBuilder $presenter_builder,
        private readonly AgileDashboardCrumbBuilder $agile_dashboard_crumb_builder,
        private readonly CSRFSynchronizerTokenProvider $token_provider,
        private readonly RecentlyVisitedTopBacklogDao $recently_visited_top_backlog_dao,
    ) {
        parent::__construct('agiledashboard', $request);
        $this->project = $project_manager->getProject($request->get('group_id'));
        try {
            $this->milestone = Option::fromValue(
                $milestone_factory->getVirtualTopMilestone($this->getCurrentUser(), $this->project)
            );
        } catch (\Planning_NoPlanningsException) {
            $this->milestone = Option::nothing(\Planning_VirtualTopMilestone::class);
        }
    }

    /**
     * @param \Closure(string $title, BreadCrumbCollection $breadcrumbs, \Tuleap\Layout\HeaderConfiguration $header_configuration): void $displayHeader
     * @param \Closure(): void $displayFooter
     */
    public function showTop(\Closure $displayHeader, \Closure $displayFooter): void
    {
        $current_user = $this->getCurrentUser();
        if (! $current_user->isAnonymous()) {
            $this->recently_visited_top_backlog_dao->save(
                (int) $current_user->getId(),
                (int) $this->project->getID(),
                $_SERVER['REQUEST_TIME'] ?? (new \DateTimeImmutable())->getTimestamp(),
            );
        }

        $presenter = $this->presenter_builder->buildPresenter(
            $this->milestone,
            $this->project,
            $current_user,
            $this->token_provider->getCSRF($this->project),
        );

        $title = sprintf(
            dgettext('tuleap-agiledashboard', '%s backlog'),
            $this->project->getPublicName()
        );

        $displayHeader(
            $title,
            $this->getBreadcrumbs(),
            HeaderConfigurationBuilder::get($title)
                ->inProject($this->project, \AgileDashboardPlugin::PLUGIN_SHORTNAME)
                ->build()
        );
        echo $this->renderToString('show-top', $presenter);
        $displayFooter();
    }

    public function getBreadcrumbs(): BreadCrumbCollection
    {
        $breadcrumb_dropdowns = new BreadCrumbCollection();
        $breadcrumb_dropdowns->addBreadCrumb(
            $this->agile_dashboard_crumb_builder->build($this->getCurrentUser(), $this->project)
        );

        return $breadcrumb_dropdowns;
    }
}
