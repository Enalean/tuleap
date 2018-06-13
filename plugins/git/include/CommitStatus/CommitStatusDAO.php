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

namespace Tuleap\Git\CommitStatus;

use Tuleap\DB\DataAccessObject;

class CommitStatusDAO extends DataAccessObject
{
    public function create($repository_id, $commit_reference, $status, $date)
    {
        $this->getDB()->insert(
            'plugin_git_commit_status',
            [
                'repository_id'    => $repository_id,
                'commit_reference' => $commit_reference,
                'status'           => $status,
                'date'             => $date
            ]
        );
    }

    public function getLastCommitStatusByRepositoryIdAndCommitReference($repository_id, $commit_reference)
    {
        $sql = 'SELECT status, date
                FROM plugin_git_commit_status
                WHERE repository_id = ? AND commit_reference = ?
                ORDER BY id DESC
                LIMIT 1';

        return $this->getDB()->row($sql, $repository_id, $commit_reference);
    }
}
