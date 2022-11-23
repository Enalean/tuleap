<?php
/**
 * Copyright (c) Enalean 2022 - Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\REST\v1\Comment;

use Tuleap\PullRequest\Comment\ThreadCommentDao;

class ThreadCommentColorRetriever
{
    public function __construct(private ThreadCommentDao $comment_dao)
    {
    }

    public function retrieveColor(int $id, int $parent_id): string
    {
        if ($parent_id === 0) {
            return "";
        }

        $all_comments = $this->comment_dao->searchAllThreadByPullRequestId($id);
        return $this->getCorrespondingTlpColor($all_comments);
    }

    private function getCorrespondingTlpColor(array $all_comments): string
    {
        $count = count($all_comments);
        if ($count >= count(ThreadColors::TLP_COLORS)) {
            $count %= count(ThreadColors::TLP_COLORS);
        }
        return ThreadColors::TLP_COLORS[$count];
    }
}
