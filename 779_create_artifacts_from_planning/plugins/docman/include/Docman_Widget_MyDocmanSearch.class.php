<?php
/**
 * Copyright (c) STMicroelectronics, 2004-2009. All rights reserved
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

require_once('common/widget/Widget.class.php');
require_once('common/user/UserManager.class.php');
require_once('Docman_ApprovalTableFactory.class.php');
require_once('Docman_PermissionsManager.class.php');
require_once('Docman_ItemFactory.class.php');

/**
* Docman_Widget_MyDocmanSearch
*/
class Docman_Widget_MyDocmanSearch extends Widget {
    
    var $pluginPath;

    function __construct($pluginPath) {
        parent::__construct('plugin_docman_mydocman_search');
        $this->_pluginPath = $pluginPath;
    }
    
    function getTitle() {
        return $GLOBALS['Language']->getText('plugin_docman', 'my_docman_search');
    }
    
    function getContent() {
        $html = '';
        $request = HTTPRequest::instance();
        $um = UserManager::instance();
        $user =$um->getCurrentUser();
        
        $vFunc = new Valid_WhiteList('docman_func', array('show_docman'));
        $vFunc->required();
        if ($request->valid($vFunc)) {
            $func = $request->get('docman_func');
        } else {
            $func = '';
        }
        $vDocmanId = new Valid_UInt('docman_id');
        $vDocmanId->required();
        if ($request->valid($vDocmanId)) {
            $docman_id = $request->get('docman_id');
        } else {
            $docman_id = '';
        }
        
        $html .= '<form method="post" action="?">';
        $html .= '<label>'.$GLOBALS['Language']->getText('plugin_docman', 'widget_my_docman_search_label').'</label>';
        $html .= '<input type="hidden" name="docman_func" value="show_docman" />';
        $html .= '<input type="text" name="docman_id" value="'.$docman_id.'" id="docman_id" />';
        $html .= '&nbsp;';
        $html .= '<input type="submit" value="'.$GLOBALS['Language']->getText('plugin_docman', 'widget_my_docman_search_btn').'"/>';
        $html .= '</form>';
        
        if (($func == 'show_docman') && $docman_id){
            $res = $this->returnAllowedGroupId($docman_id, $user);

            if ($res){
                $dPm = Docman_PermissionsManager::instance($res['group_id']);
                $itemPerm = $dPm->userCanAccess($user, $docman_id);

                if ($itemPerm){
                    $html .= '<p><a href="/plugins/docman/?group_id='.$res['group_id'].'&action=details&id='.$docman_id.'&section=properties">Show &quot;'.$res['title'].'&quot; Properties</a></p>';
                    return $html;
                }
            
            }
            $html .= '<p>'.$GLOBALS['Language']->getText('plugin_docman','perm_denied').'</p>';
             
        }
        
        return $html;
    }

    /**
     * Check if given document is in a project readable by user.
     * 
     * Returns project info if:
     * * the document belongs to a public project
     * ** And the user is active (not restricted)
     * ** Or user is restricted but member of the project. 
     * * or a private one and the user is a member of it
     * else 0
     * 
     * @param $docman_id int  Document Id
     * @param $user      User User Id
     * @return group_id 
     **/
    function returnAllowedGroupId($docman_id, $user){
        $sql_group = 'SELECT group_id,title FROM  plugin_docman_item WHERE'.
                         ' item_id = '. db_ei($docman_id);

        $res_group = db_query($sql_group);

        if ($res_group && db_numrows($res_group)== 1){
            $row = db_fetch_array($res_group);
            $res['group_id'] = $row['group_id'];
            $res['title'] = $row['title'];

            $project = ProjectManager::instance()->getProject($res['group_id']);
            if ($project->isPublic()){
                // Check restricted user
                if (($user->isRestricted() && $user->isMember($res['group_id'])) || !$user->isRestricted()) {
                    return $res;
                }
            } else {
                if ($user->isMember($res['group_id'])) {
                    return $res;
                }
            }
        }
        return 0;
    }

    function getCategory() {
        return 'plugin_docman';
    }
    
    function getDescription() {
        return $GLOBALS['Language']->getText('plugin_docman','widget_description_my_docman_search');
    }
}

?>
