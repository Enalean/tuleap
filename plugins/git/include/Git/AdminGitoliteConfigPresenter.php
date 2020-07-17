<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

class Git_AdminGitoliteConfigPresenter extends Git_AdminPresenter
{

    public $manage_gitolite_config = true;
    public $gitolite_config_active = 'tlp-tab-active';
    public $regenerate_gitolite_configuration;

    /**
     * @var Project[]
     */
    private $authorized_projects;

    /**
     * @var bool
     */
    private $show_big_objects_config;

    public function __construct(
        $title,
        CSRFSynchronizerToken $csrf_token,
        $authorized_projects,
        $show_big_objects_config
    ) {
        parent::__construct($title, $csrf_token);

        $this->regenerate_gitolite_configuration     = dgettext('tuleap-git', 'Regenerate configuration');
        $this->authorized_projects                   = $authorized_projects;
        $this->show_big_objects_config               = $show_big_objects_config;
    }

    public function gitolite_config_title()
    {
        return dgettext('tuleap-git', 'Gitolite configuration');
    }

    public function gitolite_config_description()
    {
        return dgettext('tuleap-git', 'This section allows you to regenerate the Gitolite configuration file of a selected project.');
    }

    public function submit()
    {
        return dgettext('tuleap-git', 'Submit');
    }

    public function update_allowed_projects_action()
    {
        return '/admin/git/?pane=gitolite_config&action=update-big-objects-allowed-projects';
    }

    public function project_name_placeholder()
    {
        return dgettext('tuleap-git', 'Project name');
    }

    public function authorized_projects_list_title()
    {
        return dgettext('tuleap-git', 'Authorized projects list');
    }

    public function authorized_projects_section_title()
    {
        return dgettext("tuleap-git", "Projects authorized to go over object's size limit");
    }

    public function allowed_projects_list_allow_project()
    {
        return dgettext("tuleap-git", "Authorize");
    }

    public function allowed_projects_list_revoke_projects()
    {
        return dgettext("tuleap-git", "Revoke authorization");
    }

    public function there_are_no_projects()
    {
        return count($this->authorized_projects) === 0;
    }

    public function allowed_projects_list_filter_placeholder()
    {
        return dgettext("tuleap-git", "Filter using project's id or name");
    }

    public function allowed_projects_list_id()
    {
        return dgettext("tuleap-git", "Id");
    }

    public function allowed_projects_list_name()
    {
        return dgettext("tuleap-git", "Name");
    }

    public function allowed_projects_list_empty()
    {
        return dgettext("tuleap-git", "Right now, there are no projects authorized to go over object's size limit");
    }

    public function allowed_projects_filter_empty()
    {
        return dgettext("tuleap-git", "There are no projects matching the filter");
    }

    public function allowed_projects()
    {
        return $this->authorized_projects;
    }

    public function show_big_objects_config()
    {
        return $this->show_big_objects_config;
    }
}
