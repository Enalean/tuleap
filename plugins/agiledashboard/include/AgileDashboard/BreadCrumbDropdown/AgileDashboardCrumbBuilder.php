<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbItem;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbSubItemCollection;

class AgileDashboardCrumbBuilder
{
    /**
     * @param PFUser $user
     * @param Project $project
     * @param string $plugin_path
     * @return BreadCrumbItem
     */
    public function build(PFUser $user, Project $project, $plugin_path)
    {
        $service_dropdown_items = new BreadCrumbSubItemCollection();
        if ($user->isAdmin($project->getID())) {
            $admin_url = AGILEDASHBOARD_BASE_URL . '/?' . http_build_query(
                [
                    'group_id' => $project->getID(),
                    'action'   => 'admin',
                ]
            );

            $service_dropdown_items->addBreadCrumb(
                new BreadCrumbItem(
                    $GLOBALS['Language']->getText('global', 'Administration'),
                    $admin_url
                )
            );
        }

        $service_url      = $plugin_path . '/? ' . http_build_query(['group_id' => $project->getID()]);
        $agile_breadcrumb = new BreadCrumbItem(
            dgettext('tuleap-agiledashboard', 'Agile Dashboard'),
            $service_url
        );
        $agile_breadcrumb->setIconName('fa-table');
        $agile_breadcrumb->setSubItems($service_dropdown_items);

        return $agile_breadcrumb;
    }
}
