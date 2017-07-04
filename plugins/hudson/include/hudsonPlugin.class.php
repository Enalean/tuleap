<?php
/**
 * Copyright (c) Enalean, 2011 - 2017. All Rights Reserved.
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
use Tuleap\Dashboard\User\UserDashboardController;

require_once 'autoload.php';
require_once 'constants.php';

class hudsonPlugin extends Plugin
{
    public function __construct($id)
    {
        parent::__construct($id);
        $this->addHook('javascript_file', 'jsFile', false);
        $this->addHook('cssfile', 'cssFile', false);
        $this->addHook(Event::SERVICE_ICON);
        $this->addHook(Event::SERVICES_ALLOWED_FOR_PROJECT);

        $this->addHook('project_is_deleted', 'projectIsDeleted', false);

        $this->addHook('widget_instance', 'widget_instance', false);
        $this->addHook('widgets', 'widgets', false);

        $this->addHook('get_available_reference_natures', 'getAvailableReferenceNatures', false);
        $this->addHook('ajax_reference_tooltip', 'ajax_reference_tooltip', false);
        $this->addHook(Event::AJAX_REFERENCE_SPARKLINE, 'ajax_reference_sparkline', false);
        $this->addHook('statistics_collector',          'statistics_collector',       false);

        $this->addHook(Event::BURNING_PARROT_GET_STYLESHEETS);
    }

    public function burning_parrot_get_stylesheets($params)
    {
        if ($this->canIncludeStylsheets()) {
            $variant = $params['variant'];
            $params['stylesheets'][] = $this->getThemePath() .'/css/style-'. $variant->getName() .'.css';
        }
    }

    private function canIncludeStylsheets()
    {
        return strpos($_SERVER['REQUEST_URI'], HUDSON_BASE_URL . '/') === 0 ||
            strpos($_SERVER['REQUEST_URI'], '/my/') === 0 ||
            strpos($_SERVER['REQUEST_URI'], '/projects/') === 0;
    }

    public function getPluginInfo() {
        if (!is_a($this->pluginInfo, 'hudsonPluginInfo')) {
            require_once('hudsonPluginInfo.class.php');
            $this->pluginInfo = new hudsonPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function getServiceShortname() {
        return 'hudson';
    }

    public function service_icon($params) {
        $params['list_of_icon_unicodes'][$this->getServiceShortname()] = '\e811';
    }

    function cssFile($params) {
        // Only show the stylesheet if we're actually in the hudson pages.
        // This stops styles inadvertently clashing with the main site.
        if ($this->canIncludeStylsheets() ||
            strpos($_SERVER['REQUEST_URI'], '/widgets/') === 0
        ) {
            echo '<link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/style.css" />';
        }
    }

    function jsFile($params) {
        // Only include the js files if we're actually in the IM pages.
        // This stops styles inadvertently clashing with the main site.
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            echo '<script type="text/javascript" src="/scripts/scriptaculous/scriptaculous.js"></script>'."\n";
            echo '<script type="text/javascript" src="js/hudson_tab.js"></script>'."\n";
            echo '<script type="text/javascript" src="js/form.js"></script>'."\n";
        }
    }

    /**
     * When a project is deleted,
     * we delete all the hudson jobs of this project
     *
     * @param mixed $params ($param['group_id'] the ID of the deleted project)
     */
    function projectIsDeleted($params) {
        $group_id = $params['group_id'];
        $job_dao = new PluginHudsonJobDao(CodendiDataAccess::instance());
        $dar = $job_dao->deleteHudsonJobsByGroupID($group_id);
    }


    protected $hudsonJobFactory = null;

    protected function getHudsonJobFactory() {
        if (!$this->hudsonJobFactory) {
            $this->hudsonJobFactory = new HudsonJobFactory();
        }
        return $this->hudsonJobFactory;
    }

    function widget_instance($params) {
        $request = HTTPRequest::instance();

        $user = UserManager::instance()->getCurrentUser();
        $hf   = $this->getHudsonJobFactory();
        // MY
        if ($params['widget'] == 'plugin_hudson_my_jobs') {
            require_once('hudson_Widget_MyMonitoredJobs.class.php');
            $params['instance'] = new hudson_Widget_MyMonitoredJobs($user->getId(), $this, $hf);
        }
        if ($params['widget'] == 'plugin_hudson_my_joblastbuilds') {
            require_once('hudson_Widget_JobLastBuilds.class.php');
            $params['instance'] = new hudson_Widget_JobLastBuilds(UserDashboardController::LEGACY_DASHBOARD_TYPE, $user->getId(), $hf);
        }
        if ($params['widget'] == 'plugin_hudson_my_jobtestresults') {
            require_once('hudson_Widget_JobTestResults.class.php');
            $params['instance'] = new hudson_Widget_JobTestResults(UserDashboardController::LEGACY_DASHBOARD_TYPE, $user->getId(), $hf);
        }
        if ($params['widget'] == 'plugin_hudson_my_jobtesttrend') {
            require_once('hudson_Widget_JobTestTrend.class.php');
            $params['instance'] = new hudson_Widget_JobTestTrend(UserDashboardController::LEGACY_DASHBOARD_TYPE, $user->getId(), $hf);
        }
        if ($params['widget'] == 'plugin_hudson_my_jobbuildhistory') {
            require_once('hudson_Widget_JobBuildHistory.class.php');
            $params['instance'] = new hudson_Widget_JobBuildHistory(UserDashboardController::LEGACY_DASHBOARD_TYPE, $user->getId(), $hf);
        }
        if ($params['widget'] == 'plugin_hudson_my_joblastartifacts') {
            require_once('hudson_Widget_JobLastArtifacts.class.php');
            $params['instance'] = new hudson_Widget_JobLastArtifacts(UserDashboardController::LEGACY_DASHBOARD_TYPE, $user->getId(), $hf);
        }

        // PROJECT
        if ($params['widget'] == 'plugin_hudson_project_jobsoverview') {
            require_once('hudson_Widget_ProjectJobsOverview.class.php');
            $params['instance'] = new hudson_Widget_ProjectJobsOverview($request->get('group_id'), $this, $hf);
        }
        if ($params['widget'] == 'plugin_hudson_project_joblastbuilds') {
            require_once('hudson_Widget_JobLastBuilds.class.php');
            $params['instance'] = new hudson_Widget_JobLastBuilds(ProjectDashboardController::LEGACY_DASHBOARD_TYPE, $request->get('group_id'), $hf);
        }
        if ($params['widget'] == 'plugin_hudson_project_jobtestresults') {
            require_once('hudson_Widget_JobTestResults.class.php');
            $params['instance'] = new hudson_Widget_JobTestResults(ProjectDashboardController::LEGACY_DASHBOARD_TYPE, $request->get('group_id'), $hf);
        }
        if ($params['widget'] == 'plugin_hudson_project_jobtesttrend') {
            require_once('hudson_Widget_JobTestTrend.class.php');
            $params['instance'] = new hudson_Widget_JobTestTrend(ProjectDashboardController::LEGACY_DASHBOARD_TYPE, $request->get('group_id'), $hf);
        }
        if ($params['widget'] == 'plugin_hudson_project_jobbuildhistory') {
            require_once('hudson_Widget_JobBuildHistory.class.php');
            $params['instance'] = new hudson_Widget_JobBuildHistory(ProjectDashboardController::LEGACY_DASHBOARD_TYPE, $request->get('group_id'), $hf);
        }
        if ($params['widget'] == 'plugin_hudson_project_joblastartifacts') {
            require_once('hudson_Widget_JobLastArtifacts.class.php');
            $params['instance'] = new hudson_Widget_JobLastArtifacts(ProjectDashboardController::LEGACY_DASHBOARD_TYPE, $request->get('group_id'), $hf);
        }
    }

    public function widgets($params)
    {
        if ($params['owner_type'] == UserDashboardController::LEGACY_DASHBOARD_TYPE) {
            $params['codendi_widgets'][] = 'plugin_hudson_my_jobs';
            $params['codendi_widgets'][] = 'plugin_hudson_my_joblastbuilds';
            $params['codendi_widgets'][] = 'plugin_hudson_my_jobtestresults';
            $params['codendi_widgets'][] = 'plugin_hudson_my_jobtesttrend';
            $params['codendi_widgets'][] = 'plugin_hudson_my_jobbuildhistory';
            $params['codendi_widgets'][] = 'plugin_hudson_my_joblastartifacts';
        }
        if ($params['owner_type'] == ProjectDashboardController::LEGACY_DASHBOARD_TYPE) {
            $params['codendi_widgets'][] = 'plugin_hudson_project_jobsoverview';
            $params['codendi_widgets'][] = 'plugin_hudson_project_joblastbuilds';
            $params['codendi_widgets'][] = 'plugin_hudson_project_jobtestresults';
            $params['codendi_widgets'][] = 'plugin_hudson_project_jobtesttrend';
            $params['codendi_widgets'][] = 'plugin_hudson_project_jobbuildhistory';
            $params['codendi_widgets'][] = 'plugin_hudson_project_joblastartifacts';
        }
    }

    public function uninstall()
    {
        $this->removeOrphanWidgets(array(
            'plugin_hudson_my_jobs',
            'plugin_hudson_my_joblastbuilds',
            'plugin_hudson_my_jobtestresults',
            'plugin_hudson_my_jobtesttrend',
            'plugin_hudson_my_jobbuildhistory',
            'plugin_hudson_my_joblastartifacts',
            'plugin_hudson_project_jobsoverview',
            'plugin_hudson_project_joblastbuilds',
            'plugin_hudson_project_jobtestresults',
            'plugin_hudson_project_jobtesttrend',
            'plugin_hudson_project_jobbuildhistory',
            'plugin_hudson_project_joblastartifacts'
        ));
    }

    function getAvailableReferenceNatures($params) {
        $hudson_plugin_reference_natures = array(
            'hudson_build'  => array('keyword' => 'build', 'label' => $GLOBALS['Language']->getText('plugin_hudson', 'reference_build_nature_key')),
            'hudson_job' => array('keyword' => 'job', 'label' => $GLOBALS['Language']->getText('plugin_hudson', 'reference_job_nature_key')));
        $params['natures'] = array_merge($params['natures'], $hudson_plugin_reference_natures);
    }

    function ajax_reference_tooltip($params) {
        require_once('HudsonJob.class.php');
        require_once('HudsonBuild.class.php');
        require_once('hudson_Widget_JobLastBuilds.class.php');

        $ref = $params['reference'];
        switch ($ref->getNature()) {
            case 'hudson_build':
                $val = $params['val'];
                $group_id = $params['group_id'];
                $job_dao = new PluginHudsonJobDao(CodendiDataAccess::instance());
                if (strpos($val, "/") !== false) {
                    $arr = explode("/", $val);
                    $job_name = $arr[0];
                    $build_id = $arr[1];
                    $dar = $job_dao->searchByJobName($job_name, $group_id);
                } else {
                    $build_id = $val;
                    $dar = $job_dao->searchByGroupID($group_id);
                    if ($dar->rowCount() != 1) {
                        $dar = null;
                    }
                }
                if ($dar && $dar->valid()) {
                    $row         = $dar->current();
                    $http_client = new Http_Client();
                    $build       = new HudsonBuild($row['job_url'] . '/' . $build_id . '/', $http_client);
                    echo '<strong>' . $GLOBALS['Language']->getText('plugin_hudson', 'build_time') . '</strong> ' . $build->getBuildTime() . '<br />';
                    echo '<strong>' . $GLOBALS['Language']->getText('plugin_hudson', 'status') . '</strong> ' . $build->getResult();
                } else {
                    echo '<span class="error">'.$GLOBALS['Language']->getText('plugin_hudson','error_object_not_found').'</span>';
                }
                break;
            case 'hudson_job':
                $job_dao = new PluginHudsonJobDao(CodendiDataAccess::instance());
                $job_name = $params['val'];
                $group_id = $params['group_id'];
                $dar = $job_dao->searchByJobName($job_name, $group_id);
                if ($dar->valid()) {
                    $row = $dar->current();
                    try {
                        $http_client = new Http_Client();
                        $job         = new HudsonJob($row['job_url'], $http_client);
                        $job_id      = $row['job_id'];

                        $html  = '';
                        $html .= '<table>';
                        $html .= ' <tr>';
                        $html .= '  <td colspan="2">';
                        $html .= '   <img src="'.$job->getStatusIcon().'" width="10" height="10" /> '.$job->getName().':';
                        $html .= '  </td>';
                        $html .= ' </tr>';
                        $html .= ' <tr>';
                        $html .= '  <td>';
                        $html .= '   <ul>';
                        if ($job->hasBuilds()) {
                            $html .= ' <li>'.$GLOBALS['Language']->getText('plugin_hudson', 'last_build').' <a href="/plugins/hudson/?action=view_build&group_id='.$group_id.'&job_id='.$job_id.'&build_id='.$job->getLastBuildNumber().'"># '.$job->getLastBuildNumber().'</a></li>';
                            $html .= ' <li>'.$GLOBALS['Language']->getText('plugin_hudson', 'last_build_success').' <a href="/plugins/hudson/?action=view_build&group_id='.$group_id.'&job_id='.$job_id.'&build_id='.$job->getLastSuccessfulBuildNumber().'"># '.$job->getLastSuccessfulBuildNumber().'</a></li>';
                            $html .= ' <li>'.$GLOBALS['Language']->getText('plugin_hudson', 'last_build_failure').' <a href="/plugins/hudson/?action=view_build&group_id='.$group_id.'&job_id='.$job_id.'&build_id='.$job->getLastFailedBuildNumber().'"># '.$job->getLastFailedBuildNumber().'</a></li>';
                        } else {
                            $html .= ' <li>'. $GLOBALS['Language']->getText('plugin_hudson', 'widget_build_not_found') . '</li>';
                        }
                        $html .= '   </ul>';
                        $html .= '  </td>';
                        $html .= '  <td class="widget_lastbuilds_weather">';
                        $html .= $GLOBALS['Language']->getText('plugin_hudson', 'weather_report').'<img src="'.$job->getWeatherReportIcon().'" align="middle" />';
                        $html .= '  </td>';
                        $html .= ' </tr>';
                        $html .= '</table>';
                        echo $html;
                    } catch (Exception $e) {
                    }
                } else {
                    echo '<span class="error">'.$GLOBALS['Language']->getText('plugin_hudson','error_object_not_found').'</span>';
                }
                break;
        }
    }

    function ajax_reference_sparkline($params) {
        require_once('HudsonJob.class.php');
        require_once('HudsonBuild.class.php');
        require_once('hudson_Widget_JobLastBuilds.class.php');

        $ref = $params['reference'];
        switch ($ref->getNature()) {
            case 'hudson_build':
                $val = $params['val'];
                $group_id = $params['group_id'];
                $job_dao = new PluginHudsonJobDao(CodendiDataAccess::instance());
                if (strpos($val, "/") !== false) {
                    $arr = explode("/", $val);
                    $job_name = $arr[0];
                    $build_id = $arr[1];
                    $dar = $job_dao->searchByJobName($job_name, $group_id);
                } else {
                    $build_id = $val;
                    $dar = $job_dao->searchByGroupID($group_id);
                    if ($dar->rowCount() != 1) {
                        $dar = null;
                    }
                }
                if ($dar && $dar->valid()) {
                    $row = $dar->current();
                    try {
                        $http_client         = new Http_Client();
                        $build               = new HudsonBuild($row['job_url'] . '/' . $build_id . '/', $http_client);
                        $params['sparkline'] = $build->getStatusIcon();
                    } catch (Exception $e) {
                    }
                }
                break;
            case 'hudson_job':
                $job_dao = new PluginHudsonJobDao(CodendiDataAccess::instance());
                $job_name = $params['val'];
                $group_id = $params['group_id'];
                $dar = $job_dao->searchByJobName($job_name, $group_id);
                if ($dar->valid()) {
                    $row = $dar->current();
                    try {
                        $http_client         = new Http_Client();
                        $job                 = new HudsonJob($row['job_url'], $http_client);
                        $params['sparkline'] = $job->getStatusIcon();
                    } catch (Exception $e) {
                    }
                }
                break;
        }
    }

    function process() {
        require_once('hudson.class.php');
        $controler = new hudson();
        $controler->process();
    }

    /**
     * Display CI statistics in CSV format
     *
     * @param Array $params parameters of the event
     *
     * @return void
     */
    public function statistics_collector($params) {
        if (!empty($params['formatter'])) {
            $formatter = $params['formatter'];
            $jobDao = new PluginHudsonJobDao(CodendiDataAccess::instance());
            $dar = $jobDao->countJobs($formatter->groupId);
            $count = 0;
            if ($dar && !$dar->isError()) {
                    $row = $dar->getRow();
                    if ($row) {
                        $count = $row['count'];
                    }
            }
            $formatter->clearContent();
            $formatter->addEmptyLine();
            $formatter->addLine(array($GLOBALS['Language']->getText('plugin_hudson', 'title')));
            $formatter->addLine(array($GLOBALS['Language']->getText('plugin_hudson', 'job_count', array(date('Y-m-d'))), $count));
            echo $formatter->getCsvContent();
            $formatter->clearContent();
        }
    }
}
