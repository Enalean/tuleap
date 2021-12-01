<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\Gitlab\Repository\Webhook\TagPush;

use Tuleap\DB\DataAccessObject;
use Tuleap\Gitlab\Reference\Tag\GitlabTagReference;

class TagInfoDao extends DataAccessObject
{
    public function saveGitlabTagInfo(
        int $integration_id,
        string $commit_sha1,
        string $tag_name,
        string $tag_message,
    ): void {
        $sql = '
            INSERT INTO plugin_gitlab_repository_integration_tag_info
                (
                     integration_id,
                     commit_sha1,
                     tag_name,
                     tag_message
                 )
            VALUES (?, UNHEX(?), ?, ?)
        ';

        $this->getDB()->run(
            $sql,
            $integration_id,
            $commit_sha1,
            $tag_name,
            $tag_message,
        );
    }

    /**
     * @psalm-return null|array{commit_sha1: string, tag_name: string, tag_message: string}
     */
    public function searchTagInRepositoryWithTagName(int $integration_id, string $tag_name): ?array
    {
        $sql = "
            SELECT LOWER(HEX(commit_sha1)) as commit_sha1,
                   tag_name,
                   tag_message
            FROM plugin_gitlab_repository_integration_tag_info
            WHERE integration_id = ?
                AND tag_name = ?
        ";

        return $this->getDB()->row($sql, $integration_id, $tag_name);
    }

    public function deleteTagsInIntegration(
        string $integration_path,
        int $integration_id,
        int $integration_project_id,
    ): void {
        $this->getDB()->delete(
            'plugin_gitlab_repository_integration_tag_info',
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
            GitlabTagReference::NATURE_NAME,
            $integration_project_id
        );
    }

    public function deleteTagInGitlabRepository(int $integration_id, string $tag_name): void
    {
        $this->getDB()->delete(
            'plugin_gitlab_repository_integration_tag_info',
            [
                'integration_id' => $integration_id,
                'tag_name' => $tag_name,
            ]
        );
    }
}
