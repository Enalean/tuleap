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

namespace Tuleap\PullRequest\Comment;

use Tuleap\PullRequest\PullRequest\Timeline\TimelineComment;
use Tuleap\Test\PHPUnit\TestCase;

final class CommentTest extends TestCase
{
    public function testItBuildsTheCommentFromDatabaseRowWithTheLastEditionDate(): void
    {
        $post_date         = new \DateTimeImmutable('@123456789');
        $last_edition_date = new \DateTimeImmutable('@1520883863');
        $comment           = Comment::buildFromRow(
            [
                'id'                => 1,
                'pull_request_id'   => 10,
                'user_id'           => 102,
                'post_date'         => $post_date->getTimestamp(),
                'content'           => "no",
                'parent_id'         => 0,
                'color'             => 'inca-silver',
                'format'            => TimelineComment::FORMAT_MARKDOWN,
                'last_edition_date' => $last_edition_date->getTimestamp(),
            ]
        );

        self::assertEquals($post_date, $comment->getPostDate());
        self::assertTrue($comment->getLastEditionDate()->isValue());
        self::assertEquals($last_edition_date, $comment->getLastEditionDate()->unwrapOr(null));
    }

    public function testItBuildsTheCommentFromDatabaseRowWithNothingAsLastEditionDate(): void
    {
        $comment = Comment::buildFromRow(
            [
                'id'                => 1,
                'pull_request_id'   => 10,
                'user_id'           => 102,
                'post_date'         => 123456789,
                'content'           => "no",
                'parent_id'         => 0,
                'color'             => 'inca-silver',
                'format'            => TimelineComment::FORMAT_MARKDOWN,
                'last_edition_date' => null,
            ]
        );
        self::assertTrue($comment->getLastEditionDate()->isNothing());
    }
}
