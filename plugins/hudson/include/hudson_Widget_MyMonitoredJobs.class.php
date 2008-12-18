<?php
/**
 * @copyright Copyright (c) Xerox Corporation, CodeX, Codendi 2007-2008.
 *
 * This file is licensed under the GNU General Public License version 2. See the file COPYING.
 * 
 * @author Marc Nazarian <marc.nazarian@xrce.xerox.com>
 * 
 * hudson_Widget_MyMonitoredJobs 
 */

require_once('common/widget/Widget.class.php');
require_once('common/user/UserManager.class.php');
require_once('common/include/HTTPRequest.class.php');
require_once('PluginHudsonJobDao.class.php');
require_once('HudsonJob.class.php');

class hudson_Widget_MyMonitoredJobs extends Widget {
    
    var $plugin;
    
    var $_monitored_jobs;
    var $_use_global_status;
    var $_all_status;
    var $_global_status;
    var $_global_status_icon;
    
    function hudson_Widget_MyMonitoredJobs($plugin) {
        $this->Widget('myhudsonjobs');
        $this->plugin = $plugin;
        
        $this->_monitored_jobs = user_get_preference('plugin_hudson_my_monitored_jobs');
        if ($this->_monitored_jobs === false) {
            $this->_monitored_jobs = array();
            
            $user = UserManager::instance()->getCurrentUser();
            $job_dao = new PluginHudsonJobDao(CodexDataAccess::instance());
            $dar = $job_dao->searchByUserID($user->getId());
            while ($dar->valid()) {
                $row = $dar->current();
                $this->_monitored_jobs[] = $row['job_id'];
                $dar->next();
            }
            user_set_preference('plugin_hudson_my_monitored_jobs', implode(",", $this->_monitored_jobs));
        } else {
            $this->_monitored_jobs = explode(",", $this->_monitored_jobs);
        }
        
        $this->_use_global_status = user_get_preference('plugin_hudson_use_global_status');
        if ($this->_use_global_status === false) {
            $this->_use_global_status = "true";
            user_set_preference('plugin_hudson_use_global_status', $this->_use_global_status);
        }
        
        if ($this->_use_global_status == "true") {
            $this->_all_status = array(
                'grey' => 0,
                'blue' => 0,
                'yellow' => 0,
                'red' => 0,
            );
            $this->computeGlobalStatus();
        }
        
    }
    
    function computeGlobalStatus() {
        foreach ($this->_monitored_jobs as $monitored_job) {
            try {
                $job_dao = new PluginHudsonJobDao(CodexDataAccess::instance());
                $dar = $job_dao->searchByJobID($monitored_job);
                if ($dar->valid()) {
                    $row = $dar->current();
                    $job_url = $row['job_url'];
                    $job = new HudsonJob($job_url);
                    $this->_all_status[(string)$job->getColorNoAnime()] = $this->_all_status[(string)$job->getColorNoAnime()] + 1;    
                }
            } catch (Exception $e) {
                // Do not display wrong jobs
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
    
    function getTitle() {
        $title = '';
        if ($this->_use_global_status == "true") {
            $title = '<img src="'.$this->_global_status_icon.'" title="'.$this->_global_status.'" alt="'.$this->_global_status.'" /> ';
        }
        $title .= $GLOBALS['Language']->getText('plugin_hudson', 'my_jobs'); 
        return  $title;
    }
    
    function updatePreferences(&$request) {
        $request->valid(new Valid_String('cancel'));
        if (!$request->exist('cancel')) {
            $monitored_jobs = $request->get('myhudsonjobs');
            $this->_monitored_jobs = $monitored_jobs;
            user_set_preference('plugin_hudson_my_monitored_jobs', implode(",", $monitored_jobs));
            
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
                $prefs .= '<input type="checkbox" name="myhudsonjobs[]" value="'.$row['job_id'].'" '.(in_array($row['job_id'], $this->_monitored_jobs)?'checked="checked"':'').'> '.$job->getName().'<br />';
            } catch (Exception $e) {
                // Do not display wrong jobs
            }
            $dar->next();
        }
        
        // Use global status
        $prefs .= '<strong>'.$GLOBALS['Language']->getText('plugin_hudson', 'use_global_status').'</strong>';
        $prefs .= '<input type="checkbox" name="use_global_status" value="use_global" '.(($this->_use_global_status == "true")?'checked="checked"':'').'><br />';
        return $prefs;
    }
    
    function getContent() {
        if (sizeof($this->_monitored_jobs) > 0) {
            $html = '';            
            $html .= '<table style="width:100%">';
            $cpt = 1;
            foreach ($this->_monitored_jobs as $monitored_job) {
                try {
                    
                    $job_dao = new PluginHudsonJobDao(CodexDataAccess::instance());
                    $dar = $job_dao->searchByJobID($monitored_job);
                    if ($dar->valid()) {
                        $row = $dar->current();
                        $job_url = $row['job_url'];
                        $job_id = $row['job_id'];
                        $group_id = $row['group_id'];
                        $job = new HudsonJob($job_url);
                        
                        $html .= '<tr class="'. util_get_alt_row_color($cpt) .'">';
                        $html .= ' <td>';
                        $html .= ' <img src="'.$job->getStatusIcon().'" title="'.$job->getStatus().'" >';
                        $html .= ' </td>';
                        $html .= ' <td style="width:99%">';
                        $html .= '  <a href="/plugins/hudson/?action=view_job&group_id='.$group_id.'&job_id='.$job_id.'">'.$job->getName().'</a><br />';
                        $html .= ' </td>';
                        $html .= '</tr>';
                        
                        $cpt++;
                    }
                } catch (Exception $e) {
                    // Do not display wrong jobs
                }
            }
            $html .= '</table>';
            return $html;
        }
    }

}

?>
