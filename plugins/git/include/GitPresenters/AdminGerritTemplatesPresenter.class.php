<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

class GitPresenters_AdminGerritTemplatesPresenter extends GitPresenters_AdminPresenter
{

    /**
     * List of repositories belonging to the project
     *
     * @var array
     */
    private $repository_list;

    /**
     * List of templates belonging to the project
     *
     * @var array
     */
    private $templates_list;

    /**
     * List of templates belonging to the parent project hierarchy
     *
     * @var array
     */
    private $parent_templates_list;

    /**
     * @var bool
     */
    public $has_gerrit_servers_set_up;


    public function __construct(
        $repository_list,
        $templates_list,
        $parent_templates_list,
        $project_id,
        $are_mirrors_defined,
        array $external_pane_presenters,
        $has_gerrit_servers_set_up
    ) {
        parent::__construct($project_id, $are_mirrors_defined, $external_pane_presenters);

        $this->repository_list           = $repository_list;
        $this->templates_list            = $templates_list;
        $this->parent_templates_list     = $parent_templates_list;
        $this->has_gerrit_servers_set_up = $has_gerrit_servers_set_up;
    }

    public function configurations_text()
    {
        return dgettext('tuleap-git', 'Configurations');
    }

    public function templates_text()
    {
        return dgettext('tuleap-git', 'Templates');
    }

    public function edit_text()
    {
        return dgettext('tuleap-git', 'You can edit the template. The <b>%projectname%</b> variable is available; it will be replaced by the project\'s name during the migration process:');
    }

    public function file_name_text()
    {
        return dgettext('tuleap-git', 'Template file name');
    }

    public function save_text()
    {
        return dgettext('tuleap-git', 'Save');
    }

    public function config_option()
    {
        return array_values($this->repository_list);
    }

    public function templates_option()
    {
        return $this->templates_list;
    }

    public function parent_templates_option()
    {
        return $this->parent_templates_list;
    }

    public function templates_form_action()
    {
        return '/plugins/git/?group_id=' . $this->project_id . '&action=admin-gerrit-templates';
    }

    public function template_action_text()
    {
        return dgettext('tuleap-git', 'Action');
    }

    public function template_name_text()
    {
        return dgettext('tuleap-git', 'Template name');
    }

    public function edit()
    {
        return dgettext('tuleap-git', 'edit');
    }

    public function view()
    {
        return dgettext('tuleap-git', 'view');
    }

    public function template_section_title()
    {
        return dgettext('tuleap-git', 'Gerrit permission configuration templates');
    }

    public function template_section_description()
    {
        return dgettext('tuleap-git', 'This section allows you to choose a Gerrit project configuration and create a reusable template from it');
    }

    public function please_choose()
    {
        return dgettext('tuleap-git', 'Please choose');
    }

    public function cancel()
    {
        return dgettext('tuleap-git', 'Cancel');
    }

    public function create_new_template_text()
    {
        return dgettext('tuleap-git', 'Create new');
    }

    public function template_from_gerrit_text()
    {
        return dgettext('tuleap-git', 'from gerrit config');
    }

    public function template_from_template_text()
    {
        return dgettext('tuleap-git', 'from template');
    }

    public function template_from_scratch_text()
    {
        return dgettext('tuleap-git', 'from scratch');
    }

    public function no_gerrit_servers()
    {
        return sprintf(dgettext('tuleap-git', 'It does not appear to be any <em><a href="%1$s">gerrit server</a></em> set-up for this platform! Please contact your site administrator.'), GIT_SITE_ADMIN_BASE_URL);
    }

    public function delete_label()
    {
        return dgettext('tuleap-git', 'Delete');
    }
}
