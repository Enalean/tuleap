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

require_once dirname(__FILE__).'/../View/UGroupAction.class.php';
require_once dirname(__FILE__).'/../View/EditDirectoryGroup.class.php';

class LDAP_Ugroup_Controller_Binding extends Project_Admin_UGroup_UGroupController {

    private $bindOption;
    private $synchro;
    private $ldap_user_group_manager;
    private $plugin_path;

    public function __construct(Codendi_Request $request, UGroup $ugroup, Project_Admin_UGroup_PaneInfo $pane, $ldapUserGroupManager , $pluginPath) {
        parent::__construct($request, $ugroup);
        $this->synchro    = null;
        $this->bindOption = null;
        $this->pane       = $pane;
        $this->ldap_user_group_manager = $ldapUserGroupManager;
        $this->plugin_path = $pluginPath;

    }

    public function edit_directory_group() {
        $ugroupId   = $this->getUGroupIdInRequest($this->request);

        if (! $ugroupId) {
            exit_error(
                $GLOBALS['Language']->getText('global','error'),
                $GLOBALS['Language']->getText('project_admin_editugroup','ug_not_found')
            );
        }

        $ugroup_row  = $this->getUGroupRow($ugroupId);

        if (! $ugroup_row) {
            exit_error($GLOBALS['Language']->getText('global','error'), "Cannot modify this ugroup with LDAP plugin");
        }

        $this->setldapUserGroupManager($ugroupId);
        $view = new LDAP_UGroup_View_EditDirectoryGroup($this->ugroup, $this->ugroup_binding, $ugroup_row, $this->ldap_user_group_manager, $this->plugin_path, $this->bindOption,  $this->synchro);
        $this->render($view);
    }

    private function setldapUserGroupManager($ugroupId) {
        $this->ldap_user_group_manager->setGroupName($this->request->get('bind_with_group'));
        $this->ldap_user_group_manager->setId($ugroupId);
    }

    private function getUGroupIdInRequest($request) {
        $vUgroupId = new Valid_UInt('ugroup_id');
        $vUgroupId->required();
        if($request->valid($vUgroupId)) {
            $ugroupId = $request->get('ugroup_id');
        } else {
            $ugroupId = null;
        }
        return $ugroupId;
    }

    private function getUGroupRow($ugroupId) {
        $res = ugroup_db_get_ugroup($ugroupId);
        if($res && !db_error($res) && db_numrows($res) == 1) {
            $row = db_fetch_array($res);
            session_require(array('group'=>$row['group_id'],'admin_flags'=>'A'));
            if($row['group_id'] == 100) {
                $row = null;
            }
        } else {
            exit_error($GLOBALS['Language']->getText('global','error'),$GLOBALS['Language']->getText('project_admin_editugroup','ug_not_found',array($ugroupId,db_error())));
        }
        return $row;
    }

    public function edit_directory() {
        $this->setldapUserGroupManager($this->ugroup->getId());

        $btn_update = $GLOBALS['Language']->getText('plugin_ldap', 'ugroup_edit_btn_update');
        $btn_unlink = $GLOBALS['Language']->getText('plugin_ldap', 'ugroup_edit_btn_unlink');

        $vSubmit = new Valid_WhiteList('submit', array($btn_update, $btn_unlink));
        $vSubmit->required();

        if($this->request->isPost() && $this->request->valid($vSubmit)) {
            if($this->request->get('submit') == $btn_unlink) {
                $this->unlinkLDAPGroup($this->ldap_user_group_manager);
            } else {
                $this->linkLDAPGroup($this->ldap_user_group_manager);
            }
        } else {
            $this->edit_directory_group();
        }
    }

    private function unlinkLDAPGroup($ldapUserGroupManager) {
        if($ldapUserGroupManager->unbindFromBindLdap()) {
            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_ldap', 'ugroup_manager_unlink'));
            $this->redirect();
        }
    }

    private function linkLDAPGroup($ldapUserGroupManager) {
        $vBindWithGroup = new Valid_String('bind_with_group');
        $vBindWithGroup->required();

        $this->bindOption = $this->getBindOption();
        $this->synchro    = $this->getSynchro();

        if($this->request->valid($vBindWithGroup)) {

            if($this->request->existAndNonEmpty('confirm')) {
                //
                // Perform Ugroup <-> LDAP Group synchro
                //
                $ldapUserGroupManager->bindWithLdap($this->bindOption, $this->synchro);
                $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('project_ugroup_binding', 'link_ldap_group', array($this->request->get('bind_with_group'))));
                $this->redirect();

            } elseif($this->request->exist('cancel')) {
                // Display the screen below!
                continue;

            } else {
                if ($ldapUserGroupManager->getGroupDn()) {
                    $view = new LDAP_UGroup_View_UGroupAction($this->ugroup, $this->ugroup_binding, $ldapUserGroupManager, $this->request, $this->bindOption, $this->synchro);
                    $this->render($view);
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('project_ugroup_binding', 'ldap_group_error', array($this->request->get('bind_with_group'))));
                    $this->edit_directory_group($this->bindOption, $this->synchro);
                }
            }
        } else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('project_ugroup_binding', 'ldap_group_empty'));
            $this->edit_directory_group();
        }
    }

    private function getSynchro() {
        $synchro = LDAP_GroupManager::NO_SYNCHRONIZATION;
        if ($this->request->existAndNonEmpty('synchronize')) {
            $synchro = LDAP_GroupManager::AUTO_SYNCHRONIZATION;
        }
        return $synchro;
    }

    private function getBindOption() {
        $bindOption = LDAP_GroupManager::BIND_OPTION;
        if($this->request->exist('preserve_members') && $this->request->get('preserve_members') == 'on') {
            $bindOption = LDAP_GroupManager::PRESERVE_MEMBERS_OPTION;
        }
        return $bindOption;
    }
}

?>
