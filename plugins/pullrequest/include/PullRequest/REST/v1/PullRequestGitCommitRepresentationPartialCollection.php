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

namespace Tuleap\PullRequest\REST\v1;

use Tuleap\Git\REST\v1\GitCommitRepresentation;

class PullRequestGitCommitRepresentationPartialCollection
{
    /**
     * @var GitCommitRepresentation[]
     */
    private $commits_collection = [];
    /**
     * @var int
     */
    private $total_size;

    /**
     * @param GitCommitRepresentation[] $commits_representations
     * @param int      $total_size
     */
    public function __construct(array $commits_representations, $total_size)
    {
        $this->commits_collection = $commits_representations;
        $this->total_size         = $total_size;
    }

    /**
     * @return GitCommitRepresentation[]
     */
    public function getCommitsCollection()
    {
        return $this->commits_collection;
    }

    public function getSize()
    {
        return $this->total_size;
    }
}
