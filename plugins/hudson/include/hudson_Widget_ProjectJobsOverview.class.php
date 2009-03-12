<?php
/**
 * @copyright Copyright (c) Xerox Corporation, CodeX, Codendi 2007-2008.
 *
 * This file is licensed under the GNU General Public License version 2. See the file COPYING.
 * 
 * @author Marc Nazarian <marc.nazarian@xrce.xerox.com>
 * 
 * hudson_Widget_ProjectJobsOverview 
 */

require_once('HudsonOverviewWidget.class.php');
require_once('common/user/UserManager.class.php');
require_once('common/include/HTTPRequest.class.php');
require_once('PluginHudsonJobDao.class.php');
require_once('HudsonJob.class.php');

class hudson_Widget_ProjectJobsOverview extends HudsonOverviewWidget {
    
    var $plugin;
    var $group_id;
    
    var $_not_monitored_jobs;
    var $_use_global_status = true;
    var $_all_status;
    var $_global_status;
    var $_global_status_icon;
    
    function hudson_Widget_ProjectJobsOverview($plugin) {
        $this->Widget('projecthudsonjobsoverview');
        $this->plugin = $plugin;
        
        $request =& HTTPRequest::instance();
        $this->group_id = $request->get('group_id');
        
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
        $jobs = $this->getJobsByGroup($this->group_id);
        foreach ($jobs as $job) {
            $this->_all_status[(string)$job->getColorNoAnime()] = $this->_all_status[(string)$job->getColorNoAnime()] + 1;    
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
        $title .= $GLOBALS['Language']->getText('plugin_hudson', 'project_jobs'); 
        return  $title;
    }
    
    function getDescription() {
        return $GLOBALS['Language']->getText('plugin_hudson', 'widget_description_jobsoverview');
    }
    
    function getContent() {
        $jobs = $this->getJobsByGroup($this->group_id);
        if (sizeof($jobs) > 0) {
            $html = '';            
            $html .= '<table style="width:100%">';
            $cpt = 1;
            
            foreach ($jobs as $job_id => $job) {
                try {
                    
                    $html .= '<tr class="'. util_get_alt_row_color($cpt) .'">';
                    $html .= ' <td>';
                    $html .= ' <img src="'.$job->getStatusIcon().'" title="'.$job->getStatus().'" >';
                    $html .= ' </td>';
                    $html .= ' <td style="width:99%">';
                    $html .= '  <a href="/plugins/hudson/?action=view_job&group_id='.$this->group_id.'&job_id='.$job_id.'">'.$job->getName().'</a><br />';
                    $html .= ' </td>';
                    $html .= '</tr>';
                        
                    $cpt++;
                    
                } catch (Exception $e) {
                    // Do not display wrong jobs
                }
            }
            $html .= '</table>';
            return $html;
        }
    }
    function getPreviewCssClass() {
        return parent::getPreviewCssClass('jobsoverview');
    }
}

?>