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

namespace Tuleap\PullRequest\GitReference;

use Tuleap\DB\DataAccessObject;

class GitPullRequestReferenceDAO extends DataAccessObject
{
    public function createGitReferenceForPullRequest($pull_request_id, $status)
    {
        $repository_dest_id = $this->getRepositoryIdFromPullRequest($pull_request_id);
        $this->getDB()->beginTransaction();

        try {
            $reference_id = $this->getNextAvailableReferenceIdForRepository($repository_dest_id);
            $this->getDB()->insert(
                'plugin_pullrequest_git_reference',
                [
                    'repository_dest_id' => $repository_dest_id,
                    'reference_id'       => $reference_id,
                    'pr_id'              => $pull_request_id,
                    'status'             => $status
                ]
            );
            $this->getDB()->commit();
        } catch (\PDOException $ex) {
            $this->getDB()->rollBack();
            throw $ex;
        }

        return $reference_id;
    }

    public function updateGitReferenceToNextAvailableOne($pull_request_id)
    {
        $repository_dest_id = $this->getRepositoryIdFromPullRequest($pull_request_id);
        $this->getDB()->beginTransaction();

        try {
            $reference_id = $this->getNextAvailableReferenceIdForRepository($repository_dest_id);
            $this->getDB()->run(
                'UPDATE plugin_pullrequest_git_reference SET reference_id = ? WHERE pr_id = ?',
                $reference_id,
                $pull_request_id
            );
            $this->getDB()->commit();
        } catch (\PDOException $ex) {
            $this->getDB()->rollBack();
            throw $ex;
        }

        return $reference_id;
    }

    public function updateStatusByPullRequestId($pull_request_id, $status)
    {
        $this->getDB()->run(
            'UPDATE plugin_pullrequest_git_reference SET status = ? WHERE pr_id = ?',
            $status,
            $pull_request_id
        );
    }

    /**
     * @return int
     */
    private function getRepositoryIdFromPullRequest($pull_request_id)
    {
        return $this->getDB()->single(
            'SELECT repo_dest_id FROM plugin_pullrequest_review WHERE id = ?',
            [$pull_request_id]
        );
    }

    /**
     * @return int
     */
    private function getNextAvailableReferenceIdForRepository($repository_id)
    {
        $sql = 'SELECT IFNULL(MAX(reference_id), 0)+1 AS next_reference_id
                FROM plugin_pullrequest_git_reference
                WHERE repository_dest_id = ?';

        return $this->getDB()->single($sql, [$repository_id]);
    }

    /**
     * @return array
     */
    public function getReferenceByPullRequestId($pull_request_id)
    {
        return $this->getDB()->row('SELECT * FROM plugin_pullrequest_git_reference WHERE pr_id = ?', $pull_request_id);
    }
}
