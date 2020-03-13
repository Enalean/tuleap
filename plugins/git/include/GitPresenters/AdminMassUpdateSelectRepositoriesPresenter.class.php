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

class GitPresenters_AdminMassUpdateSelectRepositoriesPresenter extends GitPresenters_AdminPresenter
{

    /**
     * @var CSRFSynchronizerToken
     */
    public $csrf_token;

    /**
     * @var array
     */
    public $repositories;

    public function __construct(
        CSRFSynchronizerToken $csrf,
        $project_id,
        array $external_pane_presenters,
        array $repositories
    ) {
        $are_mirrors_defined = true;
        parent::__construct($project_id, $are_mirrors_defined, $external_pane_presenters);

        $this->csrf_token   = $csrf;
        $this->repositories = $repositories;
    }

    public function title()
    {
        return dgettext('tuleap-git', 'Mass update of repositories');
    }

    public function select_repositories()
    {
        return dgettext('tuleap-git', 'Select the repositories you want to update. You will be able to apply mass changes on the next page.');
    }

    public function repository_list_name()
    {
        return dgettext('tuleap-git', 'Repository name');
    }

    public function mass_change()
    {
        return dgettext('tuleap-git', 'Update selected repositories');
    }

    public function form_action()
    {
        return '/plugins/git/?group_id=' . $this->project_id . '&action=admin-mass-update';
    }
}
