<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
require_once 'Binding.class.php';
require_once('pre.php');
require_once('www/project/admin/ugroup_utils.php');
require_once('www/project/admin/project_admin_utils.php');
require_once('common/plugin/PluginManager.class.php');
require_once('../../../../plugins/ldap/include/LDAP_UserGroupManager.class.php');

class Project_Admin_UGroup_View_EditDirectoryGroup extends Project_Admin_UGroup_View_Binding {

    public function __construct(UGroup $ugroup, UGroupBinding $ugroup_binding) {
        parent::__construct($ugroup, $ugroup_binding);
    }

    public function getContent(){

    $content = '';

    // Import very long user group may takes very long time.
    ini_set('max_execution_time', 0);

    //
    // Verify common requirement
    //
    $pluginManager = PluginManager::instance();
    $ldapPlugin = $pluginManager->getPluginByName('ldap');
    $request = HTTPRequest::instance();
    $pluginPath = $this->verifyLDAPAvailable($pluginManager,$ldapPlugin);
    $ugroupId   = $this->verifyUGroupExists($request);
    $row        = $this->getRow($ugroupId);
    $group_id    = $row['group_id'];

    $vFunc = new Valid_String('func', array('bind_with_group'));
    $vFunc->required();
    if(!$request->valid($vFunc)) {
        $GLOBALS['Response']->redirect('/project/admin/ugroup.php?group_id='.$group_id);
    }

    $ldapUserGroupManager = new LDAP_UserGroupManager($ldapPlugin->getLdap());
    $ldapUserGroupManager->setGroupName($request->get('bind_with_group'));
    $ldapUserGroupManager->setId($ugroupId);

    $hp = Codendi_HTMLPurifier::instance();

    $btn_update = $GLOBALS['Language']->getText('plugin_ldap', 'ugroup_edit_btn_update');
    $btn_unlink = $GLOBALS['Language']->getText('plugin_ldap', 'ugroup_edit_btn_unlink');

    //
    // Display
    //

    $ugroupRow  = ugroup_db_get_ugroup($ugroupId) ;
    $ugroupName = util_translate_name_ugroup($row['name']);
    $ldapGroup = $ldapUserGroupManager->getLdapGroupByGroupId($ugroupId);

    $clean_ugroupName = $hp->purify($ugroupName);
    if($ldapGroup !== null) {
        $clean_ldapGroupName = $hp->purify($ldapGroup->getCommonName());
    } else {
        $clean_ldapGroupName = '';
    }

    $content .= '
    <script type="text/javascript" src="/scripts/prototype/prototype.js"></script>
    <script type="text/javascript" src="/scripts/scriptaculous/scriptaculous.js"></script>
    <script type="text/javascript" src="/scripts/autocomplete.js"></script>
    <script type="text/javascript" src="'.$pluginPath.'/scripts/autocomplete.js"></script>
    ';

    $content .= '<h2>'.$GLOBALS['Language']->getText('project_admin_editugroup','ug_admin', $clean_ugroupName).'</h2>';

    if($ldapGroup !== null) {
            $content .= '<p>'.$GLOBALS['Language']->getText('plugin_ldap', 'ugroup_edit_ugroup_linked', array($clean_ugroupName, $clean_ldapGroupName)).'</p>';
    }

    $content .= '<p>'.$GLOBALS['Language']->getText('plugin_ldap', 'ugroup_edit_group_you_can').'</p>';
    $content .= '<ul>';

    if($ldapGroup !== null) {
    $content .= '<li>'.$GLOBALS['Language']->getText('plugin_ldap', 'ugroup_edit_group_resync').'</li>';
    $content .= '<li>'.$GLOBALS['Language']->getText('plugin_ldap', 'ugroup_edit_group_remove').'</li>';
    }
    $content .= '<li>'.$GLOBALS['Language']->getText('plugin_ldap', 'ugroup_edit_group_link').'</li>';
    $content .= '</ul>';
    $content .= '<p>'.$GLOBALS['Language']->getText('plugin_ldap', 'ugroup_edit_group_synchro').'</p>';
    $content .= '<ul>';
    $content .= '<li>'.$GLOBALS['Language']->getText('plugin_ldap', 'ugroup_edit_group_synchro_del', $GLOBALS['sys_name']).'</li>';
    $content .= '<li>'.$GLOBALS['Language']->getText('plugin_ldap', 'ugroup_edit_group_synchro_add', $GLOBALS['sys_name']).'</li>';
    $content .= '</ul>';

    $content .= '<form name="plugin_ldap_edit_ugroup" method="post" action="">';
    $content .= '<input type="hidden" name="ugroup_id" value="'.$ugroupId.'" />';
    $func = 'bind_with_group';
    //$content .= '<input type="hidden" name="func" value="'.$func.'" />';
    $content .= '<input type="hidden" name="action" value="edit_directory" />';


    $content .= '<p>'.$GLOBALS['Language']->getText('plugin_ldap', 'ugroup_edit_group_bind_with_group').' <input type="text" name="bind_with_group" id="group_add" value="'.$clean_ldapGroupName.'"  size="60" /></p>';

    $preservingChecked = '';
    if ($ldapUserGroupManager->isMembersPreserving($ugroupId)) {
        $preservingChecked = 'checked';
    }
    $content .= '<p><input type="checkbox" id="preserve_members" name="preserve_members" '.$preservingChecked.'/>';

    $content .= '<label for="preserve_members">'.$GLOBALS['Language']->getText('plugin_ldap', 'ugroup_edit_group_preserve_members_option').' ('.$GLOBALS['Language']->getText('plugin_ldap', 'ugroup_edit_group_preserve_members_info').')</label></p>';

    $synchroChecked = '';
    if ($ldapUserGroupManager->isSynchronizedUgroup($ugroupId)) {
        $synchroChecked = 'checked';
    }
    $content .= '<p><input type="checkbox" id="synchronize" name="synchronize" '.$synchroChecked.'/>';
    $content .= '<label for="synchronize">'.$GLOBALS['Language']->getText('plugin_ldap', 'ugroup_edit_group_synchronize_option').' ('.$GLOBALS['Language']->getText('plugin_ldap', 'ugroup_edit_group_synchronize_info').')</label></p>';

    $content .= '<input type="submit" name="submit" value="'.$btn_update.'" />';
    if($ldapGroup !== null) {
        $content .= '&nbsp;&nbsp;';
        $content .= '<input type="submit" name="submit" value="'.$btn_unlink.'" />';
    }
    $GLOBALS['Response']->includeFooterJavascriptFile($pluginPath.'/scripts/autocomplete.js');
    $js = "new LdapGroupAutoCompleter('group_add',
        '".$pluginPath."',
        '".util_get_dir_image_theme()."',
        'group_add',
        false);";
    $GLOBALS['Response']->includeFooterJavascriptSnippet($js);

    $content .= '</form>';

    return $content;
    
    }

    private function verifyLDAPAvailable($pluginManager, $ldapPlugin) {
        if ($ldapPlugin && $pluginManager->isPluginAvailable($ldapPlugin)) {
            $pluginPath = $ldapPlugin->getPluginPath();
        } else {
            exit_error($GLOBALS['Language']->getText('global','error'), 'No ldap plugin');
        }
        return $pluginPath;
    }

    private function verifyUGroupExists($request) {
        $vUgroupId = new Valid_UInt('ugroup_id');
        $vUgroupId->required();
        if($request->valid($vUgroupId)) {
            $ugroupId = $request->get('ugroup_id');
        } else {
            exit_error($GLOBALS['Language']->getText('global','error'),$GLOBALS['Language']->getText('project_admin_editugroup','ug_not_found'));
        }
        return $ugroupId;
    }

    private function getRow($ugroupId) {
        $res = ugroup_db_get_ugroup($ugroupId);
        if($res && !db_error($res) && db_numrows($res) == 1) {
            $row = db_fetch_array($res);
            session_require(array('group'=>$row['group_id'],'admin_flags'=>'A'));
            if($row['group_id'] == 100) {
                 exit_error($GLOBALS['Language']->getText('global','error'), "Cannot modify this ugroup with LDAP plugin");
            }
        } else {
            exit_error($GLOBALS['Language']->getText('global','error'),$GLOBALS['Language']->getText('project_admin_editugroup','ug_not_found',array($ugroupId,db_error())));
        }
        return $row;
    }
}

?>
