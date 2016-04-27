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

class PullRequestFileRepresentationFactory
{

    /**
     * @var GitExec;
     */
    private $executor;

    public function __construct(GitExec $executor)
    {
        $this->executor = $executor;
    }

    /**
     * @throws UnknownReferenceException
     */
    public function getModifiedFilesRepresentations(PullRequest $pull_request)
    {
        $x_files = array();

        $modified_files = $this->executor->getModifiedFiles($pull_request->getSha1Src(), $pull_request->getSha1Dest());

        foreach ($modified_files as $file) {
            $impacted_file = preg_split("/[\t]/", $file);
            $file_representation = new PullRequestFileRepresentation();
            $file_representation->build($impacted_file[1], $impacted_file[0]);
            $x_files[] = $file_representation;
        }

        return $x_files;
    }
}
