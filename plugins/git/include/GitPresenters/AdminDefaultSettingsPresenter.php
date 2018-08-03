<?php
/**
 * Copyright (c) Enalean, 2016 - 2018. All Rights Reserved.
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

use Tuleap\Git\DefaultSettings\IndexController;

class GitPresenters_AdminDefaultSettingsPresenter extends GitPresenters_AdminPresenter
{
    public $title_warning_regexp_uncheck;
    public $warning_regexp_uncheck;
    public $save_permissions;
    public $regexp_incoherence_label;
    public $regexp_permission_label;
    public $warnings;
    public $are_regexp_permission_conflicting_at_site_level;
    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf;

    public $read_options;
    public $write_options;
    public $rewrite_options;
    public $mirror_presenters;
    public $are_fine_grained_permissions_defined;
    public $can_use_fine_grained_permissions;
    public $cannot_define_per_repo_permissions;
    public $default_permissions_title;
    public $fine_grained_permissions_title;
    public $remove_fine_grained_permission_delete;
    public $remove_fine_grained_permission_desc;
    public $remove_fine_grained_permission_confirm;
    public $btn_cancel;
    public $remove_form_action;
    public $disabled;

    /**
     * @var array
     */
    public $new_fine_grained_ugroups;

    /**
     * @var array
     */
    public $tags_permissions;

    /**
     * @var array
     */
    public $branches_permissions;

    public $are_regexp_permission_activated_at_site_level;

    public $is_regexp_enabled;

    public function __construct(
        $project_id,
        $are_mirrors_defined,
        array $mirror_presenters,
        CSRFSynchronizerToken $csrf,
        array $read_options,
        array $write_options,
        array $rewrite_options,
        $pane_access_control,
        $pane_mirroring,
        $are_fine_grained_permissions_defined,
        $can_use_fine_grained_permissions,
        array $branches_permissions_representation,
        array $tags_permissions_representation,
        array $new_fine_grained_ugroups,
        $delete_url,
        CSRFSynchronizerToken $csrf_delete,
        $are_regexp_permission_activated_at_site_level,
        $is_regexp_enabled_at_global_level,
        $are_regexp_permission_conflicting_at_site_level
    ) {
        parent::__construct($project_id, $are_mirrors_defined);

        $this->manage_default_settings                         = true;
        $this->mirror_presenters                               = $mirror_presenters;
        $this->pane_access_control                             = $pane_access_control;
        $this->pane_mirroring                                  = $pane_mirroring;
        $this->read_options                                    = $read_options;
        $this->write_options                                   = $write_options;
        $this->rewrite_options                                 = $rewrite_options;
        $this->csrf                                            = $csrf;
        $this->are_fine_grained_permissions_defined            = $are_fine_grained_permissions_defined;
        $this->can_use_fine_grained_permissions                = $can_use_fine_grained_permissions;
        $this->cannot_define_per_repo_permissions              = $are_fine_grained_permissions_defined;
        $this->are_regexp_permission_activated_at_site_level   = $are_regexp_permission_activated_at_site_level;
        $this->is_regexp_enabled                               = $is_regexp_enabled_at_global_level;
        $this->are_regexp_permission_conflicting_at_site_level = $are_regexp_permission_conflicting_at_site_level;

        $this->branches_permissions = $branches_permissions_representation;
        $this->tags_permissions     = $tags_permissions_representation;

        $this->default_permissions_title = $GLOBALS['Language']->getText(
            'plugin_git',
            'default_permissions_title'
        );

        $this->fine_grained_permissions_title = $GLOBALS['Language']->getText(
            'plugin_git',
            'fine_grained_permissions_title'
        );

        $this->branches_title = $GLOBALS['Language']->getText(
            'plugin_git',
            'fine_grained_permissions_branches_title'
        );

        $this->tags_title = $GLOBALS['Language']->getText(
            'plugin_git',
            'fine_grained_permissions_tags_title'
        );

        $this->add_branch_permission = $GLOBALS['Language']->getText(
            'plugin_git',
            'add_branch_permission'
        );

        $this->add_tag_permission = $GLOBALS['Language']->getText(
            'plugin_git',
            'add_tag_permission'
        );

        $this->pattern_column = $GLOBALS['Language']->getText(
            'plugin_git',
            'fine_grained_permissions_pattern_column'
        );

        $this->actions_column = $GLOBALS['Language']->getText(
            'plugin_git',
            'fine_grained_permissions_actions_column'
        );

        $this->empty = $GLOBALS['Language']->getText(
            'plugin_git',
            'fine_grained_permissions_empty'
        );

        $this->new_fine_grained_ugroups = $new_fine_grained_ugroups;
        $this->remove_form_action       = $delete_url;
        $this->delete_challenge         = $csrf_delete->getToken();

        $this->remove_fine_grained_permission_delete = $GLOBALS['Language']->getText(
            'plugin_git',
            'fine_grained_remove_button'
        );

        $this->remove_fine_grained_permission_desc = $GLOBALS['Language']->getText(
            'plugin_git',
            'fine_grained_remove_desc'
        );

        $this->remove_fine_grained_permission_confirm = $GLOBALS['Language']->getText(
            'plugin_git',
            'fine_grained_remove_confirm'
        );

        $this->regexp_permission_label  = $GLOBALS['Language']->getText(
            'plugin_git',
            'regexp_permission_label'
        );
        $this->regexp_incoherence_label = $GLOBALS['Language']->getText(
            'plugin_git',
            'regexp_incoherence_label'
        );

        $this->title_warning_regexp_uncheck = $GLOBALS['Language']->getText(
            'plugin_git',
            'title_warning_regexp_uncheck'
        );

        $this->btn_cancel       = $GLOBALS['Language']->getText('global', 'btn_cancel');
        $this->save_permissions = $GLOBALS['Language']->getText('plugin_git', 'save_access_control');
        $this->is_fork          = false;
        $this->disabled         = '';
        $this->warnings         = $this->getWarningContentForRegexpDisableModal();
    }

    public function is_control_limited()
    {
        return false;
    }

    public function template_git_access_rights()
    {
        return $GLOBALS['Language']->getText('plugin_git', 'view_admin_tab_template_access_rights');
    }

    public function csrf_token()
    {
        return $this->csrf;
    }

    public function default_access_rights_form_action()
    {
        return '/plugins/git/?group_id=' . $this->project_id . '&action=admin-default-access-rights';
    }

    public function table_title()
    {
        return ucfirst($GLOBALS['Language']->getText('plugin_git', 'admin_mirroring'));
    }

    public function mirroring_title()
    {
        return $GLOBALS['Language']->getText('plugin_git', 'mirroring_title');
    }

    public function mirroring_info()
    {
        return $GLOBALS['Language']->getText('plugin_git', 'mirroring_default_info');
    }

    public function mirroring_mirror_name()
    {
        return $GLOBALS['Language']->getText('plugin_git', 'mirroring_mirror_name');
    }

    public function mirroring_mirror_url()
    {
        return $GLOBALS['Language']->getText('plugin_git', 'identifier');
    }

    public function mirroring_mirror_used()
    {
        return $GLOBALS['Language']->getText('plugin_git', 'mirroring_mirror_default_used');
    }

    public function mirroring_update_mirroring()
    {
        return $GLOBALS['Language']->getText('plugin_git', 'mirroring_update_default_mirroring');
    }

    public function left_tab_admin_settings()
    {
        return $GLOBALS['Language']->getText('plugin_git', 'admin_settings');
    }

    public function left_tab_admin_permissions()
    {
        return $GLOBALS['Language']->getText('plugin_git', 'view_repo_access_control');
    }

    public function left_tab_admin_notifications()
    {
        return $GLOBALS['Language']->getText('plugin_git', 'admin_mail');
    }

    public function label_read()
    {
        return $GLOBALS['Language']->getText('plugin_git', 'perm_R');
    }

    public function label_write()
    {
        return $GLOBALS['Language']->getText('plugin_git', 'perm_W');
    }

    public function label_rw()
    {
        return $GLOBALS['Language']->getText('plugin_git', 'perm_W+');
    }

    public function read_select_box_id()
    {
        return 'default_access_rights[' . Git::DEFAULT_PERM_READ . ']';
    }

    public function write_select_box_id()
    {
        return 'default_access_rights[' . Git::DEFAULT_PERM_WRITE . ']';
    }

    public function rewrite_select_box_id()
    {
        return 'default_access_rights[' . Git::DEFAULT_PERM_WPLUS . ']';
    }

    public function submit_default_access_rights()
    {
        return $GLOBALS['Language']->getText('plugin_git', 'admin_save_submit');
    }

    public function mirroring_href()
    {
        return "?action=admin-default-settings&group_id=$this->project_id&pane=" . IndexController::DEFAULT_SETTINGS_PANE_MIRRORING;
    }

    public function access_control_href()
    {
        return "?action=admin-default-settings&group_id=$this->project_id&pane=" . IndexController::DEFAULT_SETTINGS_PANE_ACCESS_CONTROL;
    }

    public function fine_grained_permissions_checkbox_label()
    {
        return $GLOBALS['Language']->getText(
            'plugin_git',
            'fine_grained_permissions_checkbox_label'
        );
    }

    public function has_branches_permissions()
    {
        return count($this->branches_permissions) > 0;
    }

    public function has_tags_permissions()
    {
        return count($this->tags_permissions) > 0;
    }

    private function getWarningContentForRegexpDisableModal()
    {
        if ($this->are_regexp_permission_conflicting_at_site_level) {
            $warning[]['message'] = $GLOBALS['Language']->getText(
                'plugin_git',
                'warning_conflit_regexp_configuration'
            );
            $warning[]['message'] = $GLOBALS['Language']->getText(
                'plugin_git',
                'warning_conflit_regexp_configuration_part_two'
            );
            $warning[]['message'] = $GLOBALS['Language']->getText(
                'plugin_git',
                'warning_conflit_regexp_configuration_confirm'
            );
        } else {
            $warning[]['message'] = $GLOBALS['Language']->getText(
                'plugin_git',
                'warning_regexp_uncheck'
            );
        }

        return $warning;
    }
}
