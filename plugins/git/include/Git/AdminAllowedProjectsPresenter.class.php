<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

class Git_AdminMAllowedProjectsPresenter {

    const TEMPLATE = 'admin-plugin-manage-allowed-projects';

    /**
     * @var Git_Mirror_Mirror
     */
    private $mirror;

    /**
     * @var Project[]
     */
    public $allowed_projects;

    /**
     * @var Boolean
     */
    public $is_mirror_restricted;

    /**
     * @var string
     */
    public $csrf_input;

    public function __construct(
        Git_Mirror_Mirror $mirror,
        array $allowed_projects,
        $is_mirror_restricted,
        CSRFSynchronizerToken $csrf
    ) {
        $this->mirror               = $mirror;
        $this->allowed_projects     = $allowed_projects;
        $this->is_mirror_restricted = $is_mirror_restricted;
        $this->csrf_input           = $csrf->fetchHTMLInput();
    }

    public function there_is_no_project() {
        return count($this->allowed_projects) === 0;
    }

    public function restricted_mirror_action() {
        return '?pane=mirrors_admin&action=set-mirror-restriction&mirror_id=' . $this->mirror->id;
    }

    public function update_allowed_projects_action() {
        return '?pane=mirrors_admin&action=update-allowed-project-list&mirror_id=' . $this->mirror->id;
    }

    public function mirror_allowed_project_title() {
        return $GLOBALS['Language']->getText('plugin_git', 'mirror_allowed_project_title', array($this->mirror->url));
    }

    public function mirror_allowed_project_allow_all() {
        return $GLOBALS['Language']->getText('plugin_git', 'mirror_allowed_project_allow_all');
    }

    public function mirror_allowed_project_allow_all_submit() {
        return $GLOBALS['Language']->getText('plugin_git', 'mirror_allowed_project_allow_all_submit');
    }

    public function mirror_allowed_project_list() {
        return $GLOBALS['Language']->getText('plugin_git', 'mirror_allowed_project_list');
    }

    public function mirror_allowed_project_list_allow_placeholder() {
        return $GLOBALS['Language']->getText('plugin_git', 'mirror_allowed_project_list_allow_placeholder');
    }

    public function mirror_allowed_project_list_filter_placeholder() {
        return $GLOBALS['Language']->getText('plugin_git', 'mirror_allowed_project_list_filter_placeholder');
    }

    public function mirror_allowed_project_list_allow_project() {
        return $GLOBALS['Language']->getText('plugin_git', 'mirror_allowed_project_list_allow_project');
    }

    public function mirror_allowed_project_list_revoke_projects() {
        return $GLOBALS['Language']->getText('plugin_git', 'mirror_allowed_project_list_revoke_projects');
    }

    public function mirror_allowed_project_list_id() {
        return $GLOBALS['Language']->getText('plugin_git', 'mirror_allowed_project_list_id');
    }

    public function mirror_allowed_project_list_name() {
        return $GLOBALS['Language']->getText('plugin_git', 'mirror_allowed_project_list_name');
    }

    public function mirror_allowed_project_list_empty() {
        return $GLOBALS['Language']->getText('plugin_git', 'mirror_allowed_project_list_empty');
    }

    public function mirror_allowed_project_revoke_title() {
        return $GLOBALS['Language']->getText('plugin_git', 'mirror_allowed_project_revoke_title');
    }

    public function mirror_allowed_project_revoke_description() {
        return $GLOBALS['Language']->getText('plugin_git', 'mirror_allowed_project_revoke_description');
    }

    public function mirror_allowed_project_revoke_yes() {
        return $GLOBALS['Language']->getText('plugin_git', 'mirror_allowed_project_revoke_yes');
    }

    public function mirror_allowed_project_revoke_no() {
        return $GLOBALS['Language']->getText('plugin_git', 'mirror_allowed_project_revoke_no');
    }
}