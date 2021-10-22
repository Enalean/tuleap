<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\LDAP\REST;

use LDAP_ProjectGroupDao;
use LDAP_ProjectGroupManager;
use LDAP_UserGroupDao;
use LDAP_UserGroupManager;
use Tuleap\Project\REST\UserGroupAdditionalInformationEvent;

final class LDAPUserGroupRepresentationInformationAdder
{
    public function __construct(
        private LDAP_ProjectGroupManager $project_user_group_manager,
        private LDAP_ProjectGroupDao $project_user_group_dao,
        private LDAP_UserGroupManager $static_user_group_manager,
        private LDAP_UserGroupDao $static_user_group_dao,
    ) {
    }

    public function addAdditionalUserGroupInformation(UserGroupAdditionalInformationEvent $event): void
    {
        $ugroup = $event->project_ugroup;

        if (! $ugroup->isStatic() && $ugroup->getId() !== \ProjectUGroup::PROJECT_MEMBERS) {
            return;
        }

        $current_user = $event->current_user;
        if (! $current_user->isAdmin($ugroup->getProjectId())) {
            return;
        }

        if ($ugroup->getId() === \ProjectUGroup::PROJECT_MEMBERS) {
            $user_group_manager              = $this->project_user_group_manager;
            $dao                             = $this->project_user_group_dao;
            $id_to_use_to_search_information = $ugroup->getProjectId();
        } else {
            $user_group_manager              = $this->static_user_group_manager;
            $dao                             = $this->static_user_group_dao;
            $id_to_use_to_search_information = $ugroup->getId();
        }

        $row         = $dao->searchByGroupId($id_to_use_to_search_information);
        $ldap_result = $user_group_manager->getLdapGroupByGroupId($id_to_use_to_search_information);

        if ($row === false || $ldap_result === null) {
            $event->setAdditionalInformation('ldap', null);
            return;
        }

        if ($current_user->isSuperUser()) {
            $representation = new \Tuleap\LDAP\REST\LDAPUserGroupRepresentationSiteAdministrator(
                $ldap_result->getGroupDisplayName(),
                $row['synchro_policy'],
                $row['bind_option'],
                $ldap_result->getDn()
            );
        } else {
            $representation = new \Tuleap\LDAP\REST\LDAPUserGroupRepresentationProjectAdministrator(
                $ldap_result->getGroupDisplayName(),
                $row['synchro_policy'],
                $row['bind_option']
            );
        }

        $event->setAdditionalInformation('ldap', $representation);
    }
}
