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

namespace Tuleap\PullRequest\REST\v1;

use Codendi_HTMLPurifier;
use Tuleap\Markdown\ContentInterpretor;
use Tuleap\PullRequest\InlineComment\Dao;
use Tuleap\PullRequest\PullRequest;
use UserManager;
use Tuleap\User\REST\MinimalUserRepresentation;

class PullRequestInlineCommentRepresentationBuilder
{
    public function __construct(private readonly Dao $dao, private readonly UserManager $user_manager, private readonly Codendi_HTMLPurifier $purifier, private readonly ContentInterpretor $common_mark_interpreter)
    {
    }

    /**
     * @return PullRequestInlineCommentRepresentation[]
     */
    public function getForFile(PullRequest $pull_request, $file_path, $project_id): array
    {
        $res = $this->dao->searchUpToDateByFilePath($pull_request->getId(), $file_path);

        $inline_comments = [];
        foreach ($res as $row) {
            $user_id             = $row['user_id'];
            $user_representation = MinimalUserRepresentation::build($this->user_manager->getUserById($user_id));

            $inline_comments[] = PullRequestInlineCommentRepresentation::build(
                $this->purifier,
                $this->common_mark_interpreter,
                (int) $row['unidiff_offset'],
                $user_representation,
                $row['post_date'],
                $row['content'],
                (int) $project_id,
                $row['position'],
                (int) $row['parent_id'],
                (int) $row['id'],
                $row['file_path'],
                $row['color'],
                $row['format']
            );
        }

        return $inline_comments;
    }
}
