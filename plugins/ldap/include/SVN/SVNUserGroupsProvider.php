<?php
/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\LDAP\SVN;

use LDAP_ProjectManager;
use Tuleap\SVNCore\GetSVNUserGroups;
use Tuleap\SVNCore\SVNUser;
use Tuleap\SVNCore\SVNUserGroup;

final class SVNUserGroupsProvider
{
    public function __construct(private readonly \LDAP_UserManager $LDAP_user_manager, private readonly LDAP_ProjectManager $LDAP_project_manager)
    {
    }

    public function handle(GetSVNUserGroups $svn_user_groups): void
    {
        if (! $this->LDAP_project_manager->hasSVNLDAPAuth((int) $svn_user_groups->project->getID())) {
            return;
        }

        foreach ($svn_user_groups->user_groups as $user_group) {
            $svn_user_groups->addSVNGroup(SVNUserGroup::fromUserGroupAndMembers($user_group, ...$this->getSVNGroupDef($user_group->getMembers())));
        }
    }

    /**
     * @psalm-param \PFUser[] $users
     */
    private function getSVNGroupDef(array $users): array
    {
        $users_map = [];
        $user_ids  = [];
        foreach ($users as $user) {
            $user_ids[]                      = (int) $user->getId();
            $users_map[(int) $user->getId()] = $user;
        }
        if (count($user_ids) === 0) {
            return [];
        }

        $dar     = $this->LDAP_user_manager->getLdapLoginFromUserIds($user_ids);
        $members = [];
        foreach ($dar as $row) {
            if (isset($users_map[$row['user_id']])) {
                $members[] = new SVNUser($users_map[$row['user_id']], strtolower($row['ldap_uid']));
            }
        }

        return $members;
    }
}
