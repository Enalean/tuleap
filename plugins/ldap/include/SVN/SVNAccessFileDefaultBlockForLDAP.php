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

use ForgeConfig;
use LDAP_ProjectManager;
use Tuleap\SVNCore\SVNAccessFileDefaultBlockOverride;
use Tuleap\SVNCore\SVNUser;
use Tuleap\SVNCore\SVNUserGroup;

final class SVNAccessFileDefaultBlockForLDAP
{
    public function __construct(private readonly \LDAP_UserManager $LDAP_user_manager, private readonly LDAP_ProjectManager $LDAP_project_manager)
    {
    }

    public function handle(SVNAccessFileDefaultBlockOverride $svn_access_file_default_block): void
    {
        if (! $this->LDAP_project_manager->hasSVNLDAPAuth((int) $svn_access_file_default_block->project->getID())) {
            return;
        }

        foreach ($svn_access_file_default_block->user_groups as $user_group) {
            $svn_access_file_default_block->addSVNGroup(SVNUserGroup::fromUserGroupAndMembers($user_group, ...$this->getSVNGroupDef($user_group->getMembers())));
        }

        // This is the default behaviour inherited from LDAP_BackendSVN at 1182b1ef816e9aa2b9d33ffa91be270196ba749d
        // Kept as is to avoid change in permission scheme in projects at a time when [/] cannot be under project
        // control
        if (! $svn_access_file_default_block->project->isPublic() || ForgeConfig::areRestrictedUsersAllowed()) {
            $svn_access_file_default_block->disableWorldAccess();
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
