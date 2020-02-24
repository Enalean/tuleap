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

use Tuleap\News\Admin\AdminNewsDao;
use Tuleap\News\Admin\NewsRetriever;
use UserManager;
use PFUser;
use ProjectManager;
use Project;
use EventManager;

class AdminSidebarPresenterBuilder
{
    /** @var UserManager */
    private $user_manager;

    /** @var ProjectManager */
    private $project_manager;

    /** @var NewsRetriever */
    private $news_manager;

    public function __construct()
    {
        $this->user_manager    = UserManager::instance();
        $this->project_manager = ProjectManager::instance();
        $this->news_manager    = new NewsRetriever(new AdminNewsDao());
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
            $this->pendingNewsCount(),
            $this->getPlugins()
        );
    }

    private function getPlugins()
    {
        $plugins = array();

        EventManager::instance()->processEvent(
            'site_admin_option_hook',
            array(
                'plugins' => &$plugins
            )
        );

        usort($plugins, function ($plugin_a, $plugin_b) {
            return strnatcasecmp($plugin_a['label'], $plugin_b['label']);
        });

        return $plugins;
    }

    private function allUsersCount()
    {
        return $this->user_manager->countAllUsers();
    }

    private function usersNeedApproval()
    {
        return $GLOBALS['sys_user_approval'] == 1;
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

    private function pendingNewsCount()
    {
        return $this->news_manager->countPendingNews();
    }
}
