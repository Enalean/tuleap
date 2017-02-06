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

    const TEMPLATE = 'manage-allowed-projects';

    /**
     * @var Git_RemoteServer_GerritServer
     */
    private $gerrit;

    /**
     * @var Project[]
     */
    public $allowed_projects;

    /**
     * @var Boolean
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
        return '/plugins/git/admin/?view=gerrit_servers_restriction&action=set-gerrit-server-restriction&gerrit_server_id=' .
                urlencode($this->gerrit->getId());
    }

    public function restricted_resource_action_csrf()
    {
        $csrf = new CSRFSynchronizerToken($this->restricted_resource_action());
        return $csrf->fetchHTMLInput();
    }

    public function update_allowed_projects_action()
    {
        return '/plugins/git/admin/?view=gerrit_servers_restriction&action=update-allowed-project-list&gerrit_server_id=' .
                urlencode($this->gerrit->getId());
    }

    public function update_allowed_projects_action_csrf()
    {
        $csrf = new CSRFSynchronizerToken($this->update_allowed_projects_action());
        return $csrf->fetchHTMLInput();
    }

    public function resource_allowed_project_back_link_title()
    {
        return $GLOBALS['Language']->getText('plugin_git', 'gerrit_servers_allowed_project_back_link_title');
    }

    public function resource_allowed_project_title()
    {
        return $GLOBALS['Language']->getText('plugin_git', 'gerrit_servers_allowed_project_title', $this->gerrit->getHost());
    }

    public function resource_allowed_project_pane_title()
    {
        return $GLOBALS['Language']->getText('plugin_git', 'gerrit_servers_allowed_project_pane_title', $this->gerrit->getHost());
    }

    public function resource_allowed_project_information()
    {
        return '';
    }

    public function resource_allowed_project_allow_all()
    {
        return $GLOBALS['Language']->getText('plugin_git', 'gerrit_servers_allowed_project_allow_all');
    }

    public function resource_allowed_project_allow_all_submit()
    {
        return '';
    }

    public function resource_allowed_project_list()
    {
        return '';
    }

    public function resource_allowed_project_list_allow_placeholder()
    {
        return '';
    }

    public function resource_allowed_project_list_filter_placeholder()
    {
        return '';
    }

    public function resource_allowed_project_list_allow_project()
    {
        return '';
    }

    public function resource_allowed_project_list_revoke_projects()
    {
        return '';
    }

    public function resource_allowed_project_list_id()
    {
        return '';
    }

    public function resource_allowed_project_list_name()
    {
        return '';
    }

    public function resource_allowed_project_list_empty()
    {
        return '';
    }

    public function resource_allowed_project_revoke_title()
    {
        return '';
    }

    public function resource_allowed_project_revoke_description()
    {
        return '';
    }

    public function resource_allowed_project_revoke_yes()
    {
        return '';
    }

    public function resource_allowed_project_revoke_no()
    {
        return '';
    }

    public function resource_allowed_project_filter_empty()
    {
        return $GLOBALS['Language']->getText('admin', 'allowed_projects_filter_empty');
    }
}
