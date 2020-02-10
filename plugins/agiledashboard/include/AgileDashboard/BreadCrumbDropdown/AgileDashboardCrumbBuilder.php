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
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumb;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLink;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLinkCollection;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLinkWithIcon;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbSubItems;
use Tuleap\Layout\BreadCrumbDropdown\SubItemsUnlabelledSection;

class AgileDashboardCrumbBuilder
{
    /** @var string */
    private $plugin_path;

    /**
     * @param string $plugin_path
     */
    public function __construct($plugin_path)
    {
        $this->plugin_path = $plugin_path;
    }

    /**
     *
     * @return BreadCrumb
     */
    public function build(PFUser $user, Project $project)
    {
        $agile_breadcrumb = new BreadCrumb(
            new BreadCrumbLinkWithIcon(
                dgettext('tuleap-agiledashboard', 'Agile Dashboard'),
                $this->plugin_path . '/?' . http_build_query(['group_id' => $project->getID()]),
                'fa-table'
            )
        );

        if ($user->isAdmin($project->getID())) {
            $this->addAdministrationLink($project, $agile_breadcrumb);
        }

        return $agile_breadcrumb;
    }

    private function addAdministrationLink(Project $project, BreadCrumb $agile_breadcrumb)
    {
        $admin_url = AGILEDASHBOARD_BASE_URL . '/?' .
            http_build_query(
                [
                    'group_id' => $project->getID(),
                    'action'   => 'admin',
                ]
            );

        $sub_items = new BreadCrumbSubItems();
        $sub_items->addSection(
            new SubItemsUnlabelledSection(
                new BreadCrumbLinkCollection(
                    [
                        new BreadCrumbLink(
                            $GLOBALS['Language']->getText('global', 'Administration'),
                            $admin_url
                        )
                    ]
                )
            )
        );
        $agile_breadcrumb->setSubItems($sub_items);
    }
}
