<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

use DataAccessObject;

class Dao extends DataAccessObject
{
    public function searchUpToDateByFilePath($pull_request_id, $file_path)
    {
        $pull_request_id = $this->da->escapeInt($pull_request_id);
        $file_path       = $this->da->quoteSmart($file_path);

        $sql = "SELECT * FROM plugin_pullrequest_inline_comments
                WHERE pull_request_id=$pull_request_id
                AND file_path=$file_path AND is_outdated=false";

        return $this->retrieve($sql);
    }

    public function searchUpToDateByPullRequestId($pull_request_id)
    {
        $pull_request_id = $this->da->escapeInt($pull_request_id);

        $sql = "SELECT * FROM plugin_pullrequest_inline_comments
                WHERE pull_request_id=$pull_request_id AND is_outdated=false";

        return $this->retrieve($sql);
    }

    public function insert($pull_request_id, $user_id, $file_path, $post_date, $unidiff_offset, $content)
    {
        $pull_request_id = $this->da->escapeInt($pull_request_id);
        $user_id         = $this->da->escapeInt($user_id);
        $file_path       = $this->da->quoteSmart($file_path);
        $post_date       = $this->da->escapeInt($post_date);
        $unidiff_offset  = $this->da->escapeInt($unidiff_offset);
        $content         = $this->da->quoteSmart($content);

        $sql = "INSERT INTO plugin_pullrequest_inline_comments(
                pull_request_id,  user_id,  file_path,  post_date,  unidiff_offset,  content)
        VALUES($pull_request_id, $user_id, $file_path, $post_date, $unidiff_offset, $content)";

        return $this->updateAndGetLastId($sql);
    }

    public function updateComment($comment_id, $unidiff_offset, $is_outdated)
    {
        $comment_id     = $this->da->escapeInt($comment_id);
        $unidiff_offset = $this->da->escapeInt($unidiff_offset);
        $is_outdated    = $this->da->escapeInt($is_outdated);

        $sql = "UPDATE plugin_pullrequest_inline_comments
            SET unidiff_offset=$unidiff_offset, is_outdated=$is_outdated
            WHERE id=$comment_id";

        return $this->update($sql);
    }
}
