<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Git\Repository;

use GitPermissionsManager;
use GitRepoNotFoundException;
use GitRepository;
use GitRepositoryFactory;
use HTTPRequest;
use PFUser;
use Tuleap\Git\Repository\Settings\UserCannotAdministrateRepositoryException;
use Valid_UInt;

class RepositoryFromRequestRetriever
{
    /**
     * @var GitRepositoryFactory
     */
    private $factory;
    /**
     * @var GitPermissionsManager
     */
    private $permissions_manager;

    public function __construct(
        GitRepositoryFactory $factory,
        GitPermissionsManager $permissions_manager
    ) {
        $this->factory             = $factory;
        $this->permissions_manager = $permissions_manager;
    }

    /**
     * @return GitRepository
     */
    public function getRepositoryUserCanAdministrate(HTTPRequest $request)
    {
        $repository = null;

        $valid = new Valid_UInt('repo_id');
        $valid->required();
        if ($request->valid($valid)) {
            $repo_id = $request->get('repo_id');
        } else {
            $repo_id = 0;
        }

        if ($repo_id !== 0) {
            $repository = $this->factory->getRepositoryById($repo_id);
        }

        if (empty($repository)) {
            throw new GitRepoNotFoundException();
        }

        $user = $request->getCurrentUser();
        $this->checkUserCanAdministrateRepository($user, $repository);

        return $repository;
    }

    private function checkUserCanAdministrateRepository(PFUser $user, GitRepository $repository)
    {
        if ($this->permissions_manager->userIsGitAdmin($user, $repository->getProject())) {
            return;
        }

        if ($repository->userCanRead($user) && $repository->belongsTo($user)) {
            return;
        }

        throw new UserCannotAdministrateRepositoryException();
    }
}
