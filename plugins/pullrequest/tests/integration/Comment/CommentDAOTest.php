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

use Tuleap\DB\DBFactory;
use Tuleap\PullRequest\PullRequest\Timeline\TimelineComment;
use Tuleap\Test\PHPUnit\TestCase;

final class CommentDAOTest extends TestCase
{
    private Dao $dao;

    protected function setUp(): void
    {
        $this->dao = new Dao();
    }

    protected function tearDown(): void
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        $db->run('DELETE FROM plugin_pullrequest_comments');
    }

    public function testItInsertsAndUpdatesContentAndLastEditionDate(): void
    {
        $pull_request_id = 36;
        $author_user_id  = 150;
        $post_date       = new \DateTimeImmutable('@1572860074');
        $content         = 'Amphirhina Spirula';
        $parent_id       = 81;
        $format          = TimelineComment::FORMAT_MARKDOWN;

        $comment_id = $this->dao->save(
            $pull_request_id,
            $author_user_id,
            $post_date,
            $content,
            $format,
            $parent_id
        );

        $comment_row = $this->dao->searchByCommentID($comment_id);
        self::assertEquals([
            'id'                => $comment_id,
            'pull_request_id'   => $pull_request_id,
            'user_id'           => $author_user_id,
            'post_date'         => $post_date->getTimestamp(),
            'content'           => $content,
            'parent_id'         => $parent_id,
            'color'             => '',
            'format'            => $format,
            'last_edition_date' => null,
        ], $comment_row);

        $comment         = Comment::buildFromRow($comment_row);
        $new_content     = 'judgingly appropriativeness';
        $edition_date    = new \DateTimeImmutable('@1720482959');
        $updated_comment = Comment::buildWithNewContent($comment, $new_content, $edition_date);

        $this->dao->updateComment($updated_comment);
        $updated_row = $this->dao->searchByCommentID($comment_id);
        self::assertSame($new_content, $updated_row['content']);
        self::assertSame($edition_date->getTimestamp(), $updated_row['last_edition_date']);
    }
}
