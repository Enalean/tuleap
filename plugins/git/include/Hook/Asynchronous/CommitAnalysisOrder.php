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

namespace Tuleap\Git\Hook\Asynchronous;

use Tuleap\Git\Hook\CommitHash;

final class CommitAnalysisOrder
{
    private function __construct(private CommitHash $commit_hash, private \PFUser $pusher, private \Project $project)
    {
    }

    public static function fromComponents(CommitHash $commit_hash, \PFUser $pusher, \Project $project): self
    {
        return new self($commit_hash, $pusher, $project);
    }

    public function getCommitHash(): CommitHash
    {
        return $this->commit_hash;
    }

    public function getPusher(): \PFUser
    {
        return $this->pusher;
    }

    public function getProject(): \Project
    {
        return $this->project;
    }
}
