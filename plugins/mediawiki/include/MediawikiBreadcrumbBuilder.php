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

namespace Tuleap\Mediawiki;

use Project;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumb;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbCollection;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLink;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLinkCollection;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbSubItems;
use Tuleap\Layout\BreadCrumbDropdown\SubItemsUnlabelledSection;
use Tuleap\Mediawiki\ForgeUserGroupPermission\MediawikiAdminAllProjects;
use User_ForgeUserGroupPermissionsManager;

class MediawikiBreadcrumbBuilder
{
    /**
     * @var User_ForgeUserGroupPermissionsManager
     */
    private $forge_user_group_permissions_manager;

    public function __construct(User_ForgeUserGroupPermissionsManager $forge_user_group_permissions_manager)
    {
        $this->forge_user_group_permissions_manager = $forge_user_group_permissions_manager;
    }

    public function getBreadcrumbs(Project $project, \PFUser $current_user): BreadCrumbCollection
    {
        $mediawiki_link       = new BreadCrumbLink(
            dgettext('tuleap-mediawiki', 'Mediawiki'),
            '/plugins/mediawiki/wiki/' . $project->getUnixName(),
        );

        $mediawiki_breadcrumb = new BreadCrumb($mediawiki_link);

        $breadcrumbs = new BreadCrumbCollection();
        $breadcrumbs->addBreadCrumb($mediawiki_breadcrumb);

        if ($this->isUserAdmin($project, $current_user)) {
            $admin_link = new BreadCrumbLink(
                _('Administration'),
                '/plugins/mediawiki/forge_admin.php?group_id=' . urlencode((string) $project->getId()),
            );
            $admin_link->setDataAttribute('test', 'mediawiki-administration-link');

            $sub_items = new BreadCrumbSubItems();
            $sub_items->addSection(new SubItemsUnlabelledSection(new BreadCrumbLinkCollection([$admin_link])));
            $mediawiki_breadcrumb->setSubItems($sub_items);
        }

        return $breadcrumbs;
    }

    public function isUserAdmin(Project $project, \PFUser $current_user): bool
    {
        $has_special_permission = $this->forge_user_group_permissions_manager->doesUserHavePermission(
            $current_user,
            new MediawikiAdminAllProjects()
        );

        return $current_user->isAdmin($project->getId()) || $has_special_permission;
    }
}
