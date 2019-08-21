<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Git;

use Git_RemoteServer_GerritServer;
use CSRFSynchronizerToken;

class AdminAllowedProjectsGerritPresenter
{

    public const TEMPLATE = 'manage-allowed-projects';

    /**
     * @var Git_RemoteServer_GerritServer
     */
    private $gerrit;

    /**
     * @var Project[]
     */
    public $allowed_projects;

    /**
     * @var bool
     */
    public $is_resource_restricted;

    public $allow_all_enabled = true;

    public function __construct(
        Git_RemoteServer_GerritServer $gerrit_server,
        array $allowed_projects,
        $is_mirror_restricted
    ) {
        $this->gerrit                 = $gerrit_server;
        $this->allowed_projects       = $allowed_projects;
        $this->is_resource_restricted = $is_mirror_restricted;
    }

    public function getTemplate()
    {
        return self::TEMPLATE;
    }

    public function there_is_no_project()
    {
        return count($this->allowed_projects) === 0;
    }

    public function restricted_resource_action()
    {
        return GIT_SITE_ADMIN_BASE_URL . '?view=gerrit_servers_restriction&action=set-gerrit-server-restriction&gerrit_server_id=' .
                urlencode($this->gerrit->getId());
    }

    public function restricted_resource_action_csrf()
    {
        $csrf = new CSRFSynchronizerToken($this->restricted_resource_action());
        return $csrf->fetchHTMLInput();
    }

    public function update_allowed_projects_action()
    {
        return GIT_SITE_ADMIN_BASE_URL . '?view=gerrit_servers_restriction&action=update-allowed-project-list&gerrit_server_id=' .
                urlencode($this->gerrit->getId());
    }

    public function update_allowed_projects_action_csrf()
    {
        $csrf = new CSRFSynchronizerToken($this->update_allowed_projects_action());
        return $csrf->fetchHTMLInput();
    }

    public function resource_allowed_project_back_link_title()
    {
        return dgettext('tuleap-git', 'Back to Gerrit servers list');
    }

    public function resource_allowed_project_title()
    {
        return sprintf(dgettext('tuleap-git', '%1$s projects restriction'), $this->gerrit->getHost());
    }

    public function resource_allowed_project_pane_title()
    {
        return sprintf(dgettext('tuleap-git', 'Projects allowed to use %1$s'), $this->gerrit->getHost());
    }

    public function resource_allowed_project_information()
    {
        return '';
    }

    public function resource_allowed_project_allow_all()
    {
        return dgettext('tuleap-git', 'Allow all the projects to use this Gerrit server.');
    }

    public function resource_allowed_project_allow_all_submit()
    {
        return dgettext('tuleap-git', 'Save');
    }

    public function resource_allowed_project_list()
    {
        return dgettext('tuleap-git', 'List of allowed projects');
    }

    public function resource_allowed_project_list_allow_placeholder()
    {
        return dgettext('tuleap-git', 'Project name');
    }

    public function resource_allowed_project_list_filter_placeholder()
    {
        return dgettext('tuleap-git', 'Filter on project id or name');
    }

    public function resource_allowed_project_list_allow_project()
    {
        return dgettext('tuleap-git', 'Allow access');
    }

    public function resource_allowed_project_list_revoke_projects()
    {
        return dgettext('tuleap-git', 'Revoke access to selected');
    }

    public function resource_allowed_project_list_id()
    {
        return dgettext('tuleap-git', 'Id');
    }

    public function resource_allowed_project_list_name()
    {
        return dgettext('tuleap-git', 'Name');
    }

    public function resource_allowed_project_list_empty()
    {
        return dgettext('tuleap-git', 'Currently, there\'s no project allowed to use this Gerrit server.');
    }

    public function resource_allowed_project_revoke_title()
    {
        return dgettext('tuleap-git', 'Warning');
    }

    public function resource_allowed_project_revoke_description()
    {
        return dgettext('tuleap-git', 'You are about to revoke the access to this Gerrit server to one or several projects. Are you sure you want to do this?');
    }

    public function resource_allowed_project_revoke_yes()
    {
        return dgettext('tuleap-git', 'Yes, revoke access');
    }

    public function resource_allowed_project_revoke_no()
    {
        return dgettext('tuleap-git', 'No');
    }

    public function resource_allowed_project_filter_empty()
    {
        return $GLOBALS['Language']->getText('admin', 'allowed_projects_filter_empty');
    }
}
