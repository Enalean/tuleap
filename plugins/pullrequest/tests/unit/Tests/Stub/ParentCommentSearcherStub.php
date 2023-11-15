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

final class ParentCommentSearcherStub implements \Tuleap\PullRequest\Comment\ParentCommentSearcher
{
    private function __construct(private readonly ?array $row)
    {
    }

    /**
     * @psalm-return null|array{id: int, parent_id: int, color: string}
     */
    public function searchByCommentID(int $inline_comment_id): ?array
    {
        return $this->row;
    }

    public static function withNotFound(): self
    {
        return new self(null);
    }

    public static function withParent(int $id, int $parent_id, string $color): self
    {
        return new self(['id' => $id, 'parent_id' => $parent_id, 'color' => $color]);
    }
}
