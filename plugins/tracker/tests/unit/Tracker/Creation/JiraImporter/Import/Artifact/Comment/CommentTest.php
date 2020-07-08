<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Comment;

use PHPUnit\Framework\TestCase;

class CommentTest extends TestCase
{
    public function testItBuildsACommentFromAPIResponse(): void
    {
        $comment = Comment::buildFromAPIResponse(
            [
                "body" => "Comment 01",
                "renderedBody" => "<p>Comment 01</p>",
                "created" => "2020-04-21T11:36:46.601+0200",
                "updated" => "2020-04-21T11:36:46.601+0200",
                "updateAuthor" => [
                    'displayName' => 'user01',
                    'accountId'   => 'e8ds123qsd'
                ]
            ]
        );

        $this->assertSame(1587461806, $comment->getDate()->getTimestamp());
        $this->assertSame("<p>Comment 01</p>", $comment->getRenderedValue());
    }

    public function testItThrowsAnExceptionIfAPIResponseIsNotWellFormed(): void
    {
        $this->expectException(CommentAPIResponseNotWellFormedException::class);

        Comment::buildFromAPIResponse(
            [
                "body" => "Comment 01",
                "created" => "2020-04-21T11:36:46.601+0200",
                "updated" => "2020-04-21T11:36:46.601+0200",
                "updateAuthor" => [
                    "displayName" => 'user01'
                ]
            ]
        );

        $this->expectException(CommentAPIResponseNotWellFormedException::class);

        Comment::buildFromAPIResponse(
            [
                "body" => "Comment 01",
                "renderedBody" => "<p>Comment 01</p>",
                "created" => "2020-04-21T11:36:46.601+0200",
                "updateAuthor" => [
                    "displayName" => 'user01'
                ]
            ]
        );

        $this->expectException(CommentAPIResponseNotWellFormedException::class);

        $comment = Comment::buildFromAPIResponse(
            [
                "body" => "Comment 01",
                "renderedBody" => "<p>Comment 01</p>",
                "created" => "2020-04-21T11:36:46.601+0200",
                "updated" => "2020-04-21T11:36:46.601+0200"
            ]
        );
    }
}
