<?php
/**
 * Copyright (c) Enalean, 2014 - 2015. All rights reserved
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

class GitPresenters_AdminMassUpdatePresenter extends GitPresenters_AdminPresenter {

    /**
     * @var string
     */
    public $csrf_input;

    /**
     * @var array
     */
    public $repositories;

    /**
     * @var GitPresenters_MirroringPresenter
     */
    public $mirroring_presenter;

    public function __construct(
        CSRFSynchronizerToken $csrf,
        $project_id,
        array $repositories,
        GitPresenters_AdminMassUdpdateMirroringPresenter $mirroring_presenter
    ) {
        $allow_mass_update = true;
        parent::__construct($project_id, $allow_mass_update);

        $this->csrf_input          = $csrf->fetchHTMLInput();
        $this->manage_mass_update  = true;
        $this->repositories        = $repositories;
        $this->mirroring_presenter = $mirroring_presenter;
    }

    public function title() {
        return $GLOBALS['Language']->getText('plugin_git', 'view_admin_mass_update_title');
    }

    public function has_more_than_one_repository() {
        return count($this->repositories) > 1;
    }

    public function info_mass_update() {
        $nb_selected_repositories = count($this->repositories);
        if ($nb_selected_repositories > 1) {
            return $GLOBALS['Language']->getText('plugin_git', 'view_admin_mass_update_selected_repositories', $nb_selected_repositories);
        }

        $repository = $this->repositories[0];

        return $GLOBALS['Language']->getText('plugin_git', 'view_admin_mass_update_selected_repository', $repository->name);
    }

    public function submit_mass_change() {
        return $GLOBALS['Language']->getText('plugin_git', 'view_admin_mass_update_submit_mass_change');
    }

    public function form_action() {
        return '/plugins/git/?group_id='. $this->project_id .'&action=admin-mass-update';
    }
}
