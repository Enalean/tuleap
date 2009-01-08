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
    
    const WIDGET_ID = 'projecthudsonjobtestresults';
    
    var $group_id;
    
    var $job;
    var $test_result;
    var $job_url;
    var $job_id;
    
    function hudson_Widget_ProjectJobTestResults($owner_type, $owner_id) {
        $this->Widget(self::WIDGET_ID);
        
        $request =& HTTPRequest::instance();
        $this->group_id = $request->get('group_id');
        
        $this->setOwner($owner_id, $owner_type);
    }
    
    function getTitle() {
        $title = '';
        if ($this->job && $this->test_result) {
            $title .= $GLOBALS['Language']->getText('plugin_hudson', 'project_job_testresults_widget_title', array($this->job->getName(), $this->test_result->getPassCount(), $this->test_result->getTotalCount()));
        } elseif ($this->job && ! $this->test_result) {
            $title .= $GLOBALS['Language']->getText('plugin_hudson', 'project_job_testresults_projectname', array($this->job->getName()));
        } else {
            $title .= $GLOBALS['Language']->getText('plugin_hudson', 'project_job_testresults');
        }
        return  $title;
    }
    
    function loadContent($id) {
        $sql = "SELECT * FROM plugin_hudson_widget WHERE widget_name='" . self::WIDGET_ID . "' AND owner_id = ". $this->owner_id ." AND owner_type = '". $this->owner_type ."' AND id = ". $id;
        $res = db_query($sql);
        if ($res && db_numrows($res)) {
            $data = db_fetch_array($res);
            $this->job_id    = $data['job_id'];
            $this->content_id = $id;
            
            $jobs = $this->getJobsByGroup($this->group_id);
            if (array_key_exists($this->job_id, $jobs)) {
                $used_job = $jobs[$this->job_id];
                $this->job_url = $used_job->getUrl();
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
            
        }
    }
    
    function getContent() {
        $html = '';
        if ($this->job != null && $this->test_result != null) {
                        
            $job = $this->job;
            $test_result = $this->test_result;

            $html .= '<div style="padding: 20px;">';
            $html .= ' <a href="/plugins/hudson/?action=view_last_test_result&group_id='.$this->group_id.'&job_id='.$this->job_id.'">'.$test_result->getTestResultPieChart().'</a>';
            $html .= '</div>';
            
        } else {
            if ($this->job != null) {
                $html .= $GLOBALS['Language']->getText('plugin_hudson', 'widget_tests_not_found');
            } else {
                $html .= $GLOBALS['Language']->getText('plugin_hudson', 'widget_job_not_found');
            }
        }
            
        return $html;
    }

}

?>