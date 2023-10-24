<?php
/**
 * Copyright (c) Enalean, 2023-present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\PullRequest\Tests\Stub;

use Tuleap\PullRequest\Comment\CommentSearcher;

final class CommentSearcherStub implements CommentSearcher
{
    private function __construct(private readonly ?array $row)
    {
    }

    public function searchByCommentID(int $comment_id): ?array
    {
        return $this->row;
    }

    public static function withDefaultRow(): self
    {
        return new self(self::defaultRow());
    }

    public static function withCustomUser(int $user_id): self
    {
        $row            = self::defaultRow();
        $row['user_id'] = $user_id;
        return new self($row);
    }

    public static function withNoRow(): self
    {
        return new self(null);
    }

    public static function withFormat(string $format): self
    {
        $row           = self::defaultRow();
        $row['format'] = $format;
        return new self($row);
    }

    private static function defaultRow(): array
    {
        return [
            'id'              => 15,
            'pull_request_id' => 20,
            'user_id'         => 102,
            'post_date'       => 1697465547,
            'content'         => 'Vroom vroom',
            'parent_id'       => 1,
            'color'           => 'graffiti-yellow',
            'format'           => 'commonmark',
        ];
    }
}
