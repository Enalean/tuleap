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

class GitPresenters_AdminDefaultSettingsPresenter extends GitPresenters_AdminPresenter {

    public $mirror_presenters;

    public function __construct($project_id, $are_mirrors_defined, array $mirror_presenters) {
        parent::__construct($project_id, $are_mirrors_defined);

        $this->manage_default_settings = true;
        $this->mirror_presenters       = $mirror_presenters;
    }

    public function table_title() {
        return ucfirst($GLOBALS['Language']->getText('plugin_git', 'admin_mirroring'));
    }

    public function mirroring_title() {
        return $GLOBALS['Language']->getText('plugin_git', 'mirroring_title');
    }

    public function mirroring_info() {
        return $GLOBALS['Language']->getText('plugin_git', 'mirroring_default_info');
    }

    public function mirroring_mirror_name() {
        return $GLOBALS['Language']->getText('plugin_git', 'mirroring_mirror_name');
    }

    public function mirroring_mirror_url() {
        return $GLOBALS['Language']->getText('plugin_git', 'identifier');
    }

    public function mirroring_mirror_used() {
        return $GLOBALS['Language']->getText('plugin_git', 'mirroring_mirror_default_used');
    }

    public function mirroring_update_mirroring() {
        return $GLOBALS['Language']->getText('plugin_git', 'mirroring_update_default_mirroring');
    }

    public function left_tab_admin_settings() {
        return $GLOBALS['Language']->getText('plugin_git', 'admin_settings');
    }

    public function left_tab_admin_permissions() {
        return $GLOBALS['Language']->getText('plugin_git', 'view_repo_access_control');
    }

    public function left_tab_admin_notifications() {
        return $GLOBALS['Language']->getText('plugin_git', 'admin_mail');
    }
}