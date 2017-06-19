<?php
/**
 * Copyright (c) Enalean, 2016 - 2017. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

require_once('HudsonOverviewWidget.class.php');
require_once('common/user/UserManager.class.php');
require_once('common/include/HTTPRequest.class.php');
require_once('PluginHudsonJobDao.class.php');
require_once('HudsonJob.class.php');

class hudson_Widget_ProjectJobsOverview extends HudsonOverviewWidget
{

    var $plugin;
    var $group_id;

    /**
     * @param Int              $group_id The owner id
     * @param hudsonPlugin     $plugin The plugin
     * @param HudsonJobFactory $factory The HudsonJob factory
     *
     * @return void
     */
    public function __construct($group_id, hudsonPlugin $plugin, HudsonJobFactory $factory)
    {
        parent::__construct('plugin_hudson_project_jobsoverview', $factory);
        $this->setOwner($group_id, ProjectDashboardController::LEGACY_DASHBOARD_TYPE);
        $this->plugin = $plugin;

        $request        = HTTPRequest::instance();
        $this->group_id = $request->get('group_id');

    }

    public function getTitle()
    {
        return $GLOBALS['Language']->getText('plugin_hudson', 'project_jobs');
    }

    public function getDescription() {
        return $GLOBALS['Language']->getText('plugin_hudson', 'widget_description_jobsoverview');
    }

    public function getContent() {
        $purifier = Codendi_HTMLPurifier::instance();
        $jobs     = $this->getJobsByGroup($this->group_id);
        $html     = '';
        if (sizeof($jobs) > 0) {
            $html .= '<table style="width:100%">';
            $cpt = 1;

            foreach ($jobs as $job_id => $job) {
                try {

                    $html .= '<tr class="'. util_get_alt_row_color($cpt) .'">';
                    $html .= ' <td>';
                    $html .= ' <img class="widget-jenkins-job-icon" src="'.$purifier->purify($job->getStatusIcon()).'" title="'.$purifier->purify($job->getStatus()).'" >';
                    $html .= ' </td>';
                    $html .= ' <td style="width:99%">';
                    $html .= '  <a class="widget-jenkins-job" href="/plugins/hudson/?action=view_job&group_id='.urlencode($this->group_id).'&job_id='.urlencode($job_id).'">'.$purifier->purify($job->getName()).'</a><br />';
                    $html .= ' </td>';
                    $html .= '</tr>';

                    $cpt++;

                } catch (Exception $e) {
                    // Do not display wrong jobs
                }
            }
            $html .= '</table>';
        } else {
            $html .= $GLOBALS['Language']->getText('plugin_hudson', 'widget_no_job_project', $purifier->purify($this->group_id));
        }
        return $html;
    }
}
