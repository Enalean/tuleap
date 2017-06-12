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

class hudson_Widget_ProjectJobsOverview extends HudsonOverviewWidget
{

    var $plugin;
    var $group_id;

    var $_not_monitored_jobs;
    var $_use_global_status;
    var $_all_status;
    var $_global_status;
    var $_global_status_icon;

    /**
     * Constructor
     *
     * @param Int              $group_id   The owner id
     * @param hudsonPlugin     $plugin     The plugin
     * @param HudsonJobFactory $factory    The HudsonJob factory
     *
     * @return void
     */
    public function __construct($group_id, hudsonPlugin $plugin, HudsonJobFactory $factory) {
        parent::__construct('plugin_hudson_project_jobsoverview', $factory);
        $this->setOwner($group_id, WidgetLayoutManager::OWNER_TYPE_GROUP);
        $this->plugin = $plugin;

        $request = HTTPRequest::instance();
        $this->group_id = $request->get('group_id');

        $this->_use_global_status = user_get_preference('plugin_hudson_use_global_status' . $this->group_id);
        if ($this->_use_global_status === false) {
            $this->_use_global_status = "false";
            user_set_preference('plugin_hudson_use_global_status' . $this->group_id, $this->_use_global_status);
        }

        if ($this->_use_global_status == "true") {
            $this->_all_status = array(
                'grey' => 0,
                'blue' => 0,
                'yellow' => 0,
                'red' => 0,
            );
        }

    }

    function computeGlobalStatus() {
        $jobs = $this->getJobsByGroup($this->group_id);
        foreach ($jobs as $job) {
            try {
                $this->_all_status[(string)$job->getColorNoAnime()] = $this->_all_status[(string)$job->getColorNoAnime()] + 1;
            } catch(Exception $e) {
                // Do not display error if some jobs fails
            }
        }
        if ($this->_all_status['grey'] > 0 || $this->_all_status['red'] > 0) {
            $this->_global_status = $GLOBALS['Language']->getText('plugin_hudson','global_status_red');
            $this->_global_status_icon = $this->plugin->getThemePath() . "/images/ic/" . "status_red.png";
        } elseif ($this->_all_status['yellow'] > 0) {
            $this->_global_status = $GLOBALS['Language']->getText('plugin_hudson','global_status_yellow');
            $this->_global_status_icon = $this->plugin->getThemePath() . "/images/ic/" . "status_yellow.png";
        } else {
            $this->_global_status = $GLOBALS['Language']->getText('plugin_hudson','global_status_blue');
            $this->_global_status_icon = $this->plugin->getThemePath() . "/images/ic/" . "status_blue.png";
        }
    }

    function hasPreferences() {
        return true;
    }

    public function getPreferencesForBurningParrot($widget_id)
    {
        $purifier = Codendi_HTMLPurifier::instance();

        return '
            <div class="tlp-form-element">
                <label class="tlp-label tlp-checkbox">
                <input type="checkbox" name="use_global_status" value="use_global" '.(($this->_use_global_status == "true")?'checked="checked"':'').'>
                '.$purifier->purify($GLOBALS['Language']->getText('plugin_hudson', 'use_global_status')).'
                </label>
            </div>';
    }

    function getPreferences() {
        $prefs  = '';
        // Use global status
        $prefs .= '<strong>'.$GLOBALS['Language']->getText('plugin_hudson', 'use_global_status').'</strong>';
        $prefs .= '<input type="checkbox" name="use_global_status" value="use_global" '.(($this->_use_global_status == "true")?'checked="checked"':'').'><br />';
        return $prefs;
    }
    function updatePreferences(&$request) {
        $request->valid(new Valid_String('cancel'));
        if (!$request->exist('cancel')) {
            $use_global_status = $request->get('use_global_status');
            $this->_use_global_status = ($use_global_status !== false)?"true":"false";
            user_set_preference('plugin_hudson_use_global_status' . $this->group_id, $this->_use_global_status);
        }
        return true;
    }

    function getTitle() {
        return parent::getTitle($GLOBALS['Language']->getText('plugin_hudson', 'project_jobs'));
    }

    function getDescription() {
        return $GLOBALS['Language']->getText('plugin_hudson', 'widget_description_jobsoverview');
    }

    function getContent() {
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
