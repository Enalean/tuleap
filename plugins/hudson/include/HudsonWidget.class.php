<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean 2017-2018. All rights reserved
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

use Tuleap\Dashboard\Project\ProjectDashboardController;
use Tuleap\Dashboard\User\UserDashboardController;

abstract class HudsonWidget extends Widget
{

    /**
     * @var MinimalHudsonJobFactory
     */
    private $minimal_hudson_job_factory;

    public function __construct($widget_id, MinimalHudsonJobFactory $factory)
    {
        parent::__construct($widget_id);
        $this->minimal_hudson_job_factory = $factory;
    }

    public function getCategory()
    {
        return dgettext('tuleap-hudson', 'Continuous integration');
    }

    protected function getAvailableJobs()
    {
        if ($this->owner_type == UserDashboardController::LEGACY_DASHBOARD_TYPE) {
            $owner_id = UserManager::instance()->getCurrentUser()->getId();
        } else {
            $owner_id = $this->group_id;
        }

        return $this->minimal_hudson_job_factory->getAvailableJobs($this->owner_type, $owner_id);
    }

    protected function getJobsByGroup($group_id)
    {
        return $this->minimal_hudson_job_factory->getAvailableJobs(
            ProjectDashboardController::LEGACY_DASHBOARD_TYPE,
            $group_id
        );
    }

    public function isAjax()
    {
        return true;
    }
}
