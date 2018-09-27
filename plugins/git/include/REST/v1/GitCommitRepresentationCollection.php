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

namespace Tuleap\Git\REST\v1;

use Tuleap\Git\GitPHP\Commit;

class GitCommitRepresentationCollection
{
    /**
     * @var GitCommitRepresentation[]
     */
    private $representations_indexed_by_commit_reference = [];

    public function __construct(GitCommitRepresentation ...$representations)
    {
        foreach ($representations as $representation) {
            $this->representations_indexed_by_commit_reference[$representation->id] = $representation;
        }
    }

    /**
     * @return GitCommitRepresentation
     */
    public function getRepresentation(Commit $commit)
    {
        if (! isset($this->representations_indexed_by_commit_reference[$commit->GetHash()])) {
            throw new \UnexpectedValueException('Commit not found in the set of representations');
        }
        return $this->representations_indexed_by_commit_reference[$commit->GetHash()];
    }

    /**
     * @return GitCommitRepresentation[]
     */
    public function getWholeCollectionAsArray()
    {
        return array_values($this->representations_indexed_by_commit_reference);
    }
}
