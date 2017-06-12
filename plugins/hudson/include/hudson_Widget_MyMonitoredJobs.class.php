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


require_once('HudsonOverviewWidget.class.php');
require_once('common/user/UserManager.class.php');
require_once('common/include/HTTPRequest.class.php');
require_once('PluginHudsonJobDao.class.php');
require_once('HudsonJob.class.php');

class hudson_Widget_MyMonitoredJobs extends HudsonOverviewWidget {

    var $plugin;

    var $_not_monitored_jobs;

    /**
     * Constructor
     *
     * @param Int              $user_id    The owner id
     * @param hudsonPlugin     $plugin     The plugin
     * @param HudsonJobFactory $factory    The HudsonJob factory
     *
     * @return void
     */
    function __construct($user_id, hudsonPlugin $plugin, HudsonJobFactory $factory) {
        parent::__construct('plugin_hudson_my_jobs', $factory);
        $this->setOwner($user_id, WidgetLayoutManager::OWNER_TYPE_USER);
        $this->plugin = $plugin;

        $this->_not_monitored_jobs = user_get_preference('plugin_hudson_my_not_monitored_jobs');
        if ($this->_not_monitored_jobs === false) {
            $this->_not_monitored_jobs = array();
        } else {
            $this->_not_monitored_jobs = explode(",", $this->_not_monitored_jobs);
        }
    }

    function isInstallAllowed() {
        $user    = UserManager::instance()->getCurrentUser();
        $job_dao = new PluginHudsonJobDao();
        $dar     = $job_dao->searchByUserID($user->getId());
        return ($dar->rowCount() > 0);
    }

    function getInstallNotAllowedMessage() {
    	$user = UserManager::instance()->getCurrentUser();
        $job_dao = new PluginHudsonJobDao(CodendiDataAccess::instance());
        $dar = $job_dao->searchByUserID($user->getId());
        if ($dar->rowCount() <= 0) {
            // no hudson jobs available
            return '<span class="feedback_warning">' . $GLOBALS['Language']->getText('plugin_hudson', 'widget_no_job_my') . '</span>';
        } else {
        	return '';
        }
    }

    function getTitle()
    {
        return $GLOBALS['Language']->getText('plugin_hudson', 'my_jobs');
    }

    function getDescription() {
        return $GLOBALS['Language']->getText('plugin_hudson', 'widget_description_myjobs');
    }

    function updatePreferences(&$request) {
        $request->valid(new Valid_String('cancel'));
        if (!$request->exist('cancel')) {
            $monitored_jobs = $request->get('myhudsonjobs');

            $user = UserManager::instance()->getCurrentUser();
            $job_dao = new PluginHudsonJobDao(CodendiDataAccess::instance());
            $dar = $job_dao->searchByUserID($user->getId());
            $not_monitored_jobs = array();
            while ($dar->valid()) {
                $row = $dar->current();
                if ( ! in_array($row['job_id'], $monitored_jobs)) {
                    $not_monitored_jobs[] = $row['job_id'];
                }
                $dar->next();
            }

            $this->_not_monitored_jobs = $not_monitored_jobs;

            user_set_preference('plugin_hudson_my_not_monitored_jobs', implode(",", $this->_not_monitored_jobs));
        }
        return true;
    }
    function hasPreferences() {
        return true;
    }

    public function getPreferencesForBurningParrot($widget_id)
    {
        $purifier = Codendi_HTMLPurifier::instance();

        $html = '<table class="tlp-table">
            <thead>
                <tr>
                    <th></th>
                    <th style="width:100%">
                        '. $purifier->purify($GLOBALS['Language']->getText('plugin_hudson', 'monitored_jobs')) .'
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
                             value="'. $purifier->purify($row['job_id']) .'"
                             '.(in_array($row['job_id'], $this->_not_monitored_jobs)?'':'checked="checked"').'>';
            $html .= '</td><td>';
            $html .= $purifier->purify($row['name']);
            $html .= '</td></tr>';
        }
        $html .= '</tbody>
            </table>';

        return $html;
    }

    /**
     * Returns user preferences for given widget
     *
     * Do not attempt to load remote jenkins job otherwise, user might be stuck if there are a lot of "not responding jobs".
     *
     * @see src/common/widget/Widget::getPreferences()
     */
    function getPreferences() {
        $purifier = Codendi_HTMLPurifier::instance();
        $prefs    = '';
        // Monitored jobs
        $prefs .= '<strong>'.$GLOBALS['Language']->getText('plugin_hudson', 'monitored_jobs').'</strong><br />';
        $user = UserManager::instance()->getCurrentUser();
        $job_dao = new PluginHudsonJobDao(CodendiDataAccess::instance());
        $dar = $job_dao->searchByUserID($user->getId());
        foreach ($dar as $row) {
            $prefs .= '<input type="checkbox" name="myhudsonjobs[]" value="'.urlencode($row['job_id']).'" '.(in_array($row['job_id'], $this->_not_monitored_jobs)?'':'checked="checked"').'> '.$purifier->purify($row['name']).'<br />';
        }
        return $prefs;
    }

    function getContent() {
        $purifier = Codendi_HTMLPurifier::instance();
        $html     = '';

    	$user = UserManager::instance()->getCurrentUser();
        $job_dao = new PluginHudsonJobDao(CodendiDataAccess::instance());
        $dar = $job_dao->searchByUserID($user->getId());
    	if ($dar->rowCount() > 0) {
	        $monitored_jobs = $this->_getMonitoredJobsByUser();
	        if (sizeof($monitored_jobs) > 0) {
	            $html .= '<table style="width:100%">';
	            $cpt = 1;

	            foreach ($monitored_jobs as $monitored_job) {
	                try {

	                    $job_dao = new PluginHudsonJobDao(CodendiDataAccess::instance());
	                    $dar = $job_dao->searchByJobID($monitored_job);
	                    if ($dar->valid()) {
                            $http_client = new Http_Client();
                            $row         = $dar->current();
                            $job_url     = $row['job_url'];
                            $job_id      = $row['job_id'];
                            $group_id    = $row['group_id'];
                            $job         = new HudsonJob($job_url, $http_client);

	                        $html .= '<tr class="'. $purifier->purify(util_get_alt_row_color($cpt)) .'">';
	                        $html .= ' <td>';
	                        $html .= ' <img class="widget-jenkins-job-icon" src="'.$purifier->purify($job->getStatusIcon()).'" title="'.$purifier->purify($job->getStatus()).'" >';
	                        $html .= ' </td>';
	                        $html .= ' <td style="width:99%">';
	                        $html .= '  <a class="widget-jenkins-job" href="/plugins/hudson/?action=view_job&group_id='.urlencode($group_id).'&job_id='.urlencode($job_id).'">'.$purifier->purify($job->getName()).'</a><br />';
	                        $html .= ' </td>';
	                        $html .= '</tr>';

	                        $cpt++;
	                    }
	                } catch (Exception $e) {
	                    // Do not display wrong jobs
	                }
	            }
	            $html .= '</table>';
	        } else {
	        	$html .= $GLOBALS['Language']->getText('plugin_hudson', 'widget_no_monitoredjob_my');
	        }
        } else {
        	$html .= $GLOBALS['Language']->getText('plugin_hudson', 'widget_no_job_my');
        }
        return $html;
    }

    function _getMonitoredJobsByUser() {
        $user = UserManager::instance()->getCurrentUser();
        $job_dao = new PluginHudsonJobDao(CodendiDataAccess::instance());
        $dar = $job_dao->searchByUserID($user->getId());
        $monitored_jobs = array();
        while ($dar->valid()) {
            $row = $dar->current();
            if ( ! in_array($row['job_id'], $this->_not_monitored_jobs)) {
                $monitored_jobs[] = $row['job_id'];
            }
            $dar->next();
        }
        return $monitored_jobs;
    }
}

?>
