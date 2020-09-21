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
use PFUser;

class RepositoryHeaderPresenter
{
    public $repository_id;
    public $repository_name;
    public $user_is_admin;
    public $repository_admin_url;
    /** @var GerritStatusPresenter */
    public $gerrit_status_presenter;
    /** @var null|ParentRepositoryPresenter */
    public $parent_repository_presenter;
    /** @var ClonePresenter */
    public $clone_presenter;
    public $project_id;
    public $is_migrated_to_gerrit;
    public $fork_url;
    /** @var bool */
    public $user_is_anonymous;
    /**  @var NavigationTabsPresenter[] */
    public $navigation_tabs_presenters;
    /** @var ForkedRepositoryPresenter[] */
    public $forked_repositories_presenters;
    /** @var bool */
    public $is_already_forked;
    /** @var bool */
    public $has_only_one_fork;
    /** @var bool */
    public $is_scope_project;
    /** @var bool */
    public $is_user_project_member;

    public function __construct(
        GitRepository $repository,
        $user_is_admin,
        $repository_admin_url,
        $fork_url,
        PFUser $user,
        ClonePresenter $clone_presenter,
        GerritStatusPresenter $gerrit_status_presenter,
        array $forked_repositories_presenters,
        array $navigation_tabs_presenters,
        ?ParentRepositoryPresenter $parent_repository_presenter = null
    ) {
        $this->project_id                     = $repository->getProjectId();
        $this->repository_id                  = $repository->getId();
        $this->repository_name                = $repository->getLabel();
        $this->gerrit_status_presenter        = $gerrit_status_presenter;
        $this->user_is_admin                  = $user_is_admin;
        $this->repository_admin_url           = $repository_admin_url;
        $this->parent_repository_presenter    = $parent_repository_presenter;
        $this->clone_presenter                = $clone_presenter;
        $this->is_migrated_to_gerrit          = $repository->isMigratedToGerrit() ? "1" : "0";
        $this->fork_url                       = $fork_url;
        $this->user_is_anonymous              = $user->isAnonymous();
        $this->navigation_tabs_presenters     = $navigation_tabs_presenters;
        $this->forked_repositories_presenters = $forked_repositories_presenters;
        $this->is_already_forked              = count($forked_repositories_presenters) > 0;
        $this->has_only_one_fork              = count($forked_repositories_presenters) === 1;
        $this->is_scope_project               = $repository->getScope() === GitRepository::REPO_SCOPE_PROJECT;
        $this->is_user_project_member         = $user->isMember($this->project_id);
    }
}
