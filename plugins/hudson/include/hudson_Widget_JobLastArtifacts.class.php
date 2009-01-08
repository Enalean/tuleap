<?php
/**
 * @copyright Copyright (c) Xerox Corporation, CodeX, Codendi 2007-2008.
 *
 * This file is licensed under the GNU General Public License version 2. See the file COPYING.
 * 
 * @author Marc Nazarian <marc.nazarian@xrce.xerox.com>
 * 
 * hudson_Widget_JobLastArtifacts 
 */

require_once('HudsonJobWidget.class.php');
require_once('common/user/UserManager.class.php');
require_once('common/include/HTTPRequest.class.php');
require_once('PluginHudsonJobDao.class.php');
require_once('HudsonBuild.class.php');

class hudson_Widget_JobLastArtifacts extends HudsonJobWidget {
    
    var $build;
    var $last_build_url;
    
    function hudson_Widget_JobLastArtifacts($owner_type, $owner_id) {
        $request =& HTTPRequest::instance();
        $wlm = new WidgetLayoutManager();
        if ($owner_type == $wlm->OWNER_TYPE_USER) {
            $this->widget_id = 'myhudsonjoblastartifacts';
            $this->group_id = $owner_id;
        } else {
            $this->widget_id = 'projecthudsonjoblastartifacts';
            $this->group_id = $request->get('group_id');
        }
        $this->Widget($this->widget_id);
        
        $this->setOwner($owner_id, $owner_type);
    }
    
    function getTitle() {
        $title = '';
        if ($this->job) {
            $title .= $GLOBALS['Language']->getText('plugin_hudson', 'project_job_lastartifacts', array($this->job->getName()));
        } else {
             $title .= $GLOBALS['Language']->getText('plugin_hudson', 'project_job_lastartifacts');
        }
        return  $title;
    }
    
    
    function loadContent($id) {
        $sql = "SELECT * FROM plugin_hudson_widget WHERE widget_name='" . $this->widget_id . "' AND owner_id = ". $this->owner_id ." AND owner_type = '". $this->owner_type ."' AND id = ". $id;
        $res = db_query($sql);
        if ($res && db_numrows($res)) {
            $data = db_fetch_array($res);
            $this->job_id    = $data['job_id'];
            $this->content_id = $id;
            
            $jobs = $this->getAvailableJobs();
            
            if (array_key_exists($this->job_id, $jobs)) {
                $used_job = $jobs[$this->job_id];
                $this->job_url = $used_job->getUrl();
                $this->job = $used_job;
                
                $this->last_build_url = $this->job_url.'/lastBuild/';

                try {
                    $this->build = new HudsonBuild($this->last_build_url);
                } catch (Exception $e) {
                    $this->build = null;
                }   
            } else {
                $this->job = null;
                $this->build = null;
            }
            
        }
    }
    
    function getContent() {
        $html = '';
        if ($this->job != null && $this->build != null) {
                        
            $build = $this->build;
            
            $html .= '<ul>';
            $dom = $build->getDom();
            foreach ($dom->artifact as $artifact) {
                $html .= ' <li><a href="'.$build->getUrl().'/artifact/'.$artifact->relativePath.'">'.$artifact->displayPath.'</a></li>';
            }
            $html .= '</ul>';
        } else {
            if ($this->job != null) {
                $html .= $GLOBALS['Language']->getText('plugin_hudson', 'widget_build_not_found');
            } else {
                $html .= $GLOBALS['Language']->getText('plugin_hudson', 'widget_job_not_found');
            }
        }            
        return $html;
    }
    
}

?>