<?php
/**
 * Copyright (c) Enalean, 2014. All rights reserved
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

require_once 'MediawikiGroupPresenter.class.php';

class MediawikiAdminPresenter {

    private $project;
    private $groups_permissions;

    public function __construct(Project $project, array $groups_permissions) {
        $this->project = $project;
        $this->groups_permissions = $groups_permissions;
    }

    public function title() {
        return $GLOBALS['Language']->getText('plugin_mediawiki', 'group_mapping_title');
    }

    public function help_intro() {
        return $GLOBALS['Language']->getText('plugin_mediawiki', 'group_mapping_help_intro', Config::get('sys_name'));
    }

    public function help_link() {
        return $GLOBALS['Language']->getText('plugin_mediawiki', 'group_mapping_help_link', $this->getMWUrl('Special:ListGroupRights'));
    }

    private function getMWUrl($page) {
        return MEDIAWIKI_BASE_URL . '/wiki/' . $this->project->getUnixName(). '/index.php/' . $page;
    }

    public function route() {
        return MEDIAWIKI_BASE_URL . '/forge_admin?' . http_build_query(array(
            'group_id' => $this->project->getID(),
            'action'   => 'save'
        ));
    }

    public function groups_permissions() {
        return $this->groups_permissions;
    }

    public function submit_label() {
        return $GLOBALS['Language']->getText('global', 'btn_update');
    }

    public function reset_label() {
        return $GLOBALS['Language']->getText('global', 'btn_cancel');
    }
}