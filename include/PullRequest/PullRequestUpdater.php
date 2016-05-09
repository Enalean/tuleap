<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

use \GitRepository;

class PullRequestUpdater
{

    /**
     * @var Factory
     */
    private $pull_request_factory;

    public function __construct(Factory $pull_request_factory)
    {
        $this->pull_request_factory = $pull_request_factory;
    }

    public function updatePullRequests(GitRepository $repository, $src_branch_name, $new_rev)
    {
        $prs = $this->pull_request_factory->getPullRequestsBySourceBranch($repository, $src_branch_name);
        foreach ($prs as $pr) {
            $this->pull_request_factory->updateSourceRev($pr, $new_rev);
        }
    }

}
