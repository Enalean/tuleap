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

use Tuleap\PullRequest\InlineComment\InlineComment;

final class InlineCommentSaverStub implements \Tuleap\PullRequest\InlineComment\InlineCommentSaver
{
    private int $call_count               = 0;
    private ?InlineComment $last_argument = null;

    private function __construct()
    {
    }

    public function saveUpdatedComment(InlineComment $comment): void
    {
        $this->call_count++;
        $this->last_argument = $comment;
    }

    public static function withCallCount(): self
    {
        return new self();
    }

    public function getCallCount(): int
    {
        return $this->call_count;
    }

    public function getLastArgument(): ?InlineComment
    {
        return $this->last_argument;
    }
}
