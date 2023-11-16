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

namespace Tuleap\PullRequest\Tests\Stub;

use Tuleap\PullRequest\InlineComment\NewInlineComment;

final class CreateInlineCommentStub implements \Tuleap\PullRequest\InlineComment\CreateInlineComment
{
    private ?NewInlineComment $last_argument = null;

    private function __construct(private readonly int $inserted_id)
    {
    }

    public static function withInsertedId(int $inserted_id): self
    {
        return new self($inserted_id);
    }

    public function insert(NewInlineComment $comment): int
    {
        $this->last_argument = $comment;
        return $this->inserted_id;
    }

    public function getLastArgument(): ?NewInlineComment
    {
        return $this->last_argument;
    }
}
