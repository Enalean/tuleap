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

use Tuleap\Project\Admin\MembershipDelegationDao;
use Tuleap\Project\Admin\ProjectMembers\EnsureUserCanManageProjectMembers;
use Tuleap\Project\Admin\ProjectMembers\UserCanManageProjectMembersChecker;
use Tuleap\Project\Admin\ProjectMembers\UserIsNotAllowedToManageProjectMembersException;
use Tuleap\Project\Admin\ProjectUGroup\CannotAddRestrictedUserToProjectNotAllowingRestricted;
use Tuleap\Project\UserPermissionsDao;

class AddProjectMember
{
    public function __construct(
        private UserPermissionsDao $dao,
        private \EventManager $event_manager,
        private \ProjectHistoryDao $history_dao,
        private \UGroupBinding $ugroup_binding,
        private EnsureUserCanManageProjectMembers $members_members_checker,
    ) {
    }

    public static function build(): self
    {
        return new self(
            new UserPermissionsDao(),
            \EventManager::instance(),
            new \ProjectHistoryDao(),
            new \UGroupBinding(
                new \UGroupUserDao(),
                new \UGroupManager()
            ),
            new UserCanManageProjectMembersChecker(new MembershipDelegationDao()),
        );
    }

    /**
     * @throws UserIsNotAllowedToManageProjectMembersException
     */
    public function addProjectMember(\PFUser $user, \Project $project, \PFUser $project_admin): void
    {
        if (\ForgeConfig::areRestrictedUsersAllowed() && $user->isRestricted() && $project->getAccess() === \Project::ACCESS_PRIVATE_WO_RESTRICTED) {
            throw new CannotAddRestrictedUserToProjectNotAllowingRestricted($user, $project);
        }
        if ($this->dao->isUserPartOfProjectMembers($project->getID(), $user->getId())) {
            throw new AlreadyProjectMemberException(_('User is already member of the project'));
        }
        $this->members_members_checker->checkUserCanManageProjectMembers($project_admin, $project);

        $this->dao->addUserAsProjectMember((int) $project->getID(), (int) $user->getId());

        $this->event_manager->processEvent(
            'project_admin_add_user',
            [
                'group_id'       => $project->getID(),
                'user_id'        => $user->getId(),
                'user_unix_name' => $user->getUserName(),
            ]
        );

        $this->history_dao->addHistory(
            $project,
            $project_admin,
            new \DateTimeImmutable('now'),
            'added_user',
            $user->getUserName(),
            [$user->getUserName()],
        );

        $this->ugroup_binding->reloadUgroupBindingInProject($project);
    }
}
