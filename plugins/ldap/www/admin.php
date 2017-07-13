<?php
/**
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 * Copyright (c) Enalean, 2017. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2008
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'pre.php';
require_once dirname(__FILE__).'/../include/LDAP_ProjectGroupManager.class.php';
require_once 'www/project/admin/project_admin_utils.php';

// Import very long user group may takes very long time.
ini_set('max_execution_time', 0);

$request = HTTPRequest::instance();

// Get group id
$vGroupId = new Valid_GroupId();
$vGroupId->required();
if(!$request->valid($vGroupId)) {
    exit_error($Language->getText('global','error'), 'No group_id');
}
$groupId = $request->get('group_id');

// Must be a project admin
session_require(array('group' => $groupId, 'admin_flags' => 'A'));

// Ensure LDAP plugin is active
$pluginManager = PluginManager::instance();
$ldapPlugin    = $pluginManager->getPluginByName('ldap');
if (!$ldapPlugin || !$pluginManager->isPluginAvailable($ldapPlugin)) {
    $GLOBALS['Response']->redirect('/project/admin/index.php?group_id='.$groupId);
}

// Check if user have choosen the preserve members option.
$bindOption = LDAP_GroupManager::BIND_OPTION;
if($request->exist('preserve_members') && $request->get('preserve_members') == 'on') {
    $bindOption = LDAP_GroupManager::PRESERVE_MEMBERS_OPTION;
}

// Check if user has checked the Synchronization option.
$synchro = LDAP_GroupManager::NO_SYNCHRONIZATION;
if($request->exist('synchronize') && $request->get('synchronize') == 'on') {
    $synchro = LDAP_GroupManager::AUTO_SYNCHRONIZATION;
}

// Get LDAP group name
$vLdapGroup = new Valid_String('ldap_group');
$vLdapGroup->required();
if($request->isPost() && $request->valid($vLdapGroup)) {
    $ldapGroupManager = $ldapPlugin->getLdapProjectGroupManager();

    $ldapGroupManager->setId($groupId);
    $ldapGroupManager->setGroupName($request->get('ldap_group'));

    if($request->existAndNonEmpty('delete')) {
        //
        // Remove link between Project Members and LDAP Group
        //
        $ldapGroupManager->unbindFromBindLdap();
        $GLOBALS['Response']->redirect('/project/admin/index.php?group_id='.$groupId);

    } elseif($request->existAndNonEmpty('update')) {
        //
        // Perform Project Members <-> LDAP Group synchro
        //
        $ldapGroupManager->bindWithLdap($bindOption, $synchro);
        $GLOBALS['Response']->redirect('/project/admin/index.php?group_id='.$groupId);

    } elseif($request->exist('cancel')) {
        //
        // Cancel operations
        //        
        $GLOBALS['Response']->redirect('/project/admin/index.php?group_id='.$groupId);

    } else {
        //
        // Display to user what will be done with project members.
        //
        $toRemove    = $ldapGroupManager->getUsersToBeRemoved($bindOption);
        $toAdd       = $ldapGroupManager->getUsersToBeAdded($bindOption);
        $notImpacted = $ldapGroupManager->getUsersNotImpacted($bindOption);

        if(is_array($toAdd)) {
            // Display
            $um = UserManager::instance();
            $hp = Codendi_HTMLPurifier::instance();
            
            project_admin_header(array('title' => $GLOBALS['Language']->getText('plugin_ldap','project_members_synchro_title'), 'group' => $groupId));
            echo '<h1>'.$GLOBALS['Language']->getText('plugin_ldap','project_members_synchro_title').'</h1>';

            echo '<p>'.$GLOBALS['Language']->getText('plugin_ldap', 'project_members_synchro_warning').'</p>';
            echo '<p>'.$GLOBALS['Language']->getText('plugin_ldap', 'group_members_synchro_sumup', array(count($toRemove), count($toAdd), count($notImpacted))).'</p>';

            echo '<table width="100%">';
            echo '<tr><td width="50%" valign="top">';

            $GLOBALS['HTML']->box1_top($GLOBALS['Language']->getText('plugin_ldap', 'group_members_synchro_to_remove', array(count($toRemove))));
            echo '<ul>';
            foreach ($toRemove as $userId) {
                if (($user = $um->getUserById($userId))) {
                    echo '<li>' . $hp->purify($user->getRealName().' ('.$user->getUserName().')') . '</li>';
                }
            }
            echo '</ul>';
            $GLOBALS['HTML']->box1_bottom();

            echo '</td><td width="50%"  valign="top">';

            $GLOBALS['HTML']->box1_top($GLOBALS['Language']->getText('plugin_ldap', 'group_members_synchro_to_add', array(count($toAdd))));
            echo '<ul>';
            foreach ($toAdd as $userId) {
                if (($user = $um->getUserById($userId))) {
                    echo '<li>'. $hp->purify($user->getRealName().' ('.$user->getUserName().')') . '</li>';
                }
            }
            echo '</ul>';
            $GLOBALS['HTML']->box1_bottom();

            echo '</tr></td>';
            echo '<tr><td colspan="2" align="center">';
            echo '<form method="post" action="?group_id='.$groupId.'">';
            echo '<input type="hidden" name="ldap_group" value="'.$hp->purify($request->get('ldap_group')).'" />';
            echo '<input type="hidden" name="confirm" value="yes" />';

            if ($bindOption == 'preserve_members') {
                echo '<input type="hidden" name="preserve_members" value="on" />';
            }

            if ($synchro === LDAP_GroupManager::AUTO_SYNCHRONIZATION) {
                echo '<input type="hidden" name="synchronize" value="on" />';
            }

            echo '<input type="submit" name="cancel" value="'.$GLOBALS['Language']->getText('global', 'btn_cancel').'" />';
            echo '<input type="submit" name="update" value="'.$GLOBALS['Language']->getText('global', 'btn_update').'" />';
            echo '</form>';
            echo '</td></tr>';
            echo '</table>';

            project_admin_footer(array());
        } else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_ldap', 'invalid_ldap_group_name'));
            $GLOBALS['Response']->redirect('/project/admin/index.php?group_id='.$groupId);
        }
    }
} else {
    $GLOBALS['Response']->redirect('/project/admin/index.php?group_id='.$groupId);
}
