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

use GitRepository;
use PFUser;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumb;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLink;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLinkCollection;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbSubItems;
use Tuleap\Layout\BreadCrumbDropdown\SubItemsUnlabelledSection;

class RepositoryCrumbBuilder
{
    /** @var \Git_GitRepositoryUrlManager */
    private $url_manager;
    /** @var \GitPermissionsManager */
    private $permissions_manager;
    /** @var string */
    private $plugin_path;

    /**
     * @param string                       $plugin_path
     */
    public function __construct(
        \Git_GitRepositoryUrlManager $url_manager,
        \GitPermissionsManager $permissions_manager,
        $plugin_path,
    ) {
        $this->url_manager         = $url_manager;
        $this->permissions_manager = $permissions_manager;
        $this->plugin_path         = $plugin_path;
    }

    public function build(PFUser $user, GitRepository $repository)
    {
        $repository_breadcrumb = new BreadCrumb(
            new BreadCrumbLink(
                $repository->getFullName(),
                $this->url_manager->getRepositoryBaseUrl($repository)
            )
        );

        if ($this->canUserAdministrateRepository($user, $repository)) {
            $this->addAdministrationLink($repository, $repository_breadcrumb);
        }

        return $repository_breadcrumb;
    }

    private function canUserAdministrateRepository(PFUser $user, GitRepository $repository)
    {
        return $this->permissions_manager->userIsGitAdmin($user, $repository->getProject()) ||
            $repository->belongsTo($user);
    }

    private function addAdministrationLink(GitRepository $repository, BreadCrumb $repository_breadcrumb)
    {
        $sub_items = new BreadCrumbSubItems();
        $sub_items->addSection(
            new SubItemsUnlabelledSection(
                new BreadCrumbLinkCollection(
                    [
                        new BreadCrumbLink(
                            $GLOBALS['Language']->getText('global', 'Settings'),
                            $this->getRepositoryAdminUrl($repository),
                        ),
                    ]
                )
            )
        );
        $repository_breadcrumb->setSubItems($sub_items);
    }

    private function getRepositoryAdminUrl(GitRepository $repository)
    {
        return $this->plugin_path . '/?' . http_build_query([
            'action' => 'repo_management',
            'group_id' => $repository->getProjectId(),
            'repo_id' => $repository->getId(),
        ]);
    }
}
