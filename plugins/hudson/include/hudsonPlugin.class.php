<?php
/**
 * @copyright Copyright (c) Xerox Corporation, Codendi 2007-2008.
 *
 * This file is licensed under the GNU General Public License version 2. See the file COPYING.
 * 
 * @author Marc Nazarian <marc.nazarian@xrce.xerox.com>
 *
 * HudsonPlugin
 */

require_once('common/plugin/Plugin.class.php');
require_once('PluginHudsonJobDao.class.php');

class hudsonPlugin extends Plugin {
	
	function hudsonPlugin($id) {
		$this->Plugin($id);
        $this->_addHook('javascript_file', 'jsFile', false);
        $this->_addHook('cssfile', 'cssFile', false);
        
        $this->_addHook('project_is_deleted', 'projectIsDeleted', false);
        
        $this->_addHook('widget_instance', 'myPageBox', false);
        $this->_addHook('widgets', 'widgets', false);
        
        $this->_addHook('get_available_reference_natures', 'getAvailableReferenceNatures', false);
        $this->_addHook('ajax_reference_tooltip', 'ajax_reference_tooltip', false);
	}
	
    function &getPluginInfo() {
        if (!is_a($this->pluginInfo, 'hudsonPluginInfo')) {
            require_once('hudsonPluginInfo.class.php');
            $this->pluginInfo =& new hudsonPluginInfo($this);
        }
        return $this->pluginInfo;
    }
    
    function cssFile($params) {
        // Only show the stylesheet if we're actually in the hudson pages.
        // This stops styles inadvertently clashing with the main site.
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0 ||
            strpos($_SERVER['REQUEST_URI'], '/my/') === 0 ||
            strpos($_SERVER['REQUEST_URI'], '/projects/') === 0 ||
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
            echo '<script type="text/javascript" src="hudson_tab.js"></script>'."\n";
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
    
    function myPageBox($params) {
        require_once('common/widget/WidgetLayoutManager.class.php');
        
        $user = UserManager::instance()->getCurrentUser();
        
        // MY
        if ($params['widget'] == 'plugin_hudson_my_jobs') {
            require_once('hudson_Widget_MyMonitoredJobs.class.php');
            $params['instance'] = new hudson_Widget_MyMonitoredJobs($this);
        }
        if ($params['widget'] == 'plugin_hudson_my_joblastbuilds') {
            require_once('hudson_Widget_JobLastBuilds.class.php');
            $params['instance'] = new hudson_Widget_JobLastBuilds(WidgetLayoutManager::OWNER_TYPE_USER, $user->getId());
        }
        if ($params['widget'] == 'plugin_hudson_my_jobtestresults') {
            require_once('hudson_Widget_JobTestResults.class.php');
            $params['instance'] = new hudson_Widget_JobTestResults(WidgetLayoutManager::OWNER_TYPE_USER, $user->getId());
        }
        if ($params['widget'] == 'plugin_hudson_my_jobtesttrend') {
            require_once('hudson_Widget_JobTestTrend.class.php');
            $params['instance'] = new hudson_Widget_JobTestTrend(WidgetLayoutManager::OWNER_TYPE_USER, $user->getId());
        }
        if ($params['widget'] == 'plugin_hudson_my_jobbuildhistory') {
            require_once('hudson_Widget_JobBuildHistory.class.php');
            $params['instance'] = new hudson_Widget_JobBuildHistory(WidgetLayoutManager::OWNER_TYPE_USER, $user->getId());
        }
        if ($params['widget'] == 'plugin_hudson_my_joblastartifacts') {
            require_once('hudson_Widget_JobLastArtifacts.class.php');
            $params['instance'] = new hudson_Widget_JobLastArtifacts(WidgetLayoutManager::OWNER_TYPE_USER, $user->getId());
        }
        
        // PROJECT
        if ($params['widget'] == 'plugin_hudson_project_jobsoverview') {
            require_once('hudson_Widget_ProjectJobsOverview.class.php');
            $params['instance'] = new hudson_Widget_ProjectJobsOverview($this);
        }
        if ($params['widget'] == 'plugin_hudson_project_joblastbuilds') {
            require_once('hudson_Widget_JobLastBuilds.class.php');
            $params['instance'] = new hudson_Widget_JobLastBuilds(WidgetLayoutManager::OWNER_TYPE_GROUP, $GLOBALS['group_id']);
        }
        if ($params['widget'] == 'plugin_hudson_project_jobtestresults') {
            require_once('hudson_Widget_JobTestResults.class.php');
            $params['instance'] = new hudson_Widget_JobTestResults(WidgetLayoutManager::OWNER_TYPE_GROUP, $GLOBALS['group_id']);
        }
        if ($params['widget'] == 'plugin_hudson_project_jobtesttrend') {
            require_once('hudson_Widget_JobTestTrend.class.php');
            $params['instance'] = new hudson_Widget_JobTestTrend(WidgetLayoutManager::OWNER_TYPE_GROUP, $GLOBALS['group_id']);
        }
        if ($params['widget'] == 'plugin_hudson_project_jobbuildhistory') {
            require_once('hudson_Widget_JobBuildHistory.class.php');
            $params['instance'] = new hudson_Widget_JobBuildHistory(WidgetLayoutManager::OWNER_TYPE_GROUP, $GLOBALS['group_id']);
        }
        if ($params['widget'] == 'plugin_hudson_project_joblastartifacts') {
            require_once('hudson_Widget_JobLastArtifacts.class.php');
            $params['instance'] = new hudson_Widget_JobLastArtifacts(WidgetLayoutManager::OWNER_TYPE_GROUP, $GLOBALS['group_id']);
        }
    }
    function widgets($params) {
        require_once('common/widget/WidgetLayoutManager.class.php');
        if ($params['owner_type'] == WidgetLayoutManager::OWNER_TYPE_USER) {
            $params['codendi_widgets'][] = 'plugin_hudson_my_jobs';
            $params['codendi_widgets'][] = 'plugin_hudson_my_joblastbuilds';
            $params['codendi_widgets'][] = 'plugin_hudson_my_jobtestresults';
            $params['codendi_widgets'][] = 'plugin_hudson_my_jobtesttrend';
            $params['codendi_widgets'][] = 'plugin_hudson_my_jobbuildhistory';
            $params['codendi_widgets'][] = 'plugin_hudson_my_joblastartifacts';
        }
        if ($params['owner_type'] == WidgetLayoutManager::OWNER_TYPE_GROUP) {
            $params['codendi_widgets'][] = 'plugin_hudson_project_jobsoverview';
            $params['codendi_widgets'][] = 'plugin_hudson_project_joblastbuilds';
            $params['codendi_widgets'][] = 'plugin_hudson_project_jobtestresults';
            $params['codendi_widgets'][] = 'plugin_hudson_project_jobtesttrend';
            $params['codendi_widgets'][] = 'plugin_hudson_project_jobbuildhistory';
            $params['codendi_widgets'][] = 'plugin_hudson_project_joblastartifacts';
        }
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
                    $row = $dar->current();
                    $build = new HudsonBuild($row['job_url'].'/'.$build_id.'/');
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
                        $job = new HudsonJob($row['job_url']);
                        $job_id = $row['job_id'];
                        $html = '';
                        $html .= '<table>';
                        $html .= ' <tr>';
                        $html .= '  <td colspan="2">';
                        $html .= '   '.$job->getName().': <img src="'.$job->getStatusIcon().'" />';
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
    
    function process() {
        require_once('hudson.class.php');
        $controler =& new hudson();
        $controler->process();
    }
    
}

?>