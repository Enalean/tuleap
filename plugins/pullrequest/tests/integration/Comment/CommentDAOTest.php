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

use Tuleap\PullRequest\PullRequest\Timeline\TimelineComment;
use Tuleap\PullRequest\Tests\Builders\PullRequestTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;

final class CommentDAOTest extends TestIntegrationTestCase
{
    private Dao $dao;
    protected function setUp(): void
    {
        $this->dao = new Dao();
    }

    public function testItInsertsAndUpdatesContentAndLastEditionDate(): void
    {
        $pull_request = PullRequestTestBuilder::aPullRequestInReview()->withId(36)->build();
        $author       = UserTestBuilder::buildWithId(150);
        $new_comment  = new NewComment(
            $pull_request,
            156,
            'Amphirhina Spirula',
            TimelineComment::FORMAT_MARKDOWN,
            81,
            $author,
            new \DateTimeImmutable('@1572860074')
        );

        $comment_id = $this->dao->create($new_comment);

        $comment_row = $this->dao->searchByCommentID($comment_id);
        self::assertNotNull($comment_row);
        self::assertEquals([
            'id'                => $comment_id,
            'pull_request_id'   => $new_comment->pull_request->getId(),
            'user_id'           => (int) $new_comment->author->getId(),
            'post_date'         => $new_comment->post_date->getTimestamp(),
            'content'           => $new_comment->content,
            'parent_id'         => $new_comment->parent_id,
            'color'             => '',
            'format'            => $new_comment->format,
            'last_edition_date' => null,
        ], $comment_row);

        $comment         = Comment::buildFromRow($comment_row);
        $new_content     = 'judgingly appropriativeness';
        $edition_date    = new \DateTimeImmutable('@1720482959');
        $updated_comment = Comment::buildWithNewContent($comment, $new_content, $edition_date);

        $this->dao->updateComment($updated_comment);
        $updated_row = $this->dao->searchByCommentID($comment_id);
        self::assertNotNull($updated_row);
        self::assertSame($new_content, $updated_row['content']);
        self::assertSame($edition_date->getTimestamp(), $updated_row['last_edition_date']);
    }
}
