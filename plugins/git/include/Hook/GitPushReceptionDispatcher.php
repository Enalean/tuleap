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

use Tuleap\Git\Hook\Asynchronous\BuildCommitAnalysisProcessor;
use Tuleap\Git\Hook\Asynchronous\CommitAnalysisOrder;

final class GitPushReceptionDispatcher implements DispatchGitPushReception
{
    public function __construct(private BuildCommitAnalysisProcessor $builder)
    {
    }

    public function dispatchGitPushReception(PushDetails $details): void
    {
        $pusher     = $details->getUser();
        $repository = $details->getRepository();
        $project    = $repository->getProject();
        $processor  = $this->builder->getProcessor($repository);
        foreach ($details->getRevisionList() as $commit_sha1) {
            $processor->process(
                CommitAnalysisOrder::fromComponents(
                    CommitHash::fromString($commit_sha1),
                    $pusher,
                    $repository,
                    $project
                )
            );
        }
    }
}
