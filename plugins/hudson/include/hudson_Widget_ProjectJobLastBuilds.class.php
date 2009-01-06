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

require_once('HudsonWidget.class.php');
require_once('common/user/UserManager.class.php');
require_once('common/include/HTTPRequest.class.php');
require_once('PluginHudsonJobDao.class.php');
require_once('HudsonJob.class.php');

class hudson_Widget_ProjectJobLastBuilds extends HudsonWidget {
    
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
        
        $jobs = $this->getJobsByGroup($this->group_id);
        if (sizeof($jobs) > 0) {
            
            /////////////////////////////////////////////////////////////////
            // TODO : change
            $used_job_id = array_shift(array_keys($jobs)); // TODO : change
            $used_job = $jobs[$used_job_id]; // TODO : change
            /////////////////////////////////////////////////////////////////

            $this->job_url = $used_job->getUrl();
            $this->job_id = $used_job_id;
            $this->job = $used_job;

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
    }*/
    
    function getPreferences() {
        $prefs  = '';
        // jobs
        $prefs .= '<strong>'.$GLOBALS['Language']->getText('plugin_hudson', 'monitored_job').'</strong><br />';
        $jobs = $this->getJobsByGroup($this->group_id);
        foreach ($jobs as $job_id => $job) {
            $prefs .= '<input type="radio" name="hudsonprojectjoblastbuilds" value="'.$job_id.'"> '.$job->getName().'<br />';
        }
        
        return $prefs;
    }
    
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
    
    function isUnique() {
        return false;
    }
    
}

?>