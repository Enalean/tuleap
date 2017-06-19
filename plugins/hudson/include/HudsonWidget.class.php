<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean 2017,. All rights reserved
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

require_once('common/widget/Widget.class.php');
require_once('PluginHudsonJobDao.class.php');
require_once('HudsonJobFactory.class.php');
require_once('common/widget/WidgetLayoutManager.class.php');

abstract class HudsonWidget extends Widget {
    
    /**
     * @var HudsonJobFactory
     */
    protected $hudsonJobFactory;
    
    public function __construct($widget_id, HudsonJobFactory $factory) {
        parent::__construct($widget_id);
        $this->hudsonJobFactory = $factory;
    }
    
    function getCategory() {
        return 'ci';
    }

    protected function getAvailableJobs()
    {
        if ($this->owner_type == UserDashboardController::LEGACY_DASHBOARD_TYPE) {
            $owner_id = UserManager::instance()->getCurrentUser()->getId();
        } else {
            $owner_id = $this->group_id;
        }

        return $this->getHudsonJobFactory()->getAvailableJobs($this->owner_type, $owner_id);
    }

    protected function getJobsByGroup($group_id)
    {
        return $this->getHudsonJobFactory()->getAvailableJobs(
            ProjectDashboardController::LEGACY_DASHBOARD_TYPE,
            $group_id
        );
    }

    protected function getJobsByUser($user_id)
    {
        return $this->getHudsonJobFactory()->getAvailableJobs(
            ProjectDashboardController::LEGACY_DASHBOARD_TYPE,
            $user_id
        );
    }

    protected function getHudsonJobFactory() {
        if (!$this->hudsonJobFactory) {
            $this->hudsonJobFactory = new HudsonJobFactory();
        }
        return $this->hudsonJobFactory;
    }
    
    public function setHudsonJobFactory(HudsonJobFactory $factory) {
        $this->hudsonJobFactory = $factory;
    }
    
    function isAjax() {
        return true;
    }

    function isInstallAllowed() {
        $jobs = $this->getAvailableJobs();
        return count($jobs) > 0;
    }

    function getInstallNotAllowedMessage() {
        $jobs = $this->getAvailableJobs();
        if (count($jobs) <= 0) {
            // no hudson jobs available
            if ($this->owner_type == ProjectDashboardController::LEGACY_DASHBOARD_TYPE) {
                return '<span class="feedback_warning">' . $GLOBALS['Language']->getText('plugin_hudson', 'widget_no_job_project', array($this->group_id)) . '</span>';
            } else {
                return '<span class="feedback_warning">' . $GLOBALS['Language']->getText('plugin_hudson', 'widget_no_job_my') . '</span>';
            }
        } else {
            return '';
        }
    }
}
