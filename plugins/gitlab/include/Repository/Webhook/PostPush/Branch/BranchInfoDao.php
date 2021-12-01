<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Repository\Webhook\PostPush\Branch;

use Tuleap\DB\DataAccessObject;
use Tuleap\Gitlab\Reference\Branch\GitlabBranchReference;

class BranchInfoDao extends DataAccessObject
{
    public function saveGitlabBranchInfo(
        int $integration_id,
        string $commit_sha1,
        string $branch_name,
        int $last_push_date,
    ): void {
        $sql = '
            INSERT INTO plugin_gitlab_repository_integration_branch_info
                (
                     integration_id,
                     commit_sha1,
                     branch_name,
                     last_push_date
                 )
            VALUES (?, UNHEX(?), ?, ?)
        ';

        $this->getDB()->run(
            $sql,
            $integration_id,
            $commit_sha1,
            $branch_name,
            $last_push_date
        );
    }

    public function updateGitlabBranchInformation(
        int $integration_id,
        string $commit_sha1,
        string $branch_name,
        int $last_push_date,
    ): void {
        $sql = '
            UPDATE plugin_gitlab_repository_integration_branch_info
            SET commit_sha1 = UNHEX(?), last_push_date = ?
            WHERE integration_id = ?
                AND branch_name = ?';

        $this->getDB()->run(
            $sql,
            $commit_sha1,
            $last_push_date,
            $integration_id,
            $branch_name
        );
    }

    /**
     * @psalm-return null|array{commit_sha1: string, branch_name: string, last_push_date:?int}
     */
    public function searchBranchInRepositoryWithBranchName(int $integration_id, string $branch_name): ?array
    {
        $sql = "
            SELECT LOWER(HEX(commit_sha1)) as commit_sha1,
                   branch_name,
                   last_push_date
            FROM plugin_gitlab_repository_integration_branch_info
            WHERE integration_id = ?
                AND branch_name = ?
        ";

        return $this->getDB()->row($sql, $integration_id, $branch_name);
    }

    public function deleteBranchesInIntegration(
        string $integration_path,
        int $integration_id,
        int $integration_project_id,
    ): void {
        $this->getDB()->delete(
            'plugin_gitlab_repository_integration_branch_info',
            [
                'integration_id' => $integration_id,
            ]
        );

        $sql = "DELETE FROM cross_references
                WHERE source_id LIKE CONCAT(?, '/%')
                    AND source_type = ?
                    AND source_gid = ?";

        $this->getDB()->run(
            $sql,
            $integration_path,
            GitlabBranchReference::NATURE_NAME,
            $integration_project_id
        );
    }

    public function deleteBranchInGitlabIntegration(int $integration_id, string $branch_name): void
    {
        $this->getDB()->delete(
            'plugin_gitlab_repository_integration_branch_info',
            [
                'integration_id' => $integration_id,
                'branch_name' => $branch_name,
            ]
        );
    }
}
