<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\layout\HomePage;

use DateTime;
use ForgeConfig;
use UserManager;

class StatisticsCollectionBuilder
{
    /**
     * @var \ProjectManager
     */
    private $project_manager;
    /**
     * @var UserManager
     */
    private $user_manager;

    public function __construct(\ProjectManager $project_manager, UserManager $user_manager)
    {
        $this->project_manager = $project_manager;
        $this->user_manager = $user_manager;
    }

    public function build()
    {
        $collection = new StatisticsCollection();

        if (ForgeConfig::get('should_display_statistics')) {
            $date = new DateTime();
            $date->modify('-1 month');
            $timestamp = $date->getTimestamp();

            $collection->addStatistic(_('Projects'), $this->countAllProjects(), $this->countProjectRegisteredLastMonth($timestamp));
            $collection->addStatistic(_('Users'), $this->countAllUsers(), $this->countUsersRegisteredLastMonth($timestamp));
        }

        return $collection;
    }

    private function countAllUsers()
    {
        return $this->user_manager->countAllAliveUsers();
    }

    private function countUsersRegisteredLastMonth($timestamp)
    {
        return $this->user_manager->countAliveRegisteredUsersBefore($timestamp);
    }

    private function countAllProjects()
    {
        $dar = $this->project_manager->getAllProjectsRows(0, 0, [\Project::STATUS_ACTIVE]);

        return $dar['numrows'];
    }

    private function countProjectRegisteredLastMonth($timestamp)
    {
        return $this->project_manager->countRegisteredProjectsBefore($timestamp);
    }
}
