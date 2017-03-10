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

class Project_Admin_UGroup_UGroupController_Binding extends Project_Admin_UGroup_UGroupController {

    private $bindOption;
    private $synchro;

    public function __construct(Codendi_Request $request, ProjectUGroup $ugroup, Project_Admin_UGroup_PaneInfo $pane) {
        parent::__construct($request, $ugroup);
        $this->synchro    = null;
        $this->bindOption = null;
        $this->pane       = $pane;
    }

    public function edit_binding() {
        $source_project_id = $this->request->getValidated('source_project', 'GroupId', 0);
        $view = new Project_Admin_UGroup_View_EditBinding($this->ugroup, $this->ugroup_binding, $source_project_id);
        $this->render($view);
    }

    public function edit_directory_group() {
        $pluginManager = PluginManager::instance();
        $ldapPlugin = $pluginManager->getPluginByName('ldap');
        $pluginPath = $this->getLDAPPath($pluginManager,$ldapPlugin);

        if (! $pluginPath) {
            exit_error($GLOBALS['Language']->getText('global','error'), 'No ldap plugin');
        }

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

        $ldapUserGroupManager = $this->setldapUserGroupManager($ldapPlugin, $this->ugroup);
        $view = new Project_Admin_UGroup_View_EditDirectoryGroup($this->ugroup, $this->ugroup_binding, $ugroup_row, $ldapUserGroupManager, $pluginPath, $this->bindOption,  $this->synchro);
        $this->render($view);
    }

    private function setldapUserGroupManager(Plugin $ldapPlugin, ProjectUGroup $ugroup) {
        $ldapUserGroupManager = new LDAP_UserGroupManager($ldapPlugin->getLdap(), ProjectManager::instance(), $ldapPlugin->getLogger());
        $ldapUserGroupManager->setGroupName($this->request->get('bind_with_group'));
        $ldapUserGroupManager->setId($ugroup->getId());
        $ldapUserGroupManager->setProjectId($ugroup->getProjectId());

        return $ldapUserGroupManager;
    }

    private function getLDAPPath($pluginManager, $ldapPlugin) {
        if ($ldapPlugin && $pluginManager->isPluginAvailable($ldapPlugin)) {
            $pluginPath = $ldapPlugin->getPluginPath();
        } else {
            $pluginPath = null;
        }
        return $pluginPath;
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

    /**
     * Display the binding pane content
     *
     * @return String
     */
    public function displayUgroupBinding() {
        $html = '';
        $ugroupUpdateUsersAllowed = !$this->ugroup->isBound();
        if ($ugroupUpdateUsersAllowed) {
            $em = EventManager::instance();
            $em->processEvent('ugroup_table_row', array('row' => array('group_id' => $this->ugroup->getProjectId(), 'ugroup_id' => $this->ugroup->getId()), 'html' => &$html));
        }
        return $html;
    }

    public function add_binding() {
        $historyDao        = new ProjectHistoryDao();
        $projectSourceId   = $this->request->getValidated('source_project', 'GroupId');
        $sourceId          = $this->request->get('source_ugroup');
        $validSourceUgroup = $this->ugroup_manager->checkUGroupValidityByGroupId($projectSourceId, $sourceId);
        $projectSource     = ProjectManager::instance()->getProject($projectSourceId);
        if ($validSourceUgroup && $projectSource->userIsAdmin()) {
            if ($this->ugroup_binding->addBinding($this->ugroup->getId(), $sourceId)) {
                $historyDao->groupAddHistory("ugroup_add_binding", $this->ugroup->getId().":".$sourceId, $this->ugroup->getProjectId());
                $this->launchEditBindingUgroupEvent();
            }
        } else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('project_ugroup_binding', 'add_error'));
        }
        $this->redirect();
    }

    public function remove_binding() {
        $historyDao        = new ProjectHistoryDao();
        if ($this->ugroup_binding->removeBinding($this->ugroup->getId())) {
            $historyDao->groupAddHistory("ugroup_remove_binding", $this->ugroup->getId(), $this->ugroup->getProjectId());
            $this->launchEditBindingUgroupEvent();
        }
        $this->redirect();
    }

    public function edit_directory() {
        $ldapPlugin = $this->getLdapPlugin();

        $ldapUserGroupManager = $this->setldapUserGroupManager($ldapPlugin, $this->ugroup);

        $btn_update = $GLOBALS['Language']->getText('plugin_ldap', 'ugroup_edit_btn_update');
        $btn_unlink = $GLOBALS['Language']->getText('plugin_ldap', 'ugroup_edit_btn_unlink');

        $vSubmit = new Valid_WhiteList('submit', array($btn_update, $btn_unlink));
        $vSubmit->required();

        if($this->request->isPost() && $this->request->valid($vSubmit)) {
            if($this->request->get('submit') == $btn_unlink) {
                $this->unlinkLDAPGroup($ldapUserGroupManager);
            } else {
                $this->linkLDAPGroup($ldapUserGroupManager);
            }
        } else {
            $this->edit_directory_group();
        }
    }

    private function unlinkLDAPGroup($ldapUserGroupManager) {
        if($ldapUserGroupManager->unbindFromBindLdap()) {
            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_ldap', 'ugroup_manager_unlink'));
            $this->launchEditBindingUgroupEvent();
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
                // Perform ProjectUGroup <-> LDAP Group synchro
                //
                $ldapUserGroupManager->bindWithLdap($this->bindOption, $this->synchro);
                $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('project_ugroup_binding', 'link_ldap_group', array($this->request->get('bind_with_group'))));
                $this->launchEditBindingUgroupEvent();
                $this->redirect();

            } elseif ($this->request->exist('cancel')) {
                // Display the screen below!
            } else {
                if ($ldapUserGroupManager->getGroupDn()) {
                    $view = new Project_Admin_UGroup_View_UGroupAction($this->ugroup, $this->ugroup_binding, $ldapUserGroupManager, $this->request, $this->bindOption, $this->synchro);
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


    protected function getLdapPlugin() {
        return  PluginManager::instance()->getPluginByName('ldap');

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

    private function launchEditBindingUgroupEvent() {
        $event_manager = EventManager::instance();
        $event_manager->processEvent('project_admin_ugroup_bind_modified',
            array(
                'group_id'  => $this->ugroup->getProjectId(),
                'ugroup_id' => $this->ugroup->getId()
            )
        );
    }
}
