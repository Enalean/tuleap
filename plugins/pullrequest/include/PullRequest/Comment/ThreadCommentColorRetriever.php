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

namespace Tuleap\PullRequest\Comment;

final class ThreadCommentColorRetriever
{
    public function __construct(private CountThreads $comment_dao, private ParentCommentSearcher $dao)
    {
    }

    public function retrieveColor(int $id, int $parent_id): string
    {
        if ($parent_id === 0) {
            return "";
        }

        $parent_comment = $this->dao->searchByCommentID($parent_id);
        if ($parent_comment && $parent_comment['color'] !== '') {
            return $parent_comment['color'];
        }

        $number_of_threads = $this->comment_dao->countAllThreadsOfPullRequest($id);
        return $this->getCorrespondingTlpColor($number_of_threads);
    }

    /**
     * @psalm-param int<0, max> $number_of_threads
     */
    private function getCorrespondingTlpColor(int $number_of_threads): string
    {
        $count = $number_of_threads;
        if ($count >= count(ThreadColors::TLP_COLORS)) {
            $count %= count(ThreadColors::TLP_COLORS);
        }
        return ThreadColors::TLP_COLORS[$count];
    }
}
