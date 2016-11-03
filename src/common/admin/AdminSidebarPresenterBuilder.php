<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

use UserManager;
use PFUser;
use ProjectManager;
use Project;
use TrackerV3;
use ForgeConfig;
use SVN_Apache_SvnrootConf;
use EventManager;
use Event;

class AdminSidebarPresenterBuilder
{
    /** @var UserManager */
    private $user_manager;

    /** @var ProjectManager */
    private $project_manager;

    /** @var EventManager */
    private $event_manager;

    public function __construct()
    {
        $this->user_manager    = UserManager::instance();
        $this->project_manager = ProjectManager::instance();
        $this->event_manager   = EventManager::instance();
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
            $this->areTroveCategoriesEnabled(),
            $this->getAdditionalTrackerEntries(),
            $this->areSvnTokensEnabled(),
            $this->getTuleapVersion()
        );
    }

    private function allUsersCount()
    {
        return $this->user_manager->countAllUsers();
    }

    private function usersNeedApproval()
    {
        return $GLOBALS['sys_user_approval'] == 1;
    }

    private function areTroveCategoriesEnabled()
    {
        return $GLOBALS['sys_use_trove'] != 0;
    }

    private function pendingUsersCount()
    {
        return $this->user_manager->countUsersByStatus(PFUser::STATUS_PENDING);
    }

    private function validatedUsersCount()
    {
        return $this->user_manager->countUsersByStatus(array(
            PFUser::STATUS_VALIDATED,
            PFUser::STATUS_VALIDATED_RESTRICTED
        ));
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

    private function getAdditionalTrackerEntries()
    {
        $additional_tracker_entries = array();

        $this->event_manager->processEvent(
            Event::SITE_ADMIN_CONFIGURATION_TRACKER,
            array(
                'additional_entries' => &$additional_tracker_entries
            )
        );

        return $additional_tracker_entries;
    }

    private function areSvnTokensEnabled()
    {
        return ForgeConfig::get(SVN_Apache_SvnrootConf::CONFIG_SVN_AUTH_KEY) !== SVN_Apache_SvnrootConf::CONFIG_SVN_AUTH_PERL;
    }

    private function getTuleapVersion()
    {
        return trim(file_get_contents($GLOBALS['codendi_dir'].'/VERSION'));
    }
}
