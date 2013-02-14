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

class Project_Admin_UGroup_View_EditDirectoryGroup extends Project_Admin_UGroup_View_Binding {

    private $row;
    private $ldap_user_group_manager;
    private $pluginPath;
    private $bindOption;
    private $synchro;

    public function __construct(UGroup $ugroup, UGroupBinding $ugroup_binding, $row, $ldapUserGroupManager, $pluginPath, $bindOption, $synchro) {
        parent::__construct($ugroup, $ugroup_binding);
        $this->row = $row;
        $this->ldap_user_group_manager = $ldapUserGroupManager;
        $this->pluginPath = $pluginPath;
        $this->purifier = Codendi_HTMLPurifier::instance();
        $this->bindOption = $bindOption;
        $this->synchro = $synchro;
    }

    public function getContent() {
        $content = '';

        // Import very long user group may takes very long time.
        ini_set('max_execution_time', 0);

        $ugroupName = util_translate_name_ugroup($this->row['name']);
        $ldapGroup = $this->ldap_user_group_manager->getLdapGroupByGroupId($this->ugroup->getId());
        $clean_ugroupName = $this->purifier->purify($ugroupName);
        $clean_ldapGroupName = $this->purifyLDAPGroupName($ldapGroup);

        $btn_update = $GLOBALS['Language']->getText('plugin_ldap', 'ugroup_edit_btn_update');
        $btn_unlink = $GLOBALS['Language']->getText('plugin_ldap', 'ugroup_edit_btn_unlink');

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
        $content .= '<input type="hidden" name="ugroup_id" value="'.$this->ugroup->getId().'" />';
        $content .= '<input type="hidden" name="action" value="edit_directory" />';


        $content .= '<p>'.$GLOBALS['Language']->getText('plugin_ldap', 'ugroup_edit_group_bind_with_group').' <input type="text" name="bind_with_group" id="group_add" value="'.$clean_ldapGroupName.'"  size="60" /></p>';

        $preservingChecked = '';
        if ($this->ldap_user_group_manager->isMembersPreserving($this->ugroup->getId()) || $this->bindOption === LDAP_GroupManager::PRESERVE_MEMBERS_OPTION) {
            $preservingChecked = 'checked';
        }
        $content .= '<p><input type="checkbox" id="preserve_members" name="preserve_members" '.$preservingChecked.'/>';

        $content .= '<label for="preserve_members">'.$GLOBALS['Language']->getText('plugin_ldap', 'ugroup_edit_group_preserve_members_option').' ('.$GLOBALS['Language']->getText('plugin_ldap', 'ugroup_edit_group_preserve_members_info').')</label></p>';

        $synchroChecked = '';
        if ($this->ldap_user_group_manager->isSynchronizedUgroup($this->ugroup->getId()) || $this->synchro === LDAP_GroupManager::AUTO_SYNCHRONIZATION) {
            $synchroChecked = 'checked';
        }
        $content .= '<p><input type="checkbox" id="synchronize" name="synchronize" '.$synchroChecked.'/>';
        $content .= '<label for="synchronize">'.$GLOBALS['Language']->getText('plugin_ldap', 'ugroup_edit_group_synchronize_option').' ('.$GLOBALS['Language']->getText('plugin_ldap', 'ugroup_edit_group_synchronize_info').')</label></p>';

        $content .= '<input type="submit" name="submit" value="'.$btn_update.'" />';
        if($ldapGroup !== null) {
            $content .= '&nbsp;&nbsp;';
            $content .= '<input type="submit" name="submit" value="'.$btn_unlink.'" />';
        }
        $GLOBALS['Response']->includeFooterJavascriptFile($this->pluginPath.'/scripts/autocomplete.js');
        $js = "new LdapGroupAutoCompleter('group_add',
            '".$this->pluginPath."',
            '".util_get_dir_image_theme()."',
            'group_add',
            false);";
        $GLOBALS['Response']->includeFooterJavascriptSnippet($js);

        $content .= '</form>';

        return $content;

    }

    private function purifyLDAPGroupName($ldapGroup) {
        if($ldapGroup !== null) {
            $clean_ldapGroupName = $this->purifier->purify($ldapGroup->getCommonName());
        } else {
            $clean_ldapGroupName = '';
        }
        return $clean_ldapGroupName;
    }
}

?>
