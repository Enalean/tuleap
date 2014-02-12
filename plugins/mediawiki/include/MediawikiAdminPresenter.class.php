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

    public function __construct(Project $project) {
        $this->project = $project;
    }

    public function title() {
        return 'Mediawiki groups mapping';
    }
    public function help() {
        return 'help';
    }
    public function route() {
        return MEDIAWIKI_BASE_URL . '/forge_admin?' . http_build_query(array(
            'group_id' => $this->project->getID(),
            'action'   => 'save'
        ));
    }
    public function groups_permissions() {
        $ugroup_manager = new UGroupManager();
        $ugroups = $ugroup_manager->getUGroups($this->project, array_merge(UGroup::$legacy_ugroups, array(UGroup::NONE)));
        return array(
            new MediawikiGroupPresenter('anonymous', 'Anonymous', $ugroups),
            new MediawikiGroupPresenter('user', 'User / Autoconfirmed / Email confirmed', $ugroups),
            new MediawikiGroupPresenter('bot', 'Bot', $ugroups),
            new MediawikiGroupPresenter('sysop', 'Sysop', $ugroups),
            new MediawikiGroupPresenter('bureaucrat', 'Bureaucrat', $ugroups),
        );
    }
    public function submit_label() {
        return $GLOBALS['Language']->getText('global', 'btn_update');
    }
    public function reset_label() {
        return $GLOBALS['Language']->getText('global', 'btn_cancel');
    }
}