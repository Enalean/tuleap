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

use Tuleap\DB\DBFactory;
use Tuleap\PullRequest\PullRequest\Timeline\TimelineComment;
use Tuleap\Test\PHPUnit\TestCase;

final class InlineCommentDAOTest extends TestCase
{
    private Dao $dao;

    protected function setUp(): void
    {
        $this->dao = new Dao();
    }

    protected function tearDown(): void
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        $db->run('DELETE FROM plugin_pullrequest_inline_comments');
    }

    public function testItInsertsAndUpdatesContentAndLastEditionDate(): void
    {
        $pull_request_id   = 92;
        $user_id           = 105;
        $file_path         = 'path/to/file.php';
        $post_date         = 1402228583;
        $unidiff_offset    = 72;
        $content           = 'acceptive person';
        $position          = 'left';
        $parent_id         = 8;
        $format            = TimelineComment::FORMAT_MARKDOWN;
        $inline_comment_id = $this->dao->insert(
            $pull_request_id,
            $user_id,
            $file_path,
            $post_date,
            $unidiff_offset,
            $content,
            $position,
            $parent_id,
            $format
        );

        $inline_comment_row = $this->dao->searchByCommentID($inline_comment_id);
        self::assertEquals([
            'id'                => $inline_comment_id,
            'pull_request_id'   => $pull_request_id,
            'user_id'           => $user_id,
            'post_date'         => $post_date,
            'file_path'         => $file_path,
            'unidiff_offset'    => $unidiff_offset,
            'content'           => $content,
            'is_outdated'       => 0,
            'parent_id'         => $parent_id,
            'position'          => $position,
            'color'             => '',
            'format'            => $format,
            'last_edition_date' => null,
        ], $inline_comment_row);

        $comment         = InlineComment::buildFromRow($inline_comment_row);
        $new_content     = 'hereditable admonitive';
        $edition_date    = new \DateTimeImmutable('@1432796767');
        $updated_comment = InlineComment::buildWithNewContent($comment, $new_content, $edition_date);

        $this->dao->saveUpdatedComment($updated_comment);
        $updated_row = $this->dao->searchByCommentID($inline_comment_id);
        self::assertSame($new_content, $updated_row['content']);
        self::assertSame($edition_date->getTimestamp(), $updated_row['last_edition_date']);
    }
}
