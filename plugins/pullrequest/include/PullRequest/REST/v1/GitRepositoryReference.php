<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\REST\v1;

use GitRepository;
use Tuleap\Git\Gitolite\GenerateGitoliteAccessURL;
use Tuleap\Project\REST\ProjectReference;

class GitRepositoryReference extends \Tuleap\Git\REST\v1\GitRepositoryReference
{
    /** @var string */
    public $name;
    /** @var ProjectReference */
    public $project;
    /**
     * @var string
     */
    public $clone_http_url;
    /**
     * @var string
     */
    public $clone_ssh_url;

    public function __construct(private readonly GenerateGitoliteAccessURL $gitolite_access_URL_generator)
    {
    }

    public function build(GitRepository $repository)
    {
        parent::build($repository);
        $this->name    = $repository->getFullName();
        $this->project = new ProjectReference($repository->getProject());

        $this->clone_http_url = $this->gitolite_access_URL_generator->getHTTPURL($repository) ?: null;
        $this->clone_ssh_url  = $this->gitolite_access_URL_generator->getSSHURL($repository) ?: null;
    }
}
