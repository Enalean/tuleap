<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\PullRequest\BranchUpdate;

use Git_GitRepositoryUrlManager;
use GitRepository;

class RepositoryURLToCommitBuilder
{
    /**
     * @var Git_GitRepositoryUrlManager
     */
    private $repository_url_manager;
    /**
     * @var GitRepository
     */
    private $git_repository;

    public function __construct(Git_GitRepositoryUrlManager $repository_url_manager, GitRepository $git_repository)
    {
        $this->repository_url_manager = $repository_url_manager;
        $this->git_repository         = $git_repository;
    }

    public function buildURLForReference(string $commit_reference): string
    {
        return $this->repository_url_manager->getAbsoluteCommitURL($this->git_repository, $commit_reference);
    }
}
