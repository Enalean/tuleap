<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

class hudson_Widget_MyMonitoredJobs extends HudsonOverviewWidget
{
    public hudsonPlugin $plugin;

    public mixed $_not_monitored_jobs;
    private MinimalHudsonJobFactory $factory;
    private HudsonJobBuilder $job_builder;

    public function __construct($user_id, hudsonPlugin $plugin, MinimalHudsonJobFactory $factory, HudsonJobBuilder $job_builder)
    {
        parent::__construct('plugin_hudson_my_jobs', $factory);
        $this->setOwner($user_id, UserDashboardController::LEGACY_DASHBOARD_TYPE);
        $this->plugin = $plugin;

        $this->_not_monitored_jobs = user_get_preference('plugin_hudson_my_not_monitored_jobs');
        if ($this->_not_monitored_jobs === false) {
            $this->_not_monitored_jobs = [];
        } else {
            $this->_not_monitored_jobs = explode(",", $this->_not_monitored_jobs);
        }
        $this->factory     = $factory;
        $this->job_builder = $job_builder;
    }

    public function getTitle()
    {
        return dgettext('tuleap-hudson', 'My Jenkins Jobs');
    }

    public function getDescription()
    {
        return dgettext('tuleap-hudson', 'Show an overview of all the jobs of all the projects you\'re member of. You can of course select the jobs you wish to display by selecting the preferences link of the widget.');
    }

    public function updatePreferences(Codendi_Request $request)
    {
        $request->valid(new Valid_String('cancel'));
        if (! $request->exist('cancel')) {
            $monitored_jobs = $request->get('myhudsonjobs');

            $user               = UserManager::instance()->getCurrentUser();
            $job_dao            = new PluginHudsonJobDao(CodendiDataAccess::instance());
            $dar                = $job_dao->searchByUserID($user->getId());
            $not_monitored_jobs = [];
            while ($dar->valid()) {
                $row = $dar->current();
                if (! in_array($row['job_id'], $monitored_jobs)) {
                    $not_monitored_jobs[] = $row['job_id'];
                }
                $dar->next();
            }

            $this->_not_monitored_jobs = $not_monitored_jobs;

            user_set_preference('plugin_hudson_my_not_monitored_jobs', implode(",", $this->_not_monitored_jobs));
        }
        return true;
    }

    public function hasPreferences($widget_id)
    {
        return true;
    }

    public function getPreferences(int $widget_id, int $content_id): string
    {
        $purifier = Codendi_HTMLPurifier::instance();

        $html = '<table class="tlp-table">
            <thead>
                <tr>
                    <th></th>
                    <th style="width:100%">
                        ' . $purifier->purify(dgettext('tuleap-hudson', 'Monitored jobs')) . '
                    </th>
                </tr>
            </thead>
            <tbody>';

        $user    = UserManager::instance()->getCurrentUser();
        $job_dao = new PluginHudsonJobDao(CodendiDataAccess::instance());
        foreach ($job_dao->searchByUserID($user->getId()) as $row) {
            $html .= '<tr><td>';
            $html .= '<input type="checkbox"
                             name="myhudsonjobs[]"
                             value="' . $purifier->purify($row['job_id']) . '"
                             ' . (in_array($row['job_id'], $this->_not_monitored_jobs) ? '' : 'checked="checked"') . '>';
            $html .= '</td><td>';
            $html .= $purifier->purify($row['name']);
            $html .= '</td></tr>';
        }
        $html .= '</tbody>
            </table>';

        return $html;
    }

    public function getContent()
    {
        $purifier = Codendi_HTMLPurifier::instance();
        $html     = '';

        $user             = UserManager::instance()->getCurrentUser();
        $job_dao          = new PluginHudsonJobDao(CodendiDataAccess::instance());
        $dar              = $job_dao->searchByUserID($user->getId());
        $nb_jobs_in_error = 0;
        if ($dar->rowCount() > 0) {
            $monitored_jobs = $this->_getMonitoredJobsByUser();
            if (sizeof($monitored_jobs) > 0) {
                $html .= '<table style="width:100%">';
                $cpt   = 1;

                $job_dao             = new PluginHudsonJobDao(CodendiDataAccess::instance());
                $minimal_hudson_jobs = [];
                $group_id_by_job_id  = [];

                foreach ($monitored_jobs as $monitored_job_id) {
                    $dar = $job_dao->searchByJobID($monitored_job_id);
                    if ($dar !== false && $dar->valid()) {
                        $row     = $dar->current();
                        $job_url = $row['job_url'];
                        $job_id  = $row['job_id'];
                        try {
                            $minimal_hudson_jobs[$job_id] = $this->factory->getMinimalHudsonJob($job_url, '');
                            $group_id_by_job_id[$job_id]  = $row['group_id'];
                        } catch (HudsonJobURLMalformedException $ex) {
                            $nb_jobs_in_error++;
                        }
                    }
                }

                $hudson_jobs_with_exception = $this->job_builder->getHudsonJobsWithException($minimal_hudson_jobs);
                foreach ($hudson_jobs_with_exception as $job_id => $hudson_job_with_exception) {
                    try {
                        $group_id = $group_id_by_job_id[$job_id];
                        $job      = $hudson_job_with_exception->getHudsonJob();

                        $html .= '<tr class="' . $purifier->purify(util_get_alt_row_color($cpt)) . '">';
                        $html .= ' <td>';
                        $html .= ' <img class="widget-jenkins-job-icon" src="' . $purifier->purify($job->getStatusIcon()) . '" title="' . $purifier->purify($job->getStatus()) . '" >';
                        $html .= ' </td>';
                        $html .= ' <td style="width:99%">';
                        $html .= '  <a class="widget-jenkins-job" href="/plugins/hudson/?action=view_job&group_id=' . urlencode($group_id) . '&job_id=' . urlencode($job_id) . '">' . $purifier->purify($job->getName()) . '</a><br />';
                        $html .= ' </td>';
                        $html .= '</tr>';

                        $cpt++;
                    } catch (Exception $e) {
                        $nb_jobs_in_error++;
                    }
                }
                $html .= '</table>';
            } else {
                $html .= dgettext('tuleap-hudson', 'You are not monitoring any job. Select preferences link to monitor a job.');
            }
        } else {
            $html .= dgettext('tuleap-hudson', 'No job found. Please add a job to any of your project before.');
        }
        if ($nb_jobs_in_error > 0) {
            $html_error_string  = '<div class="tlp-alert-warning">';
            $html_error_string .= dngettext(
                'tuleap-hudson',
                'An issue have been encountered while retrieving information, a job can not be displayed',
                'Issues have been encountered while retrieving information, some jobs can not be displayed',
                $nb_jobs_in_error
            );
            $html_error_string .= '</div>';
            $html               = $html_error_string . $html;
        }
        return $html;
    }

    public function _getMonitoredJobsByUser()
    {
        $user           = UserManager::instance()->getCurrentUser();
        $job_dao        = new PluginHudsonJobDao(CodendiDataAccess::instance());
        $dar            = $job_dao->searchByUserID($user->getId());
        $monitored_jobs = [];
        while ($dar->valid()) {
            $row = $dar->current();
            if (! in_array($row['job_id'], $this->_not_monitored_jobs)) {
                $monitored_jobs[] = $row['job_id'];
            }
            $dar->next();
        }
        return $monitored_jobs;
    }
}
