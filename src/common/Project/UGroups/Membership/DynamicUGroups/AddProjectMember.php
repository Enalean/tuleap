<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Project\UGroups\Membership\DynamicUGroups;

use Tuleap\Project\Admin\ProjectUGroup\CannotAddRestrictedUserToProjectNotAllowingRestricted;
use Tuleap\Project\UserPermissionsDao;

class AddProjectMember
{
    /**
     * @var UserPermissionsDao
     */
    private $dao;
    /**
     * @var \UserManager
     */
    private $user_manager;
    /**
     * @var \EventManager
     */
    private $event_manager;
    /**
     * @var \ProjectHistoryDao
     */
    private $history_dao;
    /**
     * @var \UGroupBinding
     */
    private $ugroup_binding;

    public function __construct(UserPermissionsDao $dao, \UserManager $user_manager, \EventManager $event_manager, \ProjectHistoryDao $history_dao, \UGroupBinding $ugroup_binding)
    {
        $this->dao = $dao;
        $this->user_manager = $user_manager;
        $this->event_manager = $event_manager;
        $this->history_dao = $history_dao;
        $this->ugroup_binding = $ugroup_binding;
    }

    public static function build(): self
    {
        return new self(
            new UserPermissionsDao(),
            \UserManager::instance(),
            \EventManager::instance(),
            new \ProjectHistoryDao(),
            new \UGroupBinding(
                new \UGroupUserDao(),
                new \UGroupManager()
            )
        );
    }

    public function addProjectMember(\PFUser $user, \Project $project): void
    {
        if (\ForgeConfig::areRestrictedUsersAllowed() && $user->isRestricted() && $project->getAccess() === \Project::ACCESS_PRIVATE_WO_RESTRICTED) {
            throw new CannotAddRestrictedUserToProjectNotAllowingRestricted($user, $project);
        }
        if ($this->dao->isUserPartOfProjectMembers($project->getID(), $user->getId())) {
            throw new AlreadyProjectMemberException(_('User is already member of the project'));
        }

        $this->dao->addUserAsProjectMember((int) $project->getID(), (int) $user->getId());

        if ($user->hasActiveUnixAccount() && ! $user->getUnixUid()) {
            $this->user_manager->assignNextUnixUid($user);
            $this->user_manager->updateDb($user);
        }

        $this->event_manager->processEvent(
            'project_admin_add_user',
            [
                'group_id'       => $project->getID(),
                'user_id'        => $user->getId(),
                'user_unix_name' => $user->getUserName(),
            ]
        );

        $this->history_dao->groupAddHistory('added_user', $user->getUserName(), $project->getID(), array($user->getUserName()));

        $this->ugroup_binding->reloadUgroupBindingInProject($project);
    }
}
