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


require_once('HudsonJobWidget.class.php');
require_once('common/user/UserManager.class.php');
require_once('common/include/HTTPRequest.class.php');
require_once('PluginHudsonJobDao.class.php');
require_once('HudsonBuild.class.php');

class hudson_Widget_JobLastArtifacts extends HudsonJobWidget {
    
    var $build;
    var $last_build_url;
    
    /**
     * Constructor
     *
     * @param String           $owner_type The owner type
     * @param Int              $owner_id   The owner id
     * @param HudsonJobFactory $factory    The HudsonJob factory
     * 
     * @return void
     */
    function __construct($owner_type, $owner_id, HudsonJobFactory $factory) {
        $request =& HTTPRequest::instance();
        if ($owner_type == WidgetLayoutManager::OWNER_TYPE_USER) {
            $this->widget_id = 'plugin_hudson_my_joblastartifacts';
            $this->group_id = $owner_id;
        } else {
            $this->widget_id = 'plugin_hudson_project_joblastartifacts';
            $this->group_id = $request->get('group_id');
        }
        parent::__construct($this->widget_id, $factory);
        
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
    
    function getDescription() {
        return $GLOBALS['Language']->getText('plugin_hudson', 'widget_description_lastartifacts');
    }
    
    function loadContent($id) {
        $this->content_id = $id;

        $sql = "SELECT * FROM plugin_hudson_widget WHERE widget_name='" . $this->widget_id . "' AND owner_id = ". $this->owner_id ." AND owner_type = '". $this->owner_type ."' AND id = ". $id;
        $res = db_query($sql);
        if ($res && db_numrows($res)) {
            $data = db_fetch_array($res);
            $this->job_id    = $data['job_id'];
            
            $jobs = $this->getAvailableJobs();
            
            if (array_key_exists($this->job_id, $jobs)) {
                try {
                    $used_job = $jobs[$this->job_id];
                    $this->job_url = $used_job->getUrl();
                    $this->job = $used_job;

                    $this->last_build_url = $this->job_url.'/lastBuild/';
                    $this->build = new HudsonBuild($this->last_build_url);
                } catch (Exception $e) {
                    $this->job = null;
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