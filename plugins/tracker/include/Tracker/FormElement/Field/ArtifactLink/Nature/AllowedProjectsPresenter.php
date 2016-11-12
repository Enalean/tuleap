<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature;

use CSRFSynchronizerToken;

class AllowedProjectsPresenter {

    const TEMPLATE = 'allowed-projects-list';

    /**  @var Project[] */
    public $allowed_projects;
    public $there_is_no_project;
    public $update_allowed_projects_action;
    public $update_allowed_projects_action_csrf;
    public $resource_allowed_project_list_allow_placeholder;
    public $resource_allowed_project_list;
    public $resource_allowed_project_list_id;
    public $resource_allowed_project_list_name;
    public $resource_allowed_project_list_empty;
    public $resource_allowed_project_list_allow_project;
    public $resource_allowed_project_list_revoke_projects;
    public $resource_allowed_project_list_filter_placeholder;
    public $resource_allowed_project_revoke_description;
    public $resource_allowed_project_revoke_title;
    public $resource_allowed_project_revoke_yes;
    public $resource_allowed_project_revoke_no;
    public $resource_allowed_project_filter_empty;

    public function __construct(CSRFSynchronizerToken $csrf, array $allowed_projects) {
        $this->allowed_projects    = $allowed_projects;
        $this->there_is_no_project = count($this->allowed_projects) === 0;

        $this->update_allowed_projects_action      = '/plugins/tracker/config.php?action=restrict-natures';
        $this->update_allowed_projects_action_csrf = $csrf->fetchHTMLInput();

        $this->resource_allowed_project_list                    = false;
        $this->resource_allowed_project_list_id                 = $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', 'allowed_project_list_id');
        $this->resource_allowed_project_list_name               = $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', 'allowed_project_list_name');
        $this->resource_allowed_project_list_empty              = $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', 'allowed_project_list_empty');
        $this->resource_allowed_project_list_allow_project      = $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', 'allowed_project_list_allow_project');
        $this->resource_allowed_project_list_revoke_projects    = $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', 'allowed_project_list_revoke_projects');
        $this->resource_allowed_project_list_allow_placeholder  = $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', 'allowed_project_list_allow_placeholder');
        $this->resource_allowed_project_list_filter_placeholder = $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', 'allowed_project_list_filter_placeholder');

        $this->resource_allowed_project_revoke_description = $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', 'allowed_project_revoke_description');
        $this->resource_allowed_project_revoke_title       = $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', 'allowed_project_revoke_title');
        $this->resource_allowed_project_revoke_yes         = $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', 'allowed_project_revoke_yes');
        $this->resource_allowed_project_revoke_no          = $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', 'allowed_project_revoke_no');

        $this->resource_allowed_project_filter_empty = $GLOBALS['Language']->getText('admin', 'allowed_projects_filter_empty');
    }
}
