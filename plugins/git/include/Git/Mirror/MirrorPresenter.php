<?php
/**
 * Copyright (c) Enalean SAS, 2016. All Rights Reserved.
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

namespace Tuleap\Git\Mirror;

use Codendi_HTMLPurifier;
use Git_Mirror_Mirror;

class MirrorPresenter
{
    public $id;
    public $url;
    public $hostname;
    public $name;
    public $owner_id;
    public $owner_name;
    public $ssh_key_value;
    public $ssh_key_ellipsis_value;
    public $number_of_repositories;
    public $delete_title;
    public $purified_delete_desc;
    public $has_repositories;
    public $already_used;
    public $repos_title;
    public $edit_title;

    /** @var Git_AdminRepositoryListForProjectPresenter[] */
    public $repository_list_for_projects;

    public function __construct(Git_Mirror_Mirror $mirror, array $repository_list_for_projects)
    {
        $this->id                     = $mirror->id;
        $this->url                    = $mirror->url;
        $this->hostname               = $mirror->hostname;
        $this->name                   = $mirror->name;
        $this->owner_id               = $mirror->owner_id;
        $this->owner_name             = $mirror->owner_name;
        $this->ssh_key_value          = $mirror->ssh_key;
        $this->ssh_key_ellipsis_value = mb_substr($mirror->ssh_key, 0, 40) . '...' . mb_substr($mirror->ssh_key, -40);

        $nb_repositories        = $this->getNbOfRepositories($repository_list_for_projects);
        $this->has_repositories = $nb_repositories > 0;

        $this->number_of_repositories = sprintf(dgettext('tuleap-git', '%1$s repositories'), $nb_repositories);

        $this->repos_title          = sprintf(dgettext('tuleap-git', 'Repositories on %1$s mirror'), $mirror->name);
        $this->edit_title           = sprintf(dgettext('tuleap-git', 'Edit %1$s mirror'), $mirror->name);
        $this->delete_title         = sprintf(dgettext('tuleap-git', 'Delete %1$s mirror'), $mirror->name);
        $this->purified_delete_desc = Codendi_HTMLPurifier::instance()->purify(
            sprintf(dgettext('tuleap-git', 'Wow, wait a minute. You are about to delete the <b>%1$s</b> mirror. Please confirm your action.'), $mirror->name),
            CODENDI_PURIFIER_LIGHT
        );

        $this->already_used = dgettext('tuleap-git', 'This mirror is used by repositories, you shall not delete it.');

        $this->repository_list_for_projects = $repository_list_for_projects;
    }

    private function getNbOfRepositories(array $repository_list_for_projects)
    {
        return array_reduce(
            $repository_list_for_projects,
            function ($nb, $repositories_for_one_project) {
                return $nb + count($repositories_for_one_project->repositories);
            },
            0
        );
    }
}
