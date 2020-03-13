<?php
/**
 * Copyright (c) Enalean, 2016 - 2018. All Rights Reserved.
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

use Tuleap\Dashboard\User\UserDashboardController;
use Tuleap\Hudson\HudsonJobBuilder;

class hudson_Widget_JobLastBuilds extends HudsonJobWidget
{
    /**
     * @var HudsonJob
     */
    private $job;
    /**
     * @var HudsonJobBuilder
     */
    private $job_builder;

    /**
     * @param String           $owner_type The owner type
     * @param Int              $owner_id   The owner id
     * @param MinimalHudsonJobFactory $factory    The HudsonJob factory
     *
     * @return void
     */
    public function __construct($owner_type, $owner_id, MinimalHudsonJobFactory $factory, HudsonJobBuilder $job_builder)
    {
        $request = HTTPRequest::instance();
        if ($owner_type == UserDashboardController::LEGACY_DASHBOARD_TYPE) {
            $this->widget_id = 'plugin_hudson_my_joblastbuilds';
            $this->group_id  = $owner_id;
        } else {
            $this->widget_id = 'plugin_hudson_project_joblastbuilds';
            $this->group_id  = $request->get('group_id');
        }
        parent::__construct($this->widget_id, $factory);

        $this->setOwner($owner_id, $owner_type);
        $this->job_builder = $job_builder;
    }

    public function getTitle()
    {
        $title = '';
        if ($this->job) {
            $title .= $GLOBALS['Language']->getText('plugin_hudson', 'project_job_lastbuilds', array($this->job->getName()));
        } else {
            $title .= $GLOBALS['Language']->getText('plugin_hudson', 'project_job_lastbuilds');
        }
        return $title;
    }

    public function getDescription()
    {
        return $GLOBALS['Language']->getText('plugin_hudson', 'widget_description_lastbuilds');
    }

    public function loadContent($id)
    {
        $this->content_id = $id;
    }

    protected function initContent()
    {
        $job_id = $this->getJobIdFromWidgetConfiguration();
        if ($job_id) {
            $this->job_id = $job_id;

            $jobs = $this->getAvailableJobs();

            if (array_key_exists($this->job_id, $jobs)) {
                try {
                    $used_job = $jobs[$this->job_id];
                    $this->job = $this->job_builder->getHudsonJob($used_job);
                } catch (Exception $e) {
                    $this->job = null;
                }
            } else {
                $this->job = null;
            }
        }
    }

    public function getContent()
    {
        $this->initContent();

        $html = '';
        if ($this->job != null) {
            $job = $this->job;

            $html .= '<table width="100%">';
            $html .= ' <tr>';
            $html .= '  <td>';
            $html .= '   <ul>';
            if ($job->hasBuilds()) {
                $html .= ' <li>' . $GLOBALS['Language']->getText('plugin_hudson', 'last_build') . ' <a href="/plugins/hudson/?action=view_build&group_id=' . $this->group_id . '&job_id=' . $this->job_id . '&build_id=' . $job->getLastBuildNumber() . '"># ' . $job->getLastBuildNumber() . '</a></li>';
                $html .= ' <li>' . $GLOBALS['Language']->getText('plugin_hudson', 'last_build_success') . ' <a href="/plugins/hudson/?action=view_build&group_id=' . $this->group_id . '&job_id=' . $this->job_id . '&build_id=' . $job->getLastSuccessfulBuildNumber() . '"># ' . $job->getLastSuccessfulBuildNumber() . '</a></li>';
                $html .= ' <li>' . $GLOBALS['Language']->getText('plugin_hudson', 'last_build_failure') . ' <a href="/plugins/hudson/?action=view_build&group_id=' . $this->group_id . '&job_id=' . $this->job_id . '&build_id=' . $job->getLastFailedBuildNumber() . '"># ' . $job->getLastFailedBuildNumber() . '</a></li>';
            } else {
                $html .= ' <li>' . $GLOBALS['Language']->getText('plugin_hudson', 'widget_build_not_found') . '</li>';
            }
            $html .= '   </ul>';
            $html .= '  </td>';
            $html .= '  <td class="widget_lastbuilds_weather">';
            $html .= $GLOBALS['Language']->getText('plugin_hudson', 'weather_report') . '<img src="' . $job->getWeatherReportIcon() . '" class="widget-lastbuilds-weather-img" />';
            $html .= '  </td>';
            $html .= ' </tr>';
            $html .= '</table>';
        } else {
            $html .= $GLOBALS['Language']->getText('plugin_hudson', 'widget_job_not_found');
        }

        return $html;
    }
}
