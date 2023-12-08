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

use Tuleap\SVNCore\Repository;

class LastAccessDao extends \DataAccessObject
{
    public function __construct()
    {
        parent::__construct();
        $this->enableExceptionsOnError();
    }

    public function updateLastCommitDate($repository_id, $date): void
    {
        $repository_id = $this->da->escapeInt($repository_id);
        $date          = $this->da->escapeInt($date);

        $sql = "INSERT INTO plugin_svn_last_access(repository_id, commit_date)
                VALUES ($repository_id, $date)
                ON DUPLICATE KEY UPDATE commit_date = $date";

        $this->update($sql);
    }

    public function importCoreLastCommitDate(Repository $repository): void
    {
        $sql = sprintf('SELECT date FROM svn_commits WHERE group_id = %d ORDER BY date DESC LIMIT 1', $this->da->escapeInt($repository->getProject()->getID()));
        $dar = $this->retrieve($sql);
        if ($dar && count($dar) === 1) {
            $row = $dar->getRow();
            $this->updateLastCommitDate($repository->getId(), $row['date']);
        }
    }
}
