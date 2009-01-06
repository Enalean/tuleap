<?php
/**
 * @copyright Copyright (c) Xerox Corporation, CodeX, Codendi 2007-2008.
 *
 * This file is licensed under the GNU General Public License version 2. See the file COPYING.
 * 
 * @author Marc Nazarian <marc.nazarian@xrce.xerox.com>
 * 
 * hudson_Widget_ProjectJobTestResults 
 */

require_once('HudsonWidget.class.php');
require_once('common/user/UserManager.class.php');
require_once('common/include/HTTPRequest.class.php');
require_once('PluginHudsonJobDao.class.php');
require_once('HudsonJob.class.php');
require_once('HudsonTestResult.class.php');

class hudson_Widget_ProjectJobTestResults extends HudsonWidget {
    
    var $plugin;
    var $group_id;
    
    var $job;
    var $test_result;
    var $job_url;
    var $job_id;
    
    function hudson_Widget_ProjectJobTestResults($plugin) {
        $this->Widget('projecthudsonjobtestresults');
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
            
            try {
                $this->test_result = new HudsonTestResult($this->job_url);
            } catch (Exception $e) {
                $this->test_result = null;
            }
        } else {
            $this->job = null;
            $this->test_result = null;
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
        $title .= $GLOBALS['Language']->getText('plugin_hudson', 'project_job_testresults_widget_title', array($this->job->getName(), $this->test_result->getPassCount(), $this->test_result->getTotalCount())); 
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
        if ($this->job != null && $this->test_result != null) {
                        
            $job = $this->job;
            $test_result = $this->test_result;        

            $html .= '<div style="padding: 20px;">';
            $html .= ' <a href="/plugins/hudson/?action=view_last_test_result&group_id='.$this->group_id.'&job_id='.$this->job_id.'">'.$test_result->getTestResultPieChart().'</a>';
            $html .= '</div>';

        }
            
        return $html;
    }
    
}

?>