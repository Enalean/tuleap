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

use Git_GitRepositoryUrlManager;
use GitPermissionsManager;
use GitRepository;
use PFUser;

class RepositoryHeaderPresenterBuilder
{
    /**
     * @var Git_GitRepositoryUrlManager
     */
    private $url_manager;

    /**
     * @var GitPermissionsManager
     */
    private $permissions_manager;

    public function __construct(
        Git_GitRepositoryUrlManager $url_manager,
        GitPermissionsManager $permissions_manager
    ) {
        $this->url_manager         = $url_manager;
        $this->permissions_manager = $permissions_manager;
    }

    /**
     * @param GitRepository $repository
     * @param PFUser        $current_user
     * @return RepositoryHeaderPresenter
     */
    public function build(GitRepository $repository, PFUser $current_user)
    {
        $is_admin = $this->permissions_manager->userIsGitAdmin($current_user, $repository->getProject()) ||
            $repository->belongsTo($current_user);

        $admin_url = $this->url_manager->getRepositoryAdminUrl($repository);

        return new RepositoryHeaderPresenter(
            $repository,
            $is_admin,
            $admin_url
        );
    }
}
