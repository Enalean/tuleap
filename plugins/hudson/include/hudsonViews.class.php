<?php

/**
 * @copyright Copyright (c) Xerox Corporation, CodeX, Codendi 2007-2008.
 *
 * This file is licensed under the GNU General Public License version 2. See the file COPYING.
 * 
 * @author Marc Nazarian <marc.nazarian@xrce.xerox.com>
 *
 * HudsonViews
 */

require_once('common/mvc/Views.class.php');
require_once('common/include/HTTPRequest.class.php');
require_once('common/user/UserManager.class.php');

require_once('HudsonJob.class.php');

class hudsonViews extends Views {
    
    function hudsonViews(&$controler, $view=null) {
        $this->View($controler, $view);
    }
    
    function header() {
        $request =& HTTPRequest::instance();
        $GLOBALS['HTML']->header(array('title'=>$this->_getTitle(),'group' => $request->get('group_id'), 'toptab' => 'hudson'));
        echo '<h2>'.$this->_getTitle().'</h2>';
    }
    function _getTitle() {
        return $GLOBALS['Language']->getText('plugin_hudson','title');
    }
    function footer() {
        $GLOBALS['HTML']->footer(array());
    }
    
    // {{{ Views
    function projectOverview() {
        $request =& HTTPRequest::instance();
        $group_id = $request->get('group_id');
        $user = UserManager::instance()->getCurrentUser();
        
        $this->_display_jobs_table($group_id);       
        if ($user->isMember($request->get('group_id'), 'A')) {
            $this->_display_add_job_form($group_id);
        }
        $this->_display_iframe();
        $this->_hide_iframe();
    }
    
    function job_details() {
        $request =& HTTPRequest::instance();
        $group_id = $request->get('group_id');
        $job_id = $request->get('job_id');
        $user = UserManager::instance()->getCurrentUser();
        
        $job_dao = new PluginHudsonJobDao(CodexDataAccess::instance());
        $dar = $job_dao->searchByJobID($job_id);
        if ($dar->valid()) {
            $row = $dar->current();
            $this->_display_iframe($row['job_url']);
        } else {
            $this->_display_iframe();
        }
                
        
    }
    
    function last_build() {
        $request =& HTTPRequest::instance();
        $group_id = $request->get('group_id');
        $job_id = $request->get('job_id');

        $job_dao = new PluginHudsonJobDao(CodexDataAccess::instance());
        $dar = $job_dao->searchByJobID($job_id);
        if ($dar->valid()) {
            $row = $dar->current();
            $this->_display_iframe($row['job_url'].'/lastBuild/');
        } else {
            $this->_display_iframe();
        }
    }
    
    function build_number() {
        $request =& HTTPRequest::instance();
        $group_id = $request->get('group_id');
        $job_id = $request->get('job_id');
        $build_id = $request->get('build_id');

        $job_dao = new PluginHudsonJobDao(CodexDataAccess::instance());
        $dar = $job_dao->searchByJobID($job_id);
        if ($dar->valid()) {
            $row = $dar->current();
            $this->_display_iframe($row['job_url'].'/'.$build_id.'/');
        } else {
            $this->_display_iframe();
        }
    }
    
    function last_test_result() {
        $request =& HTTPRequest::instance();
        $group_id = $request->get('group_id');
        $job_id = $request->get('job_id');
        $user = UserManager::instance()->getCurrentUser();
        
        $job_dao = new PluginHudsonJobDao(CodexDataAccess::instance());
        $dar = $job_dao->searchByJobID($job_id);
        if ($dar->valid()) {
            $row = $dar->current();
            $this->_display_iframe($row['job_url'].'/lastBuild/testReport/');
        } else {var_dump($dar);
            $this->_display_iframe();
        }
    }
    
    function test_trend() {
        $request =& HTTPRequest::instance();
        $group_id = $request->get('group_id');
        $job_id = $request->get('job_id');
        $user = UserManager::instance()->getCurrentUser();
        
        $job_dao = new PluginHudsonJobDao(CodexDataAccess::instance());
        $dar = $job_dao->searchByJobID($job_id);
        if ($dar->valid()) {
            $row = $dar->current();
            $this->_display_iframe($row['job_url'].'/test/?width=800&height=600&failureOnly=false');
        } else {var_dump($dar);
            $this->_display_iframe();
        }
    }
    
    function editJob() {
        $request =& HTTPRequest::instance();
        $group_id = $request->get('group_id');
        $job_id = $request->get('job_id');
        $user = UserManager::instance()->getCurrentUser();
        if ($user->isMember($group_id, 'A')) {
            
            $job_dao = new PluginHudsonJobDao(CodexDataAccess::instance());
            $dar = $job_dao->searchByJobID($job_id);
            if ($dar->valid()) {
                $row = $dar->current();
            
                echo '<h3>'.$GLOBALS['Language']->getText('plugin_hudson','editjob_title').'</h3>';
                echo ' <form method="post">';
                echo '  <p>';
                echo '   <label for="new_hudson_job_url">'.$GLOBALS['Language']->getText('plugin_hudson','form_job_url').'</label>';
                echo '   <input id="new_hudson_job_url" name="new_hudson_job_url" type="text" value="'.$row['job_url'].'" size="64" />';
                echo '  </p>';
                echo '  <p>';
                echo '   <span class="legend">'.$GLOBALS['Language']->getText('plugin_hudson','form_joburl_example').'</span>';
                echo '  </p>';
                echo '  <p>';
                echo '   <input type="hidden" name="group_id" value="'.$group_id.'" />';
                echo '   <input type="hidden" name="job_id" value="'.$job_id.'" />';
                echo '   <input type="hidden" name="action" value="update_job" />';
                echo '   <input type="submit" value="'.$GLOBALS['Language']->getText('plugin_hudson','form_editjob_button').'" />';
                echo '  </p>';
                echo ' </form>';
                
            } else {
                
            }
        } else {
            
        }
    }
    // }}}
    
    function _display_jobs_table($group_id) {
        $request =& HTTPRequest::instance();
        $group_id = $request->get('group_id');
        $user = UserManager::instance()->getCurrentUser();
        $job_dao = new PluginHudsonJobDao(CodexDataAccess::instance());
        $dar = $job_dao->searchByGroupID($group_id);
        
        if ($dar && $dar->valid()) {

            echo '<table id="jobs_table">';
            echo ' <tr class="boxtable">';
            echo '  <th class="boxtitle">&nbsp;</th>';
            echo '  <th class="boxtitle">'.$GLOBALS['Language']->getText('plugin_hudson','header_table_job').'</th>';
            echo '  <th class="boxtitle">'.$GLOBALS['Language']->getText('plugin_hudson','header_table_lastsuccess').'</th>';
            echo '  <th class="boxtitle">'.$GLOBALS['Language']->getText('plugin_hudson','header_table_lastfailure').'</th>';
            echo '  <th class="boxtitle">'.$GLOBALS['Language']->getText('plugin_hudson','header_table_rss').'</th>';
            if ($user->isMember($request->get('group_id'), 'A')) {
                echo '  <th class="boxtitle">'.$GLOBALS['Language']->getText('plugin_hudson','header_table_actions').'</th>';
            }
            echo ' </tr>';
            
            $cpt = 1;
            while ($dar->valid()) {
                $row = $dar->current();

                echo ' <tr class="'. util_get_alt_row_color($cpt) .'">';
                
                try {
                    $job = new HudsonJob($row['job_url']);
                    
                    echo '  <td><img src="'.$job->getStatusIcon().'" alt="'.$job->getStatus().'" title="'.$job->getStatus().'" /></td>';
                    // function toggle_iframe is in script plugins/hudson/www/hudson_tab.js
                    echo '  <td class="boxitem"><a href="'.$job->getUrl().'" onclick="toggle_iframe(this); return false;">'.$job->getName().'</a></td>';
                    if ($job->getLastSuccessfulBuildNumber() != '') {
                        echo '  <td><a href="'.$job->getLastSuccessfulBuildUrl().'" onclick="toggle_iframe(this); return false;">'.$GLOBALS['Language']->getText('plugin_hudson','build').' #'.$job->getLastSuccessfulBuildNumber().'</a></td>';
                    } else {
                        echo '  <td>&nbsp;</td>';
                    }
                    if ($job->getLastFailedBuildNumber() != '') {
                        echo '  <td><a href="'.$job->getLastFailedBuildUrl().'" onclick="toggle_iframe(this); return false;">'.$GLOBALS['Language']->getText('plugin_hudson','build').' #'.$job->getLastFailedBuildNumber().'</a></td>';
                    } else {
                        echo '  <td>&nbsp;</td>';
                    }
                    echo '  <td align="center"><a href="'.$job->getUrl().'/rssAll" onclick="toggle_iframe(this); return false;"><img src="'.$this->getControler()->getIconsPath().'rss_feed.png" alt="" title=""></a></td>';
                                    
                } catch (Exception $e) {
                    echo '  <td><img src="'.$this->getControler()->getIconsPath().'link_error.png" alt="'.$e->getMessage().'" title="'.$e->getMessage().'" /></td>';
                    echo '  <td colspan="4"><span class="error">'.$e->getMessage().'</span></td>';
                }
                
                if ($user->isMember($request->get('group_id'), 'A')) {
                    echo '  <td>';
                    // edit job
                    echo '   <span class="job_action">';
                    echo '    <a href="?action=edit_job&group_id='.$group_id.'&job_id='.$row['job_id'].'">'.$GLOBALS['HTML']->getimage('ic/edit.png').'</a>';
                    echo '   </span>';
                    // delete job
                    echo '   <span class="job_action">';
                    echo '    <a href="?action=delete_job&group_id='.$group_id.'&job_id='.$row['job_id'].'">'.$GLOBALS['HTML']->getimage('ic/cross.png').'</a>';
                    echo '   </span>';
                    echo '  </td>';
                }
                
                echo ' </tr>';
                
                $dar->next();
                $cpt++;
            }
            echo '</table>';   
        }
    }
    
    function _display_add_job_form($group_id) {
        // function toggle_addurlform is in script plugins/hudson/www/hudson_tab.js
        echo '<a href="#" onclick="toggle_addurlform(); return false;">' . $GLOBALS["HTML"]->getimage("ic/add.png") . ' '.$GLOBALS['Language']->getText('plugin_hudson','addjob_title').'</a>';
        echo '<div id="hudson_add_job">';
        echo ' <form>';
        echo '   <label for="hudson_job_url">Job URL:</label>';
        echo '   <input id="hudson_job_url" name="hudson_job_url" type="text" size="64" />';
        echo '   <input type="hidden" name="group_id" value="'.$group_id.'" />';
        echo '   <input type="hidden" name="action" value="add_job" />';
        echo '   <input type="submit" value="Add job" />';
        echo '   <br />';
        echo '   <span class="legend">'.$GLOBALS['Language']->getText('plugin_hudson','form_joburl_example').'</span>';
        echo ' </form>';
        echo '</div>';
        echo "<script>Element.toggle('hudson_add_job', 'slide');</script>";
    }
    
    function _display_iframe($url = '') {
        echo '<div id="hudson_iframe_div">';
        $GLOBALS['HTML']->iframe($url, array('id' => 'hudson_iframe', 'class' => 'iframe_service'));
        echo '</div>';
    }
    function _hide_iframe() {
        echo "<script>Element.toggle('hudson_iframe_div', 'slide');</script>";
    }
}


?>