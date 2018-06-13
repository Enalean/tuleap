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

class b201806111115_reserve_git_ref_for_existing_pr extends ForgeUpgrade_Bucket // @codingStandardsIgnoreLine
{
    public function description()
    {
        return 'Reserve Git references for existing pull request';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql_select_pr = 'SELECT id, repo_dest_id
                          FROM plugin_pullrequest_review
                          LEFT JOIN plugin_pullrequest_git_reference ON (plugin_pullrequest_review.id = plugin_pullrequest_git_reference.pr_id)
                          WHERE plugin_pullrequest_git_reference.pr_id IS NULL
                          ORDER BY id';

        foreach ($this->db->dbh->query($sql_select_pr) as $row) {
            $this->db->dbh->beginTransaction();
            $reference_id = $this->getNextAvailableReferenceIdForRepository($row['repo_dest_id']);
            if (! $this->reserveReference($row['id'], $row['repo_dest_id'], $reference_id) || ! $this->db->dbh->commit()) {
                $this->db->dbh->rollBack();
                throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                    'Not able to reserve a Git reference for pull request #' . $row['id']
                );
            }
        }
    }

    private function getNextAvailableReferenceIdForRepository($repository_id)
    {
        $sql = 'SELECT IFNULL(MAX(reference_id), 0)+1 AS next_reference_id
                FROM plugin_pullrequest_git_reference
                WHERE repository_dest_id = ?';

        $pdo_statement = $this->db->dbh->prepare($sql);
        $pdo_statement->execute([$repository_id]);

        return $pdo_statement->fetchColumn(0);
    }

    private function reserveReference($pull_request_id, $repository_id, $reference_id)
    {
        $sql = 'INSERT INTO plugin_pullrequest_git_reference(pr_id , reference_id, repository_dest_id, status)
                VALUES (?, ?, ?, 1)';

        $pdo_statement = $this->db->dbh->prepare($sql);
        return $pdo_statement->execute([$pull_request_id, $reference_id, $repository_id]);
    }
}
