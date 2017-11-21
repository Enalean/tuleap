<?php
/**
 * Copyright (c) Enalean, 2013-2017. All Rights Reserved.
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

class Project_Admin_UGroup_View_Settings extends Project_Admin_UGroup_View {

    const IDENTIFIER = 'settings';

    /**
     * @var UserManager
     */
    private $user_manager;
    /**
     * @var EventManager
     */
    private $event_manager;
    /**
     * @var PermissionsManager
     */
    private $permissions_manager;
    /**
     * @var FRSReleaseFactory
     */
    private $release_factory;
    /**
     * @var Codendi_HTMLPurifier
     */
    private $html_purifier;
    private $plugin_binding;
    /**
     * @var LdapPlugin
     */
    private $ldap_plugin;
    /**
     * @var UGroupBinding
     */
    protected $ugroup_binding;
    /**
     * @var ProjectManager
     */
    protected $project_manager;
    /**
     * @var UserHelper
     */
    private $user_helper;

    public function __construct(
        ProjectUGroup $ugroup,
        UGroupBinding $ugroup_binding,
        $plugin_binding,
        LdapPlugin $ldap_plugin = null
    ) {
        parent::__construct($ugroup);
        $this->plugin_binding = $plugin_binding;
        $this->ldap_plugin    = $ldap_plugin;
        $this->ugroup_binding = $ugroup_binding;

        $this->project_manager     = ProjectManager::instance();
        $this->user_manager        = UserManager::instance();
        $this->permissions_manager = PermissionsManager::instance();
        $this->event_manager       = EventManager::instance();
        $this->html_purifier       = Codendi_HTMLPurifier::instance();
        $this->release_factory     = new FRSReleaseFactory();
        $this->user_helper         = new UserHelper();
    }

    public function getContent() {
        $permissions = $this->getFormattedPermissions();
        $binding     = $this->getBinding();
        $members     = $this->getMembers();

        $csrf = new CSRFSynchronizerToken('/project/admin/ugroup.php');

        return TemplateRendererFactory::build()
            ->getRenderer(ForgeConfig::get('codendi_dir') . '/src/templates/project/admin/')
            ->renderToString(
                'ugroup-settings',
                array(
                    'id'              => $this->ugroup->getId(),
                    'project_id'      => $this->ugroup->getProjectId(),
                    'name'            => $this->ugroup->getName(),
                    'description'     => $this->ugroup->getDescription(),
                    'has_permissions' => count($permissions) > 0,
                    'permissions'     => $permissions,
                    'binding'         => $binding,
                    'members'         => $members,
                    'csrf_token'      => $csrf
                )
            );
    }

    /**
     * @return array
     */
    private function getFormattedPermissions()
    {
        $data      = array();
        $dar       = $this->permissions_manager->searchByUgroupId($this->ugroup->getId());
        $row_count = 0;

        if ($dar && !$dar->isError() && $dar->rowCount() >0) {
            foreach ($dar as $row) {
                if (strpos($row['permission_type'], 'TRACKER_FIELD') === 0) {
                    $atid = permission_extract_atid($row['object_id']);
                    if (isset($tracker_field_displayed[$atid])) {
                        continue;
                    }
                    $objname = permission_get_object_name('TRACKER_ACCESS_FULL', $atid);
                } else {
                    $objname = permission_get_object_name($row['permission_type'], $row['object_id']);
                }

                if (!$objname) {
                    continue;
                }

                if ($row['permission_type'] == 'PACKAGE_READ') {
                    $content = $GLOBALS['Language']->getText('project_admin_editugroup', 'package')
                        . ' <a href="/file/admin/editpackagepermissions.php?package_id='
                        . urlencode($row['object_id']) . '&group_id=' . urlencode($this->ugroup->getProjectId()) . '">'
                        . $this->html_purifier->purify($objname) . '</a>';
                } else if ($row['permission_type'] == 'RELEASE_READ') {
                    $release         = $this->release_factory->getFRSReleaseFromDb($row['object_id']);
                    $package_name    = $release->getPackage()->getName();
                    $package_id      = $release->getPackageID();

                    $content = $GLOBALS['Language']->getText('project_admin_editugroup', 'release')
                        . ' <a href="/file/admin/editreleasepermissions.php?release_id=' . urlencode($row['object_id']) . '&group_id=' . urlencode($this->ugroup->getProjectId()) . '&package_id=' . urlencode($package_id) . '">'
                        . $this->html_purifier->purify($objname) . '</a> ('
                        . $GLOBALS['Language']->getText('project_admin_editugroup', 'from_package')
                        . ' <a href="/file/admin/editreleases.php?package_id=' . urlencode($package_id) . '&group_id=' . urlencode($this->ugroup->getProjectId()) . '">'
                        . $this->html_purifier->purify($package_name) . '</a> )';
                } else if ($row['permission_type'] == 'WIKI_READ') {
                    $content = $GLOBALS['Language']->getText('project_admin_editugroup', 'wiki')
                        . ' <a href="/wiki/admin/index.php?view=wikiPerms&group_id=' . urlencode($this->ugroup->getProjectId()) . '">'
                        . $this->html_purifier->purify($objname) . '</a>';
                } else if ($row['permission_type'] == 'WIKIPAGE_READ') {
                    $content = $GLOBALS['Language']->getText('project_admin_editugroup', 'wiki_page')
                        . ' <a href="/wiki/admin/index.php?group_id=' . urlencode($this->ugroup->getProjectId()) . '&view=pagePerms&id=' . urlencode($row['object_id']) . '">'
                        . $this->html_purifier->purify($objname) . '</a>';
                } else if (strpos($row['permission_type'], 'TRACKER_ACCESS') === 0) {
                    $content = $GLOBALS['Language']->getText('project_admin_editugroup', 'tracker')
                        . ' <a href="/tracker/admin/?func=permissions&perm_type=tracker&group_id=' . urlencode($this->ugroup->getProjectId()) . '&atid=' . urlencode($row['object_id']) . '">'
                        . $this->html_purifier->purify($objname) . '</a>';
                } else if (strpos($row['permission_type'], 'TRACKER_FIELD') === 0) {
                    $tracker_field_displayed[$atid] = 1;
                    $atid                           = permission_extract_atid($row['object_id']);
                    $content = $GLOBALS['Language']->getText('project_admin_editugroup', 'tracker_field')
                        . ' <a href="/tracker/admin/?group_id=' . urlencode($this->ugroup->getProjectId()) . '&atid=' . urlencode($atid) . '&func=permissions&perm_type=fields&group_first=1&selected_id=' . urlencode($this->ugroup->getId()) . '">'
                        . $this->html_purifier->purify($objname) . '</a>';
                } else if ($row['permission_type'] == 'TRACKER_ARTIFACT_ACCESS') {
                    $content = $this->html_purifier->purify($objname, CODENDI_PURIFIER_BASIC);
                } else {
                    $results = false;
                    $this->event_manager->processEvent('permissions_for_ugroup', array(
                        'permission_type' => $row['permission_type'],
                        'object_id' => $row['object_id'],
                        'objname' => $objname,
                        'group_id' => $this->ugroup->getProjectId(),
                        'ugroup_id' => $this->ugroup->getId(),
                        'results' => &$results
                    ));
                    if ($results) {
                        $content = $results;
                    } else {
                        $content = $row['object_id'];
                    }
                }

                $data[$row_count]['permission_name'] = permission_get_name($row['permission_type']);
                $data[$row_count]['content']         = $content;
                $row_count++;
            }

            return $data;
        }
    }

    public function getIdentifier() {
        return self::IDENTIFIER;
    }

    private function getBinding()
    {
        $url_add_ldap = '/project/admin/editugroup.php?' .
            http_build_query(
                array(
                    'group_id'  => $this->ugroup->getProjectId(),
                    'ugroup_id' => $this->ugroup->getId(),
                    'func'      => 'edit',
                    'pane'      => 'binding',
                    'action'    => 'edit_directory_group',
                )
            );

        $clones = $this->getClones();

        $csrf = new CSRFSynchronizerToken(
            'project/admin/editugroup.php&' . http_build_query(
                array(
                    'group_id'  => $this->ugroup->getProjectId(),
                    'ugroup_id' => $this->ugroup->getId(),
                    'func'      => 'edit',
                    'pane'      => 'settings',
                )
            )
        );

        return array(
            'add_binding'          => $this->getAddBinding(),
            'url_add_ldap_binding' => $url_add_ldap,
            'add_ldap_title'       => $this->getLDAPTitle(),
            'has_clones'           => count($clones) > 0,
            'clones'               => $clones,
            'current_binding'      => $this->getCurrentBinding(),
            'has_ldap'             => ! empty($this->plugin_binding),
            'csrf_token'           => $csrf
        );
    }

    /**
     * Get the HTML output for ugroups bound to the current one
     */
    private function getClones() {
        $ugroups        = array();
        $nb_not_visible = 0;
        foreach ($this->ugroup_binding->getUGroupsByBindingSource($this->ugroup->getId()) as $id => $clone) {
            $project = $this->project_manager->getProject($clone['group_id']);
            if ($project->userIsAdmin()) {
                $ugroups[] = $this->getUgroupBindingPresenter($project, $id, $clone['cloneName']);
            } else {
                $nb_not_visible ++;
            }
        }

        return array(
            'ugroups'        => $ugroups,
            'has_ugroups'    => count($ugroups) > 0,
            'nb_not_visible' => $nb_not_visible
        );
    }

    /**
     * Create the good title link if we have already a ldap group linked or not
     *
     * @return String
     */
    private function getLDAPTitle() {
        if (! $this->ldap_plugin) {
            return false;
        }

        $ldap_group = $this->ldap_plugin
            ->getLdapUserGroupManager()
            ->getLdapGroupByGroupId($this->ugroup->getId());

        if ($ldap_group !== null) {
            $name = $this->html_purifier->purify($ldap_group->getCommonName());
            $title = $GLOBALS['Language']->getText('plugin_ldap', 'ugroup_list_add_upd_binding', $name);
        } else {
            $title = $GLOBALS['Language']->getText('plugin_ldap', 'ugroup_list_add_set_binding');
        }

        return $title;
    }

    private function getCurrentBinding()
    {
        $source = $this->ugroup->getSourceGroup();
        if (! $source) {
            return false;
        }

        $project = $source->getProject();
        if (! $project->userIsAdmin()) {
            return $this->getEmptyUgroupBindingPresenter();
        }

        return $this->getUgroupBindingPresenter($project, $source->getId(), $source->getName());
    }

    private function getUgroupBindingPresenter($project, $id, $name)
    {
        return array(
            'project_url'  => '/projects/' . $project->getUnixName(),
            'project_name' => $project->getUnconvertedPublicName(),
            'ugroup_url'   => '/project/admin/editugroup.php?' . http_build_query(
                    array(
                        'group_id'  => $project->getID(),
                        'ugroup_id' => $id,
                        'func'      => 'edit',
                    )
                ),
            'ugroup_name'  => $name,
        );
    }

    private function getEmptyUgroupBindingPresenter()
    {
        return array(
            'project_url'  => '',
            'project_name' => '',
            'ugroup_url'   => '',
            'ugroup_name'  => '',
        );
    }

    private function getMembers()
    {
        $can_be_updated = ! $this->ugroup->isBound();
        $em             = EventManager::instance();
        $em->processEvent(
            Event::UGROUP_UPDATE_USERS_ALLOWED,
            array('ugroup_id' => $this->ugroup->getId(), 'allowed' => &$can_be_updated)
        );

        $members = $this->getFormattedProjectMembers();

        $csrf = new CSRFSynchronizerToken(
            '/project/admin/editugroup.php?group_id=' . $this->ugroup->getProjectId()
            . '&ugroup_id=' . $this->ugroup->getId() . '&func=edit&pane=settings'
        );

        return array(
            'has_members'    => count($members) > 0,
            'can_be_updated' => $can_be_updated,
            'members'        => $members,
            'csrf_token'     => $csrf
        );
    }

    private function getFormattedProjectMembers()
    {
        $ugroup_members = array();

        foreach ($this->ugroup->getMembers() as $key => $member) {
            $ugroup_members[$key]['profile_page_url'] = "/users/" . urlencode($member->getUserName()) . "/";

            $ugroup_members[$key]['username_display'] = $this->user_helper->getDisplayName(
                $member->getUserName(),
                $member->getRealName()
            );

            $ugroup_members[$key]['has_avatar'] = $member->hasAvatar();
            $ugroup_members[$key]['user_name']  = $member->getUserName();
            $ugroup_members[$key]['user_id']    = $member->getId();
        }

        return $ugroup_members;
    }

    private function getAddBinding()
    {
        return array(
            'projects' => $this->getProjectsPresentersForBinding(),
        );
    }

    private function getProjectsPresentersForBinding()
    {
        $current_user       = $this->user_manager->getCurrentUser();
        $projects           = array();
        $current_project_id = $this->ugroup->getProjectId();
        $projects_of_user   = $current_user->getProjects(true);
        foreach ($projects_of_user as $project_as_row) {
            if ($current_project_id == $project_as_row['group_id']) {
                continue;
            }

            $project = $this->project_manager->getProject($project_as_row['group_id']);
            if (! $current_user->isAdmin($project->getID())) {
                continue;
            }

            $ugroup_list = $this->getUgroupPresenterList($project->getID());
            if (empty($ugroup_list)) {
                continue;
            }

            $projects[] = array(
                'id'                   => $project->getID(),
                'name'                 => $project->getUnconvertedPublicName(),
                'json_encoded_ugroups' => json_encode($ugroup_list)
            );
        }

        return $projects;
    }

    private function getUgroupPresenterList($project_id) {
        $ugroupList = array();
        $ugroups    = ugroup_db_get_existing_ugroups($project_id);
        while ($ugroup_row = db_fetch_array($ugroups)) {
            $user_group = new ProjectUGroup(array('ugroup_id' => $ugroup_row['ugroup_id']));
            if (! $user_group->isBound()) {
                $ugroupList[] = array('id' => $ugroup_row['ugroup_id'], 'name' => $ugroup_row['name']);
            }
        }

        return $ugroupList;
    }
}
