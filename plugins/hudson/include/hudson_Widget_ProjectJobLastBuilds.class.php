<?php
/**
 * @copyright Copyright (c) Xerox Corporation, CodeX, Codendi 2007-2008.
 *
 * This file is licensed under the GNU General Public License version 2. See the file COPYING.
 * 
 * @author Marc Nazarian <marc.nazarian@xrce.xerox.com>
 * 
 * hudson_Widget_ProjectJobLastBuilds 
 */

require_once('common/widget/Widget.class.php');
require_once('common/user/UserManager.class.php');
require_once('common/include/HTTPRequest.class.php');
require_once('PluginHudsonJobDao.class.php');
require_once('HudsonJob.class.php');

class hudson_Widget_ProjectJobLastBuilds extends Widget {
    
    var $plugin;
    var $group_id;
    
    var $job;
    var $job_url;
    var $job_id;
    
    function hudson_Widget_ProjectJobLastBuilds($plugin) {
        $this->Widget('projecthudsonjoblastbuilds');
        $this->plugin = $plugin;
        
        $request =& HTTPRequest::instance();
        $this->group_id = $request->get('group_id');
        
        $monitored_jobs = $this->_getMonitoredJobsByGroup();
        if (sizeof($monitored_jobs) > 0) {
            $monitored_job_id = $monitored_jobs[0]; // TODO : change

            $job_dao = new PluginHudsonJobDao(CodexDataAccess::instance());
            $dar = $job_dao->searchByJobID($monitored_job_id);
            if ($dar->valid()) {
                $row = $dar->current();
                $this->job_url = $row['job_url'];
                $this->job_id = $row['job_id'];

                try {
                    $this->job = new HudsonJob($this->job_url);
                } catch (Exception $e) {
                    $this->job = null;
                }
            }
        } else {
            $this->job = null;
        }
        
        
            
        /*$this->_not_monitored_jobs = user_get_preference('plugin_hudson_project_not_monitored_jobs');
        if ($this->_not_monitored_jobs === false) {
            $this->_not_monitored_jobs = array();
        } else {
            $this->_not_monitored_jobs = explode(",", $this->_not_monitored_jobs);
        }*/
        
    }
    
    function getTitle() {
        $title = '';
        $title .= $GLOBALS['Language']->getText('plugin_hudson', 'project_job_lastbuilds', array($this->job->getName())); 
        return  $title;
    }
    /*
    function updatePreferences(&$request) {
        $request->valid(new Valid_String('cancel'));
        if (!$request->exist('cancel')) {
            $monitored_jobs = $request->get('myhudsonjobs');
            
            $user = UserManager::instance()->getCurrentUser();
            $job_dao = new PluginHudsonJobDao(CodexDataAccess::instance());
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
            
            $use_global_status = $request->get('use_global_status');
            $this->_use_global_status = ($use_global_status !== false)?"true":"false";
            user_set_preference('plugin_hudson_use_global_status', $this->_use_global_status);
        }
        return true;
    }
    function getPreferences() {
        $prefs  = '';
        // Monitored jobs
        $prefs .= '<strong>'.$GLOBALS['Language']->getText('plugin_hudson', 'monitored_jobs').'</strong><br />';
        $user = UserManager::instance()->getCurrentUser();
        $job_dao = new PluginHudsonJobDao(CodexDataAccess::instance());
        $dar = $job_dao->searchByUserID($user->getId());
        while ($dar->valid()) {
            $row = $dar->current();
            try {
                $job = new Hudsonjob($row['job_url']);
                $prefs .= '<input type="checkbox" name="myhudsonjobs[]" value="'.$row['job_id'].'" '.(in_array($row['job_id'], $this->_not_monitored_jobs)?'':'checked="checked"').'> '.$job->getName().'<br />';
            } catch (Exception $e) {
                // Do not display wrong jobs
            }
            $dar->next();
        }
        
        // Use global status
        $prefs .= '<strong>'.$GLOBALS['Language']->getText('plugin_hudson', 'use_global_status').'</strong>';
        $prefs .= '<input type="checkbox" name="use_global_status" value="use_global" '.(($this->_use_global_status == "true")?'checked="checked"':'').'><br />';
        return $prefs;
    }*/
    
    function getContent() {
        $html = '';
        if ($this->job != null) {
                        
            $job = $this->job;
                        
            $html .= '<ul>';
            $html .= ' <li>'.$GLOBALS['Language']->getText('plugin_hudson', 'last_build').' <a href="/plugins/hudson/?action=view_build&group_id='.$this->group_id.'&job_id='.$this->job_id.'&build_id='.$job->getLastBuildNumber().'"># '.$job->getLastBuildNumber().'</a></li>';
            $html .= ' <li>'.$GLOBALS['Language']->getText('plugin_hudson', 'last_build_success').' <a href="/plugins/hudson/?action=view_build&group_id='.$this->group_id.'&job_id='.$this->job_id.'&build_id='.$job->getLastSuccessfulBuildNumber().'"># '.$job->getLastSuccessfulBuildNumber().'</a></li>';
            $html .= ' <li>'.$GLOBALS['Language']->getText('plugin_hudson', 'last_build_failure').' <a href="/plugins/hudson/?action=view_build&group_id='.$this->group_id.'&job_id='.$this->job_id.'&build_id='.$job->getLastFailedBuildNumber().'"># '.$job->getLastFailedBuildNumber().'</a></li>';
            $html .= '</ul>';
            
            $html .= $GLOBALS['Language']->getText('plugin_hudson', 'weather_report').'<img src="'.$job->getWeatherReportIcon().'" />';
                    
        }
            
        return $html;
    }
    
    function _getMonitoredJobsByGroup() {
        $job_dao = new PluginHudsonJobDao(CodexDataAccess::instance());
        $dar = $job_dao->searchByGroupID($this->group_id);
        $monitored_jobs = array();
        while ($dar->valid()) {
            $row = $dar->current();
            $monitored_jobs[] = $row['job_id'];                    
            $dar->next();
        }
        return $monitored_jobs;
    }
    
}

?>