<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */


require_once('HudsonOverviewWidget.class.php');
require_once('common/user/UserManager.class.php');
require_once('common/include/HTTPRequest.class.php');
require_once('PluginHudsonJobDao.class.php');
require_once('HudsonJob.class.php');

class hudson_Widget_MyMonitoredJobs extends HudsonOverviewWidget {
    
    var $plugin;
    
    var $_not_monitored_jobs;
    var $_use_global_status;
    var $_all_status;
    var $_global_status;
    var $_global_status_icon;
    
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
        
        $this->_use_global_status = user_get_preference('plugin_hudson_use_global_status');
        if ($this->_use_global_status === false) {
            $this->_use_global_status = "false";
            user_set_preference('plugin_hudson_use_global_status', $this->_use_global_status);
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
        $monitored_jobs = $this->_getMonitoredJobsByUser();
        foreach ($monitored_jobs as $monitored_job) {
            try {
                $job_dao = new PluginHudsonJobDao(CodendiDataAccess::instance());
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
    
    function getTitle() {
        return parent::getTitle($GLOBALS['Language']->getText('plugin_hudson', 'my_jobs'));
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
            
            $use_global_status = $request->get('use_global_status');
            $this->_use_global_status = ($use_global_status !== false)?"true":"false";
            user_set_preference('plugin_hudson_use_global_status', $this->_use_global_status);
        }
        return true;
    }
    function hasPreferences() {
        return true;
    }
    
    /**
     * Returns user preferences for given widget
     * 
     * Do not attempt to load remote jenkins job otherwise, user might be stuck if there are a lot of "not responding jobs".
     * 
     * @see src/common/widget/Widget::getPreferences()
     */
    function getPreferences() {
        $prefs  = '';
        // Monitored jobs
        $prefs .= '<strong>'.$GLOBALS['Language']->getText('plugin_hudson', 'monitored_jobs').'</strong><br />';
        $user = UserManager::instance()->getCurrentUser();
        $job_dao = new PluginHudsonJobDao(CodendiDataAccess::instance());
        $dar = $job_dao->searchByUserID($user->getId());
        foreach ($dar as $row) {
            $prefs .= '<input type="checkbox" name="myhudsonjobs[]" value="'.$row['job_id'].'" '.(in_array($row['job_id'], $this->_not_monitored_jobs)?'':'checked="checked"').'> '.$row['name'].'<br />';
        }
        // Use global status
        $prefs .= '<strong>'.$GLOBALS['Language']->getText('plugin_hudson', 'use_global_status').'</strong>';
        $prefs .= '<input type="checkbox" name="use_global_status" value="use_global" '.(($this->_use_global_status == "true")?'checked="checked"':'').'><br />';
        return $prefs;
    }
    
    function getContent() {
    	$html = '';
    	
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
