<?php

require_once('common/widget/Widget.class.php');
require_once('common/user/UserManager.class.php');
require_once('common/include/HTTPRequest.class.php');
/**
* IM_Widget
* 
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
* Copyright (c) Enalean, 2017. All rights reserved
*
* This file is a part of Tuleap.
*
* Tuleap is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* Tuleap is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Tuleap. If not, see <http://www.gnu.org/licenses/
*
* @author  M. Nazarian, D. Madruga De Aquino, Z. Diallo
*/
class IM_Widget_MyRoster extends Widget {
    var $plugin;
    var $request;

    public function __construct($plugin)
    {
        parent::__construct('plugin_im_myroster');
        $this->plugin  = $plugin;
        $this->request = HTTPRequest::instance();
    }

    function getTitle() {
        return $GLOBALS['Language']->getText('plugin_im', 'my_roster');
    }
    
    function getContent() {
        $request = HTTPRequest::instance();
        $user = UserManager::instance()->getCurrentUser();
        //group id of the user is member
        $projects_id_user=$user->getProjects();
        $html = '';
        $pm = ProjectManager::instance();
        foreach ($projects_id_user as $project_id) {
            $project = $pm->getProject($project_id);
            $project_unix_name=$project->getUnixName();
            $project_public_name=$project->getPublicName();
            $members_id_array=$project->getMembersUserNames();
            if(sizeof($members_id_array)>1){
                list($hide_now,$count_diff,$hide_url) = my_hide_url('im_group',$project_id,$request->get('hide_item_id'),count($members_id_array),$request->get('hide_im_group'), $request->get('dashboard_id'));
                $html .= $hide_url;
                $html .= '<b>'. $project_public_name .'</b><br>';
                if (!$hide_now) {
                    $html .= '<div style="padding-left:20px;">';
                    foreach ($members_id_array as $member){
                        $html .= $this->plugin->getDisplayPresence($member['user_id'], $member['user_name'], $member['realname']);
                        $html .= '<br>';
                    }
                    $html .= '</div>';
                }
               
            }
        }
        return $html;
    }
    
    function getDescription() {
        return $GLOBALS['Language']->getText('plugin_im','widget_description');
    }
    
    function isAvailable() {
        return ! UserManager::instance()->getCurrentUser()->getPreference('plugin_im_hide_users_presence');
    }
    
    /**
     * Say if the widget should display its content via ajax. This speed up the 
     * rendering of the page but defer the rendering of the dashboard.
     *
     * @return boolean
     */
    function isAjax() {
        // We cannot be in ajax mode since there is some javascript code to 
        // execute in the content (for security reasons scripts are removed from 
        // ajax response).
        return false;
    }
}

?>