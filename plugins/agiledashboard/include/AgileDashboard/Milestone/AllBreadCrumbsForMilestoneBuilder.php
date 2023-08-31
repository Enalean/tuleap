<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Milestone;

use PFUser;
use Planning_Milestone;
use Project;
use Tuleap\AgileDashboard\BreadCrumbDropdown\AgileDashboardCrumbBuilder;
use Tuleap\AgileDashboard\BreadCrumbDropdown\MilestoneCrumbBuilder;
use Tuleap\AgileDashboard\BreadCrumbDropdown\VirtualTopMilestoneCrumbBuilder;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbCollection;

class AllBreadCrumbsForMilestoneBuilder
{
    public function __construct(
        private readonly AgileDashboardCrumbBuilder $agile_dashboard_crumb_builder,
        private readonly VirtualTopMilestoneCrumbBuilder $top_milestone_crumb_builder,
        private readonly MilestoneCrumbBuilder $milestone_crumb_builder,
        private readonly \Tuleap\Kanban\SplitKanbanConfigurationChecker $split_kanban_configuration_checker,
    ) {
    }

    public function getBreadcrumbs(PFUser $user, Project $project, Planning_Milestone $milestone): BreadCrumbCollection
    {
        $breadcrumbs = new BreadCrumbCollection();
        $breadcrumbs->addBreadCrumb(
            $this->agile_dashboard_crumb_builder->build($user, $project)
        );
        if (! $this->split_kanban_configuration_checker->isProjectAllowedToUseSplitKanban($project)) {
            $breadcrumbs->addBreadCrumb(
                $this->top_milestone_crumb_builder->build($project)
            );
        }

        if ($milestone->getArtifact()) {
            foreach (array_reverse($milestone->getAncestors()) as $ancestor) {
                $breadcrumbs->addBreadCrumb($this->milestone_crumb_builder->build($user, $ancestor));
            }
            $breadcrumbs->addBreadCrumb($this->milestone_crumb_builder->build($user, $milestone));
        }

        return $breadcrumbs;
    }
}
