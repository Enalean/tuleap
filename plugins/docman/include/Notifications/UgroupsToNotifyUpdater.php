<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Docman\Notifications;

use ForgeAccess;
use Project;

class UgroupsToNotifyUpdater
{
    /**
     * @var UgroupsToNotifyDao
     */
    private $ugroups_dao;

    public function __construct(UgroupsToNotifyDao $ugroups_dao)
    {
        $this->ugroups_dao = $ugroups_dao;
    }

    public function updateProjectAccess($project_id, $old_access, $new_access)
    {
        if ($new_access == Project::ACCESS_PRIVATE) {
            $this->ugroups_dao->disableAnonymousRegisteredAuthenticated($project_id);
        }

        if ($new_access == Project::ACCESS_PUBLIC && $old_access == Project::ACCESS_PUBLIC_UNRESTRICTED) {
            $this->ugroups_dao->disableAuthenticated($project_id);
        }
    }

    public function updateSiteAccess($old_access)
    {
        if ($old_access == ForgeAccess::ANONYMOUS) {
            $this->ugroups_dao->updateAllAnonymousAccessToRegistered();
        }

        if ($old_access == ForgeAccess::RESTRICTED) {
            $this->ugroups_dao->updateAllAuthenticatedAccessToRegistered();
        }
    }
}
