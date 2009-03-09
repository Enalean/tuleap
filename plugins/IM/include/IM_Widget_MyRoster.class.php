<?php

require_once('common/widget/Widget.class.php');
require_once('common/user/UserManager.class.php');
require_once('common/include/HTTPRequest.class.php');
/**
* IM_Widget
* 
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2008. All rights reserved
*
* @author  M. Nazarian, D. Madruga De Aquino, Z. Diallo
*/
class IM_Widget_MyRoster extends Widget {
    var $plugin;
    var $request;
    function IM_Widget_MyRoster($plugin) {
        $this->Widget('myroster');
        $this->plugin = $plugin;
        $this->request =& HTTPRequest::instance();
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
        foreach ($projects_id_user as $project_id) {
            $project = project_get_object($project_id);
            $project_unix_name=$project->getUnixName();
            $project_public_name=$project->getPublicName();
            $members_id_array=$project->getMembersUserNames();
            if(sizeof($members_id_array)>1){
                list($hide_now,$count_diff,$hide_url) = my_hide_url('im_group',$project_id,$request->get('hide_item_id'),count($members_id_array),$request->get('hide_im_group'));
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
    
    function isAvailable() {
        return ! UserManager::instance()->getCurrentUser()->getPreference('plugin_im_hide_users_presence');
    }
    
    function getPreviewCssClass() {
        $locale = UserManager::instance()->getCurrentUser()->getLocale();
        if ($locale == 'fr_FR') {
            return 'widget-preview-myroster-fr-FR';
        }
        return 'widget-preview-myroster-en-US';
    }
}

?>
