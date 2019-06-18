<?php
/**
 * Copyright (c) Enalean, 2015 - Presenter. All rights reserved
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
class GitPresenters_AccessControlPresenter
{
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

    public $label_read;

    public $label_write;

    public $label_rw;

    public $is_control_limited;

    public $limited_control_notice;

    public $read_select_box_id;

    public $write_select_box_id;

    public $rewrite_select_box_id;

    public $read_options;

    public $write_options;

    public $rewrite_options;

    public $default_permissions_title;

    public $fine_grained_permissions_title;

    public $are_fine_grained_permissions_defined;
    public $can_use_fine_grained_permissions;
    public $remove_fine_grained_permission_delete;
    public $remove_fine_grained_permission_desc;
    public $remove_fine_grained_permission_confirm;
    public $btn_cancel;
    public $remove_form_action;
    public $disabled;
    public $are_regexp_permission_activated;
    public $regexp_permission_label;
    public $regexp_incoherence_label;
    public $is_regexp_enabled;
    public $warnings;
    public $fine_grained_permissions_fork_warning;

    public function __construct(
        $is_control_limited,
        $read_select_box_id,
        $write_select_box_id,
        $rewrite_select_box_id,
        $read_options,
        $write_options,
        $rewrite_options,
        $are_fine_grained_permissions_defined,
        $can_use_fine_grained_permissions,
        array $branches_permissions_representation,
        array $tags_permissions_representation,
        array $new_fine_grained_ugroups,
        $delete_url,
        CSRFSynchronizerToken $csrf,
        $is_fork,
        $are_regexp_permission_activated_at_site_level,
        $is_regexp_enabled,
        $warnings
    ) {
        $this->is_control_limited     = $is_control_limited;
        $this->limited_control_notice = sprintf(dgettext('tuleap-git', 'You can only change <strong>READ</strong> permissions since this repository is managed by a remote Gerrit server. Please note that those modifications are local to %1$s <strong>ARE NOT propagated to Gerrit</strong>.'), \ForgeConfig::get('sys_name'));

        $this->label_read  = dgettext('tuleap-git', 'Read');
        $this->label_write = dgettext('tuleap-git', 'Write');
        $this->label_rw    = dgettext('tuleap-git', 'Rewind');

        $this->read_select_box_id    = $read_select_box_id;
        $this->write_select_box_id   = $write_select_box_id;
        $this->rewrite_select_box_id = $rewrite_select_box_id;

        $this->read_options    = $read_options;
        $this->write_options   = $write_options;
        $this->rewrite_options = $rewrite_options;

        $this->are_fine_grained_permissions_defined = $are_fine_grained_permissions_defined;
        $this->can_use_fine_grained_permissions     = $can_use_fine_grained_permissions;
        $this->cannot_define_per_repo_permissions   = ($is_control_limited || $are_fine_grained_permissions_defined);
        $this->is_fork                              = $is_fork;

        $this->fine_grained_permissions_checkbox_label = dgettext('tuleap-git', 'Enable fine-grained permissions');

        $this->are_regexp_permission_activated_at_site_level = $are_regexp_permission_activated_at_site_level;
        $this->is_regexp_enabled                             = $is_regexp_enabled;
        $this->regexp_permission_label                       = dgettext('tuleap-git', 'Enable regular expressions in branches and tags pattern');
        $this->regexp_incoherence_label                      = dgettext('tuleap-git', 'Enabling this option might end up in non working state. Invalid regular expressions will be ignored without errors.');

        $this->fine_grained_permissions_fork_warning .= dgettext('tuleap-git', 'Please note that when forking a single repository to another project, user-defined static user groups will not be copied.');

        $this->branches_permissions = $branches_permissions_representation;
        $this->tags_permissions     = $tags_permissions_representation;

        $this->default_permissions_title = dgettext('tuleap-git', 'Default permissions');

        $this->fine_grained_permissions_title = dgettext('tuleap-git', 'Fine-grained permissions');

        $this->branches_title = dgettext('tuleap-git', 'Branches');

        $this->tags_title = dgettext('tuleap-git', 'Tags');

        $this->add_branch_permission = dgettext('tuleap-git', 'Add permission on a new branch');

        $this->add_tag_permission = dgettext('tuleap-git', 'Add permission on a new tag');

        $this->pattern_column = dgettext('tuleap-git', 'Pattern');

        $this->actions_column = dgettext('tuleap-git', 'Actions');

        $this->empty = dgettext('tuleap-git', 'No permission defined yet.');

        $this->new_fine_grained_ugroups = $new_fine_grained_ugroups;
        $this->remove_form_action       = $delete_url;
        $this->delete_challenge         = $csrf->getToken();

        $this->remove_fine_grained_permission_delete = dgettext('tuleap-git', 'Delete');

        $this->remove_fine_grained_permission_desc = dgettext('tuleap-git', 'You are about to remove this permission. Please confirm your action.');

        $this->remove_fine_grained_permission_confirm = dgettext('tuleap-git', 'Confirm deletion');

        $this->title_warning_regexp_uncheck = dgettext('tuleap-git', 'Some rules may be deleted');
        $this->warnings = $warnings;

        $this->btn_cancel       = $GLOBALS['Language']->getText('global', 'btn_cancel');
        $this->save_permissions = dgettext('tuleap-git', 'Save permissions');
        $this->disabled         = '';
        if ($is_fork) {
            $this->disabled = 'disabled="disabled"';
        }
    }

    public function has_branches_permissions()
    {
        return count($this->branches_permissions) > 0;
    }

    public function has_tags_permissions()
    {
        return count($this->tags_permissions) > 0;
    }
}
