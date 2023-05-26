<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\Project\UGroups\Membership\DynamicUGroups;

use EventManager;
use ForgeConfig;
use PFUser;
use Project;
use ProjectUGroup;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\Project\Admin\ProjectUGroup\ApproveProjectAdministratorRemoval;
use Tuleap\Project\Admin\ProjectUGroup\CannotAddRestrictedUserToProjectNotAllowingRestricted;
use Tuleap\Project\Admin\ProjectUGroup\CannotRemoveLastProjectAdministratorException;
use Tuleap\Project\Admin\ProjectUGroup\CannotRemoveUserMembershipToUserGroupException;
use Tuleap\Project\Admin\ProjectUGroup\UserBecomesForumAdmin;
use Tuleap\Project\Admin\ProjectUGroup\UserBecomesNewsAdministrator;
use Tuleap\Project\Admin\ProjectUGroup\UserBecomesNewsWriter;
use Tuleap\Project\Admin\ProjectUGroup\UserBecomesProjectAdmin;
use Tuleap\Project\Admin\ProjectUGroup\UserBecomesWikiAdmin;
use Tuleap\Project\Admin\ProjectUGroup\UserIsNoLongerForumAdmin;
use Tuleap\Project\Admin\ProjectUGroup\UserIsNoLongerNewsAdministrator;
use Tuleap\Project\Admin\ProjectUGroup\UserIsNoLongerNewsWriter;
use Tuleap\Project\Admin\ProjectUGroup\UserIsNoLongerProjectAdmin;
use Tuleap\Project\Admin\ProjectUGroup\UserIsNoLongerWikiAdmin;
use Tuleap\Project\UserPermissionsDao;

class DynamicUGroupMembersUpdater
{
    public function __construct(
        private readonly UserPermissionsDao $user_permissions_dao,
        private readonly DBTransactionExecutor $transaction_executor,
        private readonly ProjectMemberAdder $project_member_adder,
        private readonly EventManager $event_manager,
        private readonly \ProjectHistoryDao $project_history_dao,
    ) {
    }

    /**
     * @throws CannotAddRestrictedUserToProjectNotAllowingRestricted
     */
    public function addUser(Project $project, ProjectUGroup $ugroup, PFUser $user, PFUser $project_admin): void
    {
        if (
            $project->getAccess() === Project::ACCESS_PRIVATE_WO_RESTRICTED && ForgeConfig::areRestrictedUsersAllowed() &&
            $user->isRestricted()
        ) {
            throw new CannotAddRestrictedUserToProjectNotAllowingRestricted($user, $project);
        }

        switch ($ugroup->getId()) {
            case ProjectUGroup::PROJECT_ADMIN:
                $this->addProjectAdministrator($project, $user, $project_admin);
                break;
            case ProjectUGroup::WIKI_ADMIN:
                $this->addWikiAdministrator($project, $user, $project_admin);
                break;
            case ProjectUGroup::FORUM_ADMIN:
                $this->addForumAdministrator($project, $user, $project_admin);
                break;
            case ProjectUGroup::NEWS_WRITER:
                $this->addNewsEditor($project, $user, $project_admin);
                break;
            case ProjectUGroup::NEWS_ADMIN:
                $this->addNewsAdministrator($project, $user, $project_admin);
                break;
        }
    }

    /**
     * @throws CannotRemoveUserMembershipToUserGroupException
     */
    public function removeUser(Project $project, ProjectUGroup $ugroup, PFUser $user, PFUser $project_admin): void
    {
        switch ($ugroup->getId()) {
            case ProjectUGroup::PROJECT_ADMIN:
                $this->removeProjectAdministrator($project, $user, $project_admin);
                break;
            case ProjectUGroup::WIKI_ADMIN:
                $this->removeWikiAdministrator($project, $user);
                break;
            case ProjectUGroup::FORUM_ADMIN:
                $this->removeForumAdministrator($project, $user);
                break;
            case ProjectUGroup::NEWS_WRITER:
                $this->removeNewsEditor($project, $user);
                break;
            case ProjectUGroup::NEWS_ADMIN:
                $this->removeNewsAdministrator($project, $user);
                break;
        }
    }

    private function addProjectAdministrator(Project $project, PFUser $user, PFUser $project_admin): void
    {
        $this->ensureUserIsProjectMember($project, $user, $project_admin);

        $this->user_permissions_dao->addUserAsProjectAdmin($project->getID(), $user->getId());
        $this->project_history_dao->addHistory(
            $project,
            $project_admin,
            new \DateTimeImmutable(),
            ProjectAdminHistoryEntry::Add->value,
            $user->getUserName() . " (" . $user->getId() . ")",
        );
        $this->event_manager->dispatch(new UserBecomesProjectAdmin($project, $user));
    }

    /**
     * @throws CannotRemoveUserMembershipToUserGroupException
     */
    private function removeProjectAdministrator(Project $project, PFUser $user, PFUser $project_admin): void
    {
        $event = $this->transaction_executor->execute(
            function () use ($project, $user, $project_admin): UserIsNoLongerProjectAdmin {
                if (! $this->user_permissions_dao->isThereOtherProjectAdmin($project->getID(), $user->getId())) {
                    throw new CannotRemoveLastProjectAdministratorException($user, $project);
                }
                $this->event_manager->processEvent(new ApproveProjectAdministratorRemoval($project, $user));
                $this->user_permissions_dao->removeUserFromProjectAdmin($project->getID(), $user->getId());
                $this->project_history_dao->addHistory(
                    $project,
                    $project_admin,
                    new \DateTimeImmutable(),
                    ProjectAdminHistoryEntry::Remove->value,
                    $user->getUserName() . " (" . $user->getId() . ")",
                );
                return new UserIsNoLongerProjectAdmin($project, $user);
            }
        );
        $this->event_manager->dispatch($event);
    }

    private function addWikiAdministrator(Project $project, PFUser $user, PFUser $project_admin): void
    {
        $this->ensureUserIsProjectMember($project, $user, $project_admin);

        $this->user_permissions_dao->addUserAsWikiAdmin($project->getID(), $user->getId());
        $this->event_manager->processEvent(new UserBecomesWikiAdmin($project, $user));
    }

    private function removeWikiAdministrator(Project $project, PFUser $user): void
    {
        $this->user_permissions_dao->removeUserFromWikiAdmin($project->getID(), $user->getId());
        $this->event_manager->processEvent(new UserIsNoLongerWikiAdmin($project, $user));
    }

    private function ensureUserIsProjectMember(Project $project, PFUser $user, PFUser $project_admin): void
    {
        if (! $this->user_permissions_dao->isUserPartOfProjectMembers($project->getID(), $user->getId())) {
            $this->project_member_adder->addProjectMemberWithFeedback($user, $project, $project_admin);
        }
    }

    private function addForumAdministrator(Project $project, PFUser $user, PFUser $project_admin): void
    {
        $this->ensureUserIsProjectMember($project, $user, $project_admin);
        $this->user_permissions_dao->addUserAsForumAdmin($project->getID(), $user->getId());
        $this->event_manager->processEvent(new UserBecomesForumAdmin($project, $user));
    }

    private function removeForumAdministrator(Project $project, PFUser $user): void
    {
        $this->user_permissions_dao->removeUserFromForumAdmin($project->getID(), $user->getId());
        $this->event_manager->processEvent(new UserIsNoLongerForumAdmin($project, $user));
    }

    private function addNewsEditor(Project $project, PFUser $user, PFUser $project_admin): void
    {
        $this->ensureUserIsProjectMember($project, $user, $project_admin);
        $this->user_permissions_dao->addUserAsNewsEditor($project->getID(), $user->getId());
        $this->event_manager->processEvent(new UserBecomesNewsWriter($project, $user));
    }

    private function removeNewsEditor(Project $project, PFUser $user): void
    {
        $this->user_permissions_dao->removeUserFromNewsEditor($project->getID(), $user->getId());
        $this->event_manager->processEvent(new UserIsNoLongerNewsWriter($project, $user));
    }

    private function addNewsAdministrator(Project $project, PFUser $user, PFUser $project_admin): void
    {
        $this->ensureUserIsProjectMember($project, $user, $project_admin);
        $this->user_permissions_dao->addUserAsNewsAdmin($project->getID(), $user->getId());
        $this->event_manager->processEvent(new UserBecomesNewsAdministrator($project, $user));
    }

    private function removeNewsAdministrator(Project $project, PFUser $user): void
    {
        $this->user_permissions_dao->removeUserFromNewsAdmin($project->getID(), $user->getId());
        $this->event_manager->processEvent(new UserIsNoLongerNewsAdministrator($project, $user));
    }
}
