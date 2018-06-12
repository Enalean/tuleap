<?php
/**
 * Copyright (c) Enalean, 2016-2018. All Rights Reserved.
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

use Tuleap\PullRequest\Authorization\AccessControlVerifier;
use Tuleap\PullRequest\GitExec;
use Tuleap\PullRequest\PullRequest;

class PullRequestRepresentationFactory
{
    /**
     * @var AccessControlVerifier
     */
    private $access_control_verifier;

    public function __construct(AccessControlVerifier $access_control_verifier)
    {
        $this->access_control_verifier = $access_control_verifier;
    }

    public function getPullRequestRepresentation(
        PullRequest $pull_request,
        \GitRepository $repository_src,
        \GitRepository $repository_dest,
        GitExec $executor_repository_destination,
        \PFUser $user
    ) {
        $short_stat        = $executor_repository_destination->getShortStat(
            $pull_request->getSha1Dest(),
            $pull_request->getSha1Src()
        );
        $short_stat_repres = new PullRequestShortStatRepresentation();
        $short_stat_repres->build($short_stat);

        $user_can_merge   = $this->access_control_verifier->canWrite($user, $repository_dest, $pull_request->getBranchDest());
        $user_can_abandon = $user_can_merge ||
            $this->access_control_verifier->canWrite($user, $repository_src, $pull_request->getBranchSrc());

        $user_can_update_labels = $user_can_merge;

        $pull_request_representation = new PullRequestRepresentation();
        $pull_request_representation->build(
            $pull_request,
            $repository_src,
            $repository_dest,
            $user_can_merge,
            $user_can_abandon,
            $user_can_update_labels,
            $short_stat_repres
        );

        return $pull_request_representation;
    }
}
