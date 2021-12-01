<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Repository\Webhook\PostMergeRequest;

use Tuleap\DB\DataAccessObject;
use Tuleap\Gitlab\Reference\MergeRequest\GitlabMergeRequestReference;

class MergeRequestTuleapReferenceDao extends DataAccessObject
{
    public function saveGitlabMergeRequestInfo(
        int $integration_id,
        int $merge_request_id,
        string $title,
        string $description,
        string $source_branch,
        string $state,
        int $created_at,
    ): void {
        $this->getDB()->insertOnDuplicateKeyUpdate(
            'plugin_gitlab_repository_integration_merge_request_info',
            [
                'integration_id'   => $integration_id,
                'merge_request_id' => $merge_request_id,
                'title'            => $title,
                'description'      => $description,
                'source_branch'    => $source_branch,
                'state'            => $state,
                'created_at'       => $created_at,
            ],
            [
                'title',
                'description',
                'source_branch',
                'state',
                'created_at',
            ]
        );
    }

    public function setAuthorData(int $integration_id, int $merge_request_id, string $author_name, ?string $author_email): void
    {
        $this->getDB()->update(
            'plugin_gitlab_repository_integration_merge_request_info',
            [
                'author_name'  => $author_name,
                'author_email' => $author_email,
            ],
            [
                'integration_id'   => $integration_id,
                'merge_request_id' => $merge_request_id,
            ]
        );
    }

    /**
     * @psalm-return array{title: string, state: string, description: string, source_branch: ?string, created_at: int, author_name: ?string, author_email: ?string}
     */
    public function searchMergeRequestInRepositoryWithId(int $integration_id, int $merge_request_id): ?array
    {
        $sql = "
            SELECT title, state, description, source_branch, created_at, author_name, author_email
            FROM plugin_gitlab_repository_integration_merge_request_info
            WHERE integration_id = ?
                AND merge_request_id = ?
        ";

        return $this->getDB()->row($sql, $integration_id, $merge_request_id);
    }

    public function deleteAllMergeRequestInIntegration(
        string $integration_path,
        int $integration_id,
        int $integration_project_id,
    ): void {
        $this->getDB()->delete(
            'plugin_gitlab_repository_integration_merge_request_info',
            ['integration_id' => $integration_id]
        );

        $sql = "DELETE FROM cross_references
                WHERE source_id LIKE CONCAT(?, '/%')
                    AND source_type = ?
                    AND source_gid = ?";

        $this->getDB()->run(
            $sql,
            $integration_path,
            GitlabMergeRequestReference::NATURE_NAME,
            $integration_project_id
        );
    }
}
