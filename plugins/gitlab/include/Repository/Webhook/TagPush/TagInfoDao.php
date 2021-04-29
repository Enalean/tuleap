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

class TagInfoDao extends DataAccessObject
{
    public function saveGitlabTagInfo(
        int $repository_id,
        string $commit_sha1,
        string $tag_name,
        string $tag_message
    ): void {
        $sql = '
            INSERT INTO plugin_gitlab_tag_info
                (
                     repository_id,
                     commit_sha1,
                     tag_name,
                     tag_message
                 )
            VALUES (?, UNHEX(?), ?, ?)
        ';

        $this->getDB()->run(
            $sql,
            $repository_id,
            $commit_sha1,
            $tag_name,
            $tag_message,
        );
    }

    /**
     * @psalm-return null|array{commit_sha1: string, tag_name: string, tag_message: string}
     */
    public function searchTagInRepositoryWithTagName(int $repository_id, string $tag_name): ?array
    {
        $sql = "
            SELECT LOWER(HEX(commit_sha1)) as commit_sha1,
                   tag_name,
                   tag_message
            FROM plugin_gitlab_tag_info
            WHERE repository_id = ?
                AND tag_name = ?
        ";

        return $this->getDB()->row($sql, $repository_id, $tag_name);
    }

    public function deleteTagsInGitlabRepository(int $repository_id): void
    {
        $this->getDB()->delete(
            'plugin_gitlab_tag_info',
            [
                'repository_id' => $repository_id,
            ]
        );
    }
}
