<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Git\BreadCrumbDropdown;

use GitPermissionsManager;
use PFUser;
use Project;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumb;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLink;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLinkCollection;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbSubItems;
use Tuleap\Layout\BreadCrumbDropdown\SubItemsUnlabelledSection;

class GitCrumbBuilder
{
    /** @var GitPermissionsManager */
    private $permissions_manager;
    /** @var string */
    private $plugin_path;

    /**
     * @param string                $plugin_path
     */
    public function __construct(GitPermissionsManager $permissions_manager, $plugin_path)
    {
        $this->permissions_manager = $permissions_manager;
        $this->plugin_path         = $plugin_path;
    }

    /**
     *
     * @return BreadCrumb
     */
    public function build(PFUser $user, Project $project)
    {
        $git_breadcrumb = new BreadCrumb(
            new BreadCrumbLink(
                dgettext('tuleap-git', 'Git repositories'),
                $this->plugin_path . '/' . urlencode($project->getUnixNameLowerCase()) . '/',
            )
        );

        $links = new BreadCrumbLinkCollection();

        if ($this->permissions_manager->userIsGitAdmin($user, $project)) {
            $this->addAdministrationLink($project, $links);
        }
        $this->addForkRepositoriesLink($project, $links);

        $sub_items = new BreadCrumbSubItems();
        $sub_items->addSection(
            new SubItemsUnlabelledSection(
                $links
            )
        );
        $git_breadcrumb->setSubItems($sub_items);

        return $git_breadcrumb;
    }

    private function addAdministrationLink(Project $project, BreadCrumbLinkCollection $links)
    {
        $admin_url = $this->plugin_path . '/?' .
            http_build_query(
                [
                    'group_id' => $project->getID(),
                    'action'   => 'admin',
                ]
            );
        $links->add(
            new BreadCrumbLink(
                $GLOBALS['Language']->getText('global', 'Administration'),
                $admin_url,
            )
        );
    }

    private function addForkRepositoriesLink(Project $project, BreadCrumbLinkCollection $links)
    {
        $fork_repositories_url = $this->plugin_path . '/?' . http_build_query(
            [
                'group_id' => $project->getID(),
                'action'   => 'fork_repositories',
            ]
        );
        $links->add(
            new BreadCrumbLink(
                dgettext('tuleap-git', 'Fork repositories'),
                $fork_repositories_url,
            )
        );
    }
}
