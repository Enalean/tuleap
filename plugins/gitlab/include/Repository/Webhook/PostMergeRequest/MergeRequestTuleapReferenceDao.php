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

class MergeRequestTuleapReferenceDao extends DataAccessObject
{
    public function saveGitlabMergeRequestInfo(
        int $repository_id,
        int $merge_request_id,
        string $title,
        string $description,
        string $state,
        int $created_at
    ): void {
        $this->getDB()->insertOnDuplicateKeyUpdate(
            'plugin_gitlab_merge_request_info',
            [
                'repository_id'    => $repository_id,
                'merge_request_id' => $merge_request_id,
                'title'            => $title,
                'description'      => $description,
                'state'            => $state,
                'created_at'       => $created_at
            ],
            [
                'title',
                'description',
                'state',
                'created_at'
            ]
        );
    }

    public function setAuthorData(int $repository_id, int $merge_request_id, string $author_name, ?string $author_email): void
    {
        $this->getDB()->update(
            'plugin_gitlab_merge_request_info',
            [
                'author_name'  => $author_name,
                'author_email' => $author_email,
            ],
            [
                'repository_id'    => $repository_id,
                'merge_request_id' => $merge_request_id
            ]
        );
    }

    /**
     * @psalm-return array{title: string, state: string, description: string, created_at: int, author_name: ?string, author_email: ?string}
     */
    public function searchMergeRequestInRepositoryWithId(int $repository_id, int $merge_request_id): ?array
    {
        $sql = "
            SELECT title, state, description, created_at, author_name, author_email
            FROM plugin_gitlab_merge_request_info
            WHERE repository_id = ?
                AND merge_request_id = ?
        ";

        return $this->getDB()->row($sql, $repository_id, $merge_request_id);
    }

    public function deleteAllMergeRequestWithRepositoryId(int $repository_id): void
    {
        $this->getDB()->delete(
            'plugin_gitlab_merge_request_info',
            ['repository_id' => $repository_id]
        );
    }
}
