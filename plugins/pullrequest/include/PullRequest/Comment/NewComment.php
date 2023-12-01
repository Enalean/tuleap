<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

use Tuleap\PullRequest\PullRequest;

/**
 * I hold the data necessary to create a new Comment. I do not have an ID yet.
 * @see Comment
 * @psalm-immutable
 */
final class NewComment
{
    public function __construct(
        public readonly PullRequest $pull_request,
        public readonly int $project_id,
        public readonly string $content,
        public readonly string $format,
        public readonly int $parent_id,
        public readonly \PFUser $author,
        public readonly \DateTimeImmutable $post_date,
    ) {
    }
}
