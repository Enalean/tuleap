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

namespace Tuleap\Git\CommitMetadata;

use Tuleap\Git\CommitStatus\CommitStatus;

class CommitMetadata
{
    /**
     * @var CommitStatus
     */
    private $last_commit_status;
    /**
     * @var null|\PFUser
     */
    private $author;

    public function __construct(CommitStatus $last_commit_status, \PFUser $author = null)
    {
        $this->last_commit_status = $last_commit_status;
        $this->author             = $author;
    }

    /**
     * @return CommitStatus
     */
    public function getCommitStatus()
    {
        return $this->last_commit_status;
    }

    /**
     * @return null|\PFUser
     */
    public function getAuthor()
    {
        return $this->author;
    }
}
