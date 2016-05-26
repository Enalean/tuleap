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

namespace Tuleap\PullRequest\REST\v1;

use Tuleap\PullRequest\GitExec;
use Tuleap\PullRequest\PullRequest;

class PullRequestRepresentationFactory
{

    /**
     * @var GitExec;
     */
    private $executor;

    public function __construct(GitExec $executor)
    {
        $this->executor = $executor;
    }

    public function getPullRequestRepresentation($pull_request, $repository_src, $repository_dest, $user)
    {
        $short_stat        = $this->executor->getShortStat($pull_request->getSha1Dest(), $pull_request->getSha1Src());
        $short_stat_repres = new PullRequestShortStatRepresentation();
        $short_stat_repres->build($short_stat);

        $user_can_merge   = $repository_dest->userCanWrite($user);
        $user_can_abandon = ($repository_dest->userCanWrite($user) || $repository_src->userCanWrite($user));

        $pull_request_representation = new PullRequestRepresentation();
        $pull_request_representation->build(
            $pull_request,
            $repository_src,
            $repository_dest,
            $user_can_merge,
            $user_can_abandon,
            $short_stat_repres);

        return $pull_request_representation;
    }
}
