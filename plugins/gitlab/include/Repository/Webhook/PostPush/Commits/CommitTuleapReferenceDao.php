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

namespace Tuleap\Gitlab\Repository\Webhook\PostPush\Commits;

use Tuleap\DB\DataAccessObject;
use Tuleap\Gitlab\Reference\Commit\GitlabCommitReference;

class CommitTuleapReferenceDao extends DataAccessObject
{
    public function saveGitlabCommitInfo(
        int $integration_id,
        string $commit_sha1,
        int $commit_date,
        string $commit_title,
        string $commit_branch_name,
        string $commit_author_name,
        string $commit_author_email,
    ): void {
        $sql = '
            INSERT INTO plugin_gitlab_repository_integration_commit_info
                (
                     integration_id,
                     commit_sha1,
                     commit_date,
                     commit_title,
                     commit_branch,
                     author_name,
                     author_email
                 )
            VALUES (?, UNHEX(?), ?, ?, ?, ?, ?)
        ';

        $this->getDB()->run(
            $sql,
            $integration_id,
            $commit_sha1,
            $commit_date,
            $commit_title,
            $commit_branch_name,
            $commit_author_name,
            $commit_author_email
        );
    }

    /**
     * @psalm-return array{commit_sha1: string, commit_date: int, commit_title: string, author_name: string, author_email: string}
     */
    public function searchCommitInRepositoryWithSha1(int $integration, string $commit_sha1): ?array
    {
        $sql = "
            SELECT LOWER(HEX(commit_sha1)) as commit_sha1,
                   commit_date,
                   commit_title,
                   commit_branch,
                   author_name,
                   author_email
            FROM plugin_gitlab_repository_integration_commit_info
            WHERE integration_id = ?
                AND commit_sha1 = UNHEX(?)
        ";

        return $this->getDB()->row($sql, $integration, $commit_sha1);
    }

    public function deleteCommitsInIntegration(
        string $integration_path,
        int $integration_id,
        int $integration_project_id,
    ): void {
        $this->getDB()->delete(
            'plugin_gitlab_repository_integration_commit_info',
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
            GitlabCommitReference::NATURE_NAME,
            $integration_project_id
        );
    }
}
