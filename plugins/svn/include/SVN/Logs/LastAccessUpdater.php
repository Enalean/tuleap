<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\SVN\Logs;

use Tuleap\SVN\Commit\CommitInfo;
use Tuleap\SVNCore\Repository;

class LastAccessUpdater
{
    /**
     * @var LastAccessDao
     */
    private $dao;

    public function __construct(LastAccessDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @throws \Tuleap\SVN\Logs\CannotGetCommitDateException
     */
    public function updateLastCommitDate(Repository $repository, CommitInfo $commit_info)
    {
        $commit_date = $this->getCommitDate($commit_info);
        $this->dao->updateLastCommitDate($repository->getId(), $commit_date->getTimestamp());
    }

    /**
     * @return \DateTime
     * @throws \Tuleap\SVN\Logs\CannotGetCommitDateException
     */
    private function getCommitDate(CommitInfo $commit_info)
    {
        $datetime = \DateTime::createFromFormat('Y-m-d H:i:s T (D, d M Y)', $commit_info->getDate());
        if ($datetime === false) {
            throw new CannotGetCommitDateException();
        }
        return $datetime;
    }
}
