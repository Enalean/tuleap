<?php
/**
 * Copyright (c) Enalean, 2016 - 2018. All Rights Reserved.
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

use Tuleap\PullRequest\InlineComment\Dao;
use Tuleap\PullRequest\PullRequest;
use UserManager;
use Tuleap\User\REST\MinimalUserRepresentation;

class PullRequestInlineCommentRepresentationBuilder
{
    /** @var UserManager */
    private $user_manager;

    /** @var \Tuleap\PullRequest\InlineComment\Dao */
    private $dao;

    public function __construct(Dao $dao, UserManager $user_manager)
    {
        $this->dao          = $dao;
        $this->user_manager = $user_manager;
    }

    public function getForFile(PullRequest $pull_request, $file_path, $project_id)
    {
        $res = $this->dao->searchUpToDateByFilePath($pull_request->getId(), $file_path);

        $inline_comments = array();
        foreach ($res as $row) {
            $user_id = $row['user_id'];
            $user_representation = new MinimalUserRepresentation();
            $user_representation->build($this->user_manager->getUserById($user_id));

            $inline_comments[] = new PullRequestInlineCommentRepresentation(
                (int) $row['unidiff_offset'],
                $user_representation,
                $row['post_date'],
                $row['content'],
                $project_id,
                $row['position']
            );
        }

        return $inline_comments;
    }
}
