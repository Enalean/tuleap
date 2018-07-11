<?php
/**
 * Copyright (c) Enalean, 2014 - 2018. All rights reserved
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
        $has_gerrit_servers_set_up
    ) {
        parent::__construct($project_id, $are_mirrors_defined);

        $this->manage_gerrit_templates   = true;
        $this->repository_list           = $repository_list;
        $this->templates_list            = $templates_list;
        $this->parent_templates_list     = $parent_templates_list;
        $this->has_gerrit_servers_set_up = $has_gerrit_servers_set_up;
    }

    public function configurations_text() {
        return $GLOBALS['Language']->getText('plugin_git', 'view_admin_repos_list');
    }

    public function templates_text() {
        return $GLOBALS['Language']->getText('plugin_git', 'view_admin_templates_list');
    }

    public function edit_text() {
        return $GLOBALS['Language']->getText('plugin_git', 'view_admin_edit_configuration_label');
    }

    public function file_name_text() {
        return $GLOBALS['Language']->getText('plugin_git', 'view_admin_file_name_label');
    }

    public function save_text() {
        return $GLOBALS['Language']->getText('plugin_git', 'view_admin_submit_button');
    }

    public function config_option() {
        return array_values($this->repository_list);
    }

    public function templates_option() {
        return $this->templates_list;
    }

    public function parent_templates_option() {
        return $this->parent_templates_list;
    }

    public function templates_form_action() {
        return '/plugins/git/?group_id='. $this->project_id .'&action=admin-gerrit-templates';
    }

    public function template_action_text() {
        return $GLOBALS['Language']->getText('plugin_git', 'view_admin_template_table_action');
    }

    public function template_name_text() {
        return $GLOBALS['Language']->getText('plugin_git', 'view_admin_template_table_name');
    }

    public function edit() {
        return $GLOBALS['Language']->getText('plugin_git', 'edit');
    }

    public function view() {
        return $GLOBALS['Language']->getText('plugin_git', 'view');
    }

    public function template_section_title() {
        return $GLOBALS['Language']->getText('plugin_git', 'view_admin_template_section_title');
    }

    public function template_section_description() {
        return $GLOBALS['Language']->getText('plugin_git', 'view_admin_template_section_description');
    }

    public function please_choose() {
        return $GLOBALS['Language']->getText('plugin_git', 'please_choose');
    }

    public function cancel() {
        return $GLOBALS['Language']->getText('plugin_git', 'cancel');
    }

    public function create_new_template_text() {
        return $GLOBALS['Language']->getText('plugin_git', 'view_admin_create_new_template_text');
    }

    public function template_from_gerrit_text() {
        return $GLOBALS['Language']->getText('plugin_git', 'view_admin_template_from_gerrit_text');
    }

    public function template_from_template_text() {
        return $GLOBALS['Language']->getText('plugin_git', 'view_admin_template_from_template_text');
    }

    public function template_from_scratch_text() {
        return $GLOBALS['Language']->getText('plugin_git', 'view_admin_template_from_scratch_text');
    }

    public function no_gerrit_servers()
    {
        return $GLOBALS['Language']->getText('plugin_git', 'view_admin_no_gerrit_server', [GIT_SITE_ADMIN_BASE_URL]);
    }

    public function delete_label() {
        return $GLOBALS['Language']->getText('plugin_git', 'gerrit_template_delete');
    }
}