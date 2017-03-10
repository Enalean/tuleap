<?php
/**
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Sabri LABBENE, 2009
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
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
require_once('pre.php');
require_once('common/plugin/PluginManager.class.php');
require_once(dirname(__FILE__).'/../include/LDAP_UserGroupManager.class.php');
require_once('www/project/admin/ugroup_utils.php');
require_once('www/project/admin/project_admin_utils.php');

//
// Verify common requirement
//

// LDAP plugin enabled
$pluginManager = PluginManager::instance();
$ldapPlugin = $pluginManager->getPluginByName('ldap');
if ($ldapPlugin && $plugin_manager->isPluginAvailable($ldapPlugin)) {
    $pluginPath = $ldapPlugin->getPluginPath();
} else {
    exit_error($Language->getText('global','error'), 'No ldap plugin');
}

// User group id exists
$vUgroupId = new Valid_UInt('ugroup_id');
$vUgroupId->required();
if($request->valid($vUgroupId)) {
    $ugroupId = $request->get('ugroup_id');
} else {
    exit_error($Language->getText('global','error'),$Language->getText('project_admin_editugroup','ug_not_found'));
}

// Do not try to modify ugroups of project 100
$res = ugroup_db_get_ugroup($ugroupId);
if($res && !db_error($res) && db_numrows($res) == 1) {
    $row = db_fetch_array($res);
    session_require(array('group'=>$row['group_id'],'admin_flags'=>'A'));
    if($row['group_id'] == 100) {
         exit_error($Language->getText('global','error'), "Cannot modify this ugroup with LDAP plugin");
    }
} else {
    exit_error($Language->getText('global','error'),$Language->getText('project_admin_editugroup','ug_not_found',array($ugroupId,db_error())));
}
$group_id = $row['group_id'];

$ugroupUpdateUsersAllowed = true;
$em->processEvent(Event::UGROUP_UPDATE_USERS_ALLOWED, array('ugroup_id' => $ugroup_id, 'allowed' => &$ugroupUpdateUsersAllowed));
if ($ugroupUpdateUsersAllowed) {
    $ldap_user_manager    = $ldapPlugin->getLdapUserManager();
    $ldapUserGroupManager = new LDAP_UserGroupManager(
        $ldapPlugin->getLdap(),
        $ldap_user_manager,
        ProjectManager::instance(),
        $ldapPlugin->getLogger()
    );

    $ldapUserGroupManager->setId($ugroupId);
    $ldapUserGroupManager->setProjectId($group_id);

    $hp = Codendi_HTMLPurifier::instance();

    $btn_update = $Language->getText('plugin_ldap', 'ugroup_edit_btn_update');
    $vSubmit = new Valid_WhiteList('submit', array($btn_update));
    $vSubmit->required();
    if($request->isPost() && $request->valid($vSubmit)) {
        if($request->get('submit') == $btn_update) {
                $vUserAdd = new Valid_String('user_add');
                $vUserAdd->required();
                if($request->valid($vUserAdd)) {
                    $ldapUserGroupManager->addListOfUsersToGroup($request->get('user_add'));
                }
        }
    }

    //
    // Display
    //

    $ugroupRow  = ugroup_db_get_ugroup($ugroupId) ;
    $ugroupName = util_translate_name_ugroup($row['name']);
    $clean_ugroupName = $hp->purify($ugroupName);

    project_admin_header(array('title'=>$Language->getText('project_admin_editugroup','edit_ug'),'group'=>$group_id));

    echo '<h2>'.$Language->getText('project_admin_editugroup','ug_admin', $clean_ugroupName).'</h2>';

    echo '<p>'.$GLOBALS['Language']->getText('plugin_ldap', 'ugroup_edit_group_add_users_help').'</p>';

    echo '<form name="plugin_ldap_edit_ugroup" method="post" action="">';
    echo '<input type="hidden" name="ugroup_id" value="'.$ugroupId.'" />';
    echo '<input type="hidden" name="func" value="add_user" />';

    echo '<p>'.$GLOBALS['Language']->getText('plugin_ldap', 'ugroup_edit_group_add_users').' <textarea name="user_add" id="user_add" rows="2" cols="60" wrap="soft"/></textarea></p>';
    echo '<input type="submit" name="submit" value="'.$btn_update.'" />';

    // JS code for autocompletion on "add_user" field defined on top.
    $js = "new UserAutoCompleter('user_add',
                            '".util_get_dir_image_theme()."',
                            true);";
    $GLOBALS['Response']->includeFooterJavascriptSnippet($js);
    echo '</form>';

    project_admin_footer(array());
} else {
    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global', 'operation_not_allowed'));
    $GLOBALS['Response']->redirect('/project/admin/ugroup.php?group_id='. $group_id);
}

?>