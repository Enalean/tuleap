<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Git\Repository\View;

use GitRepository;

class RepositoryHeaderPresenter
{
    public $repository_path;
    public $repository_name;
    public $user_is_admin;
    public $repository_admin_url;
    /** @var GerritStatusPresenter */
    public $gerrit_status_presenter;
    /** @var null|ParentRepositoryPresenter */
    public $parent_repository_presenter;
    /** @var ClonePresenter */
    public $clone_presenter;

    /**
     * @param GitRepository                  $repository
     * @param bool                           $user_is_admin
     * @param string                         $repository_admin_url
     * @param ClonePresenter                 $clone_presenter
     * @param GerritStatusPresenter          $gerrit_status_presenter
     * @param ParentRepositoryPresenter|null $parent_repository_presenter
     */
    public function __construct(
        GitRepository $repository,
        $user_is_admin,
        $repository_admin_url,
        ClonePresenter $clone_presenter,
        GerritStatusPresenter $gerrit_status_presenter,
        ParentRepositoryPresenter $parent_repository_presenter = null
    ) {
        $this->repository_path             = $repository->getPathWithoutProject();
        $this->repository_name             = $repository->getLabel();
        $this->gerrit_status_presenter     = $gerrit_status_presenter;
        $this->user_is_admin               = $user_is_admin;
        $this->repository_admin_url        = $repository_admin_url;
        $this->parent_repository_presenter = $parent_repository_presenter;
        $this->clone_presenter             = $clone_presenter;
    }
}
