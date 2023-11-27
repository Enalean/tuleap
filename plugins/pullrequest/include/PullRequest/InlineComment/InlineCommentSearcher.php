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

namespace Tuleap\PullRequest\InlineComment;

interface InlineCommentSearcher
{
    /**
     * @psalm-return array{
     *     id: int,
     *     pull_request_id: int,
     *     user_id: int,
     *     post_date: int,
     *     file_path: string,
     *     unidiff_offset: int,
     *     content: string,
     *     is_outdated: 0|1,
     *     parent_id: int,
     *     position: string,
     *     color: string,
     *     format: string,
     *     last_edition_date: int|null
     * }|null
     */
    public function searchByCommentID(int $inline_comment_id): ?array;
}
