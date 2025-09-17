<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

namespace Tuleap\Admin;

use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\InviteBuddy\InviteBuddyConfiguration;
use User_UserStatusManager;
use UserManager;
use PFUser;
use ProjectManager;
use Project;
use EventManager;

class AdminSidebarPresenterBuilder
{
    private UserManager $user_manager;
    private ProjectManager $project_manager;
    private EventDispatcherInterface $event_dispatcher;
    private InviteBuddyConfiguration $invitation_config;

    public function __construct()
    {
        $this->user_manager      = UserManager::instance();
        $this->project_manager   = ProjectManager::instance();
        $this->event_dispatcher  = EventManager::instance();
        $this->invitation_config = new InviteBuddyConfiguration($this->event_dispatcher);
    }

    public function build()
    {
        return new AdminSidebarPresenter(
            $this->allUsersCount(),
            $this->usersNeedApproval(),
            $this->pendingUsersCount(),
            $this->validatedUsersCount(),
            $this->allProjectsCount(),
            $this->pendingProjectsCount(),
            $this->getPlugins(),
            $this->areInvitationsEnabled(),
        );
    }

    /**
     * @return SiteAdministrationPluginOption[]
     */
    private function getPlugins(): array
    {
        $site_administration_add_option = new SiteAdministrationAddOption();
        $this->event_dispatcher->dispatch($site_administration_add_option);

        return $site_administration_add_option->getPluginOptions();
    }

    private function allUsersCount()
    {
        return $this->user_manager->countAllUsers();
    }

    private function usersNeedApproval()
    {
        return \ForgeConfig::getInt(User_UserStatusManager::CONFIG_USER_REGISTRATION_APPROVAL) === 1;
    }

    private function pendingUsersCount()
    {
        return $this->user_manager->countUsersByStatus(PFUser::STATUS_PENDING);
    }

    private function validatedUsersCount()
    {
        return $this->user_manager->countUsersByStatus([
            PFUser::STATUS_VALIDATED,
            PFUser::STATUS_VALIDATED_RESTRICTED,
        ]);
    }

    private function allProjectsCount()
    {
        $dar = $this->project_manager->getAllProjectsRows(0, 0);

        return $dar['numrows'];
    }

    private function pendingProjectsCount()
    {
        return $this->project_manager->countProjectsByStatus(Project::STATUS_PENDING);
    }

    private function areInvitationsEnabled(): bool
    {
        return $this->invitation_config->canSiteAdminConfigureTheFeature();
    }
}
