<?php
/**
 * Copyright (c) Enalean, 2018 - present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\BreadCrumbDropdown;

use PFUser;
use Project;
use Tuleap\AgileDashboard\AgileDashboardServiceHomepageUrlBuilder;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumb;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLink;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLinkCollection;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbSubItems;
use Tuleap\Layout\BreadCrumbDropdown\SubItemsUnlabelledSection;

class AgileDashboardCrumbBuilder
{
    /**
     *
     * @return BreadCrumb
     */
    public function build(PFUser $user, Project $project)
    {
        $label = dgettext('tuleap-agiledashboard', 'Backlog');

        $agile_breadcrumb = new BreadCrumb(
            new BreadCrumbLink(
                $label,
                AgileDashboardServiceHomepageUrlBuilder::getTopBacklogUrl($project),
            )
        );

        if ($user->isAdmin($project->getID())) {
            $this->addAdministrationLink($project, $agile_breadcrumb);
        }

        return $agile_breadcrumb;
    }

    private function addAdministrationLink(Project $project, BreadCrumb $agile_breadcrumb): void
    {
        $admin_url = AGILEDASHBOARD_BASE_URL . '/?' .
            http_build_query(
                [
                    'group_id' => $project->getID(),
                    'action'   => 'admin',
                ]
            );

        $link = new BreadCrumbLink(
            $GLOBALS['Language']->getText('global', 'Administration'),
            $admin_url
        );
        $link->setDataAttribute('test', 'link-to-ad-administration');


        $sub_items = new BreadCrumbSubItems();
        $sub_items->addSection(new SubItemsUnlabelledSection(new BreadCrumbLinkCollection([$link])));
        $agile_breadcrumb->setSubItems($sub_items);
    }
}
