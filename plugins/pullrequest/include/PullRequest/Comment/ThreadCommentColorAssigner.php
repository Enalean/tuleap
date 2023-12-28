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

final class ThreadCommentColorAssigner
{
    public function __construct(private ParentCommentSearcher $dao, private ThreadColorUpdater $thread_color_updater)
    {
    }

    public function assignColor(int $parent_id, string $color): void
    {
        if ($parent_id === 0) {
            return;
        }

        $parent_comment = $this->dao->searchByCommentID($parent_id);
        if (! $parent_comment || $parent_comment['parent_id'] !== 0 || $parent_comment['color'] !== '') {
            return;
        }

        $this->thread_color_updater->setThreadColor($parent_comment['id'], $color);
    }
}
