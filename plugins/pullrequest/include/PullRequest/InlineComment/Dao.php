<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\InlineComment;

use Tuleap\DB\DataAccessObject;
use Tuleap\PullRequest\Comment\ParentCommentSearcher;
use Tuleap\PullRequest\Comment\ThreadColorUpdater;

class Dao extends DataAccessObject implements ParentCommentSearcher, ThreadColorUpdater, InlineCommentSearcher, InlineCommentSaver, SearchInlineCommentsOnFile, CreateInlineComment
{
    public function searchByCommentID(int $inline_comment_id): ?array
    {
        return $this->getDB()->row(
            'SELECT id, pull_request_id, user_id, post_date, file_path, unidiff_offset, content, is_outdated, parent_id, position, color, format, last_edition_date
            FROM plugin_pullrequest_inline_comments
            WHERE id = ?',
            $inline_comment_id
        );
    }

    public function searchUpToDateByFilePath(int $pull_request_id, string $file_path): array
    {
        $sql = 'SELECT * FROM plugin_pullrequest_inline_comments
                WHERE pull_request_id=?
                AND file_path=? AND is_outdated=false';

        $rows     = $this->getDB()->run($sql, $pull_request_id, $file_path);
        $comments = [];
        foreach ($rows as $row) {
            $comments[] = InlineComment::buildFromRow($row);
        }
        return $comments;
    }

    public function searchUpToDateByPullRequestId($pull_request_id)
    {
        $sql = 'SELECT * FROM plugin_pullrequest_inline_comments
                WHERE pull_request_id=? AND is_outdated=false';

        return $this->getDB()->run($sql, $pull_request_id);
    }

    public function searchAllByPullRequestId($pull_request_id)
    {
        $sql = 'SELECT SQL_CALC_FOUND_ROWS *
                FROM plugin_pullrequest_inline_comments
                WHERE pull_request_id = ?';

        return $this->getDB()->run($sql, $pull_request_id);
    }

    public function insert(NewInlineComment $comment): int
    {
        $this->getDB()->insert(
            'plugin_pullrequest_inline_comments',
            [
                'pull_request_id' => $comment->pull_request->getId(),
                'user_id'         => (int) $comment->author->getId(),
                'file_path'       => $comment->file_path,
                'post_date'       => $comment->post_date->getTimestamp(),
                'unidiff_offset'  => $comment->unidiff_offset,
                'content'         => $comment->content,
                'position'        => $comment->position,
                'parent_id'       => $comment->parent_id,
                'format'          => $comment->format,
            ]
        );

        return (int) $this->getDB()->lastInsertId();
    }

    public function updateComment($comment_id, $unidiff_offset, $is_outdated)
    {
        $sql = 'UPDATE plugin_pullrequest_inline_comments
            SET unidiff_offset=?, is_outdated=?
            WHERE id=?';

        $this->getDB()->run($sql, $unidiff_offset, $is_outdated, $comment_id);
    }

    public function setThreadColor(int $id, string $color): void
    {
        $sql = 'UPDATE plugin_pullrequest_inline_comments
            SET color=?
            WHERE id=?';

        $this->getDB()->run($sql, $color, $id);
    }

    public function saveUpdatedComment(InlineComment $comment): void
    {
        $this->getDB()->update(
            'plugin_pullrequest_inline_comments',
            [
                'content'           => $comment->getContent(),
                'last_edition_date' => $comment->getLastEditionDate()->unwrapOr(null),
            ],
            ['id' => $comment->getId()]
        );
    }
}
