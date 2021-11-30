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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Git\Reference;

use Tuleap\DB\DataAccessObject;

class CommitDetailsCacheDao extends DataAccessObject
{
    /**
     * @psalm-return array{"title": string, "author_name": string, "author_email": string, "committer_epoch": int, "first_branch": string, "first_tag": string} | null
     */
    public function searchCommitDetails(int $repository_id, string $commit_sha1): ?array
    {
        $sql = "SELECT title,
                    author_name,
                    author_email,
                    committer_epoch,
                    first_branch,
                    first_tag
                FROM plugin_git_commit_details_cache
                WHERE repository_id = ?
                  AND commit_sha1 = UNHEX(?)";

        return $this->getDB()->row($sql, $repository_id, $commit_sha1);
    }

    public function saveCommitDetails(
        int $repository_id,
        string $commit_sha1,
        string $title,
        string $author_email,
        string $author_name,
        int $author_epoch,
        string $committer_email,
        string $committer_name,
        int $committer_epoch,
        string $first_branch,
        string $first_tag,
    ): void {
        $sql = '
            INSERT INTO plugin_git_commit_details_cache
                (
                    repository_id,
                    commit_sha1,
                    title,
                    author_name,
                    author_email,
                    author_epoch,
                    committer_name,
                    committer_email,
                    committer_epoch,
                    first_branch,
                    first_tag
                 )
            VALUES (?, UNHEX(?), ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ';

        $this->getDB()->run(
            $sql,
            $repository_id,
            $commit_sha1,
            $title,
            $author_name,
            $author_email,
            $author_epoch,
            $committer_name,
            $committer_email,
            $committer_epoch,
            $first_branch,
            $first_tag
        );
    }
}
