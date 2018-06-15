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

namespace Tuleap\PullRequest;

use Tuleap\PullRequest\GitReference\GitPullRequestReference;

class PullRequestWithGitReference
{
    /**
     * @var PullRequest
     */
    private $pull_request;
    /**
     * @var GitPullRequestReference
     */
    private $git_reference;

    public function __construct(PullRequest $pull_request, GitPullRequestReference $git_reference)
    {
        $this->pull_request  = $pull_request;
        $this->git_reference = $git_reference;
    }

    /**
     * @return PullRequest
     */
    public function getPullRequest()
    {
        return $this->pull_request;
    }

    /**
     * @return GitPullRequestReference
     */
    public function getGitReference()
    {
        return $this->git_reference;
    }
}
