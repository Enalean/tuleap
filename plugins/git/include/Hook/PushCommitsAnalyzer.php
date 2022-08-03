<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Git\Hook;

use Tuleap\Git\Hook\Asynchronous\CommitAnalysisOrder;
use Tuleap\Git\Repository\Settings\ArtifactClosure\VerifyArtifactClosureIsAllowed;

final class PushCommitsAnalyzer
{
    public function __construct(
        private VerifyArtifactClosureIsAllowed $closure_verifier,
        private VerifyIsDefaultBranch $default_branch_verifier,
    ) {
    }

    /**
     * @return list<CommitAnalysisOrder>
     */
    public function analyzePushCommits(PushDetails $details): array
    {
        $repository = $details->getRepository();

        if (! $this->closure_verifier->isArtifactClosureAllowed((int) $repository->getId())) {
            return [];
        }

        if (! $this->default_branch_verifier->isDefaultBranch($details->getRefname())) {
            return [];
        }

        $pusher = $details->getUser();
        return array_map(
            static fn(string $commit_sha1) => CommitAnalysisOrder::fromComponents(
                CommitHash::fromString($commit_sha1),
                $pusher,
                $repository
            ),
            $details->getRevisionList()
        );
    }
}
