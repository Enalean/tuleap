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

use ParagonIE\EasyDB\EasyStatement;
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

    public function getLastCommitStatusByRepositoryIdAndCommitReferences($repository_id, array $commit_references)
    {
        if (empty($commit_references)) {
            return [];
        }
        $commit_references_in_condition = EasyStatement::open()->in('?*', $commit_references);

        $sql = "SELECT commit_status1.commit_reference, commit_status1.status, commit_status1.date
                FROM plugin_git_commit_status AS commit_status1
                LEFT JOIN plugin_git_commit_status AS commit_status2 ON (
                  commit_status1.repository_id = commit_status2.repository_id AND
                  commit_status1.commit_reference = commit_status2.commit_reference AND
                  commit_status1.id < commit_status2.id
                )
                WHERE
                  commit_status1.repository_id = ? AND
                  commit_status1.commit_reference IN ($commit_references_in_condition) AND
                  commit_status2.id IS NULL";

        return $this->getDB()->safeQuery($sql, array_merge([$repository_id], $commit_references_in_condition->values()));
    }
}
