<?php
/**
 * Copyright (c) Enalean, 2017-2019. All Rights Reserved.
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

namespace Tuleap;

use EventManager;
use PFUser;
use Tuleap\Request\CurrentPage;
use User_ForgeUserGroupPermission_ProjectApproval;
use User_ForgeUserGroupPermissionsManager;

class BurningParrotCompatiblePageDetector
{
    /**
     * @var CurrentPage
     */
    private $current_page;
    /**
     * @var User_ForgeUserGroupPermissionsManager
     */
    private $forge_user_group_permissions_manager;

    public function __construct(
        CurrentPage $current_page,
        User_ForgeUserGroupPermissionsManager $forge_user_group_permissions_manager
    ) {
        $this->current_page                         = $current_page;
        $this->forge_user_group_permissions_manager = $forge_user_group_permissions_manager;
    }

    public function isInCompatiblePage(PFUser $current_user)
    {
        if (IS_SCRIPT) {
            return false;
        }

        return $this->isInCoreServicesSiteAdmin($current_user)
            || $this->current_page->isDashboard()
            || $this->isManagingLabels()
            || $this->isInProjectAdmin()
            || $this->isInContact()
            || $this->isInHelp()
            || $this->isInBurningParrotCompatiblePage()
            || $this->isSoftwareMap();
    }

    private function isManagingLabels()
    {
        return strpos($_SERVER['REQUEST_URI'], '/project/admin/labels.php') === 0;
    }

    private function isInProjectAdmin()
    {
        parse_str($_SERVER['QUERY_STRING'], $query_string);

        return strpos($_SERVER['REQUEST_URI'], '/project/admin/editgroupinfo.php') === 0
            || strpos($_SERVER['REQUEST_URI'], '/project/admin/ugroup.php') === 0
            || strpos($_SERVER['REQUEST_URI'], '/project/admin/editugroup.php') === 0
            || strpos($_SERVER['REQUEST_URI'], '/project/admin/permission_per_group.php') === 0;
    }

    private function isInCoreServicesSiteAdmin(PFUser $current_user)
    {
        $uri = $_SERVER['REQUEST_URI'];

        $is_in_site_admin = (
                    strpos($uri, '/admin/') === 0 ||
                    strpos($uri, '/tracker/admin/restore.php') === 0
                ) &&
                strpos($uri, '/admin/register_admin.php') !== 0;

        if ($is_in_site_admin && $current_user->isSuperUser()) {
            return true;
        }

        return $this->isInCoreServicesSiteAdminWithPermissionDelegation($current_user);
    }

    private function isInCoreServicesSiteAdminWithPermissionDelegation(PFUser $current_user)
    {
        $uri                       = $_SERVER['REQUEST_URI'];
        $is_in_project_approbation = (strpos($uri, '/admin/approve-pending.php') === 0);

        return $is_in_project_approbation &&
            $this->forge_user_group_permissions_manager->doesUserHavePermission(
                $current_user,
                new User_ForgeUserGroupPermission_ProjectApproval()
            );
    }

    private function isInContact()
    {
        return strpos($_SERVER['REQUEST_URI'], '/contact.php') === 0;
    }

    private function isInHelp()
    {
        return strpos($_SERVER['REQUEST_URI'], '/help/') === 0;
    }

    private function isSoftwareMap()
    {
        return strpos($_SERVER['REQUEST_URI'], '/softwaremap/') === 0;
    }

    private function isInBurningParrotCompatiblePage()
    {
        $burning_parrot_compatible_event = new BurningParrotCompatiblePageEvent();
        EventManager::instance()->processEvent($burning_parrot_compatible_event);

        return $burning_parrot_compatible_event->isInBurningParrotCompatiblePage();
    }
}
