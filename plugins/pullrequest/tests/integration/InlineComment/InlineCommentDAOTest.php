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
use Tuleap\PullRequest\Tests\Builders\InlineCommentTestBuilder;
use Tuleap\PullRequest\Tests\Builders\PullRequestTestBuilder;
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

    public function testItFindsUpToDateComments(): void
    {
        $pull_request_id         = 338;
        $file_path               = 'undercutter/laudist/angulate.json';
        $pull_request            = PullRequestTestBuilder::aPullRequestInReview()->withId($pull_request_id)->build();
        $first_comment           = InlineCommentTestBuilder::aMarkdownComment('excruciating crosshackle')
            ->onPullRequest($pull_request)
            ->onFile($file_path)
            ->build();
        $second_comment          = InlineCommentTestBuilder::aMarkdownComment('galactic bullback')
            ->onPullRequest($pull_request)
            ->onFile($file_path)
            ->build();
        $comment_on_another_file = InlineCommentTestBuilder::aMarkdownComment('unexplorable')
            ->onPullRequest($pull_request)
            ->onFile('coresign/unmicroscopic/electrophotometry.ts')
            ->build();
        $comment_on_another_pr   = InlineCommentTestBuilder::aTextComment('kinesodic')
            ->onPullRequest(PullRequestTestBuilder::aPullRequestInReview()->withId(414)->build())
            ->onFile($file_path)
            ->build();
        $outdated_comment        = InlineCommentTestBuilder::aMarkdownComment('tragical prostemmate')
            ->onPullRequest($pull_request)
            ->onFile($file_path)
            ->build();

        $first_comment_id           = $this->insert($first_comment);
        $second_comment_id          = $this->insert($second_comment);
        $comment_on_another_file_id = $this->insert($comment_on_another_file);
        $comment_on_another_pr_id   = $this->insert($comment_on_another_pr);
        $outdated_comment_id        = $this->insert($outdated_comment);
        $this->markCommentAsOutdated($outdated_comment_id, $outdated_comment->getUnidiffOffset());

        $all_rows  = $this->dao->searchAllByPullRequestId($pull_request_id);
        $found_ids = $this->mapRowsToIds($all_rows);

        self::assertCount(4, $all_rows);
        self::assertContains($first_comment_id, $found_ids);
        self::assertContains($second_comment_id, $found_ids);
        self::assertContains($comment_on_another_file_id, $found_ids);
        self::assertContains($outdated_comment_id, $found_ids);
        self::assertNotContains($comment_on_another_pr_id, $found_ids);

        $comments_by_path = $this->dao->searchUpToDateByFilePath($pull_request_id, $file_path);
        $found_ids        = $this->mapCommentsToIds($comments_by_path);

        self::assertCount(2, $comments_by_path);
        self::assertContains($first_comment_id, $found_ids);
        self::assertContains($second_comment_id, $found_ids);
        self::assertNotContains($outdated_comment_id, $found_ids);

        $rows_by_pull_request_id = $this->dao->searchUpToDateByPullRequestId($pull_request_id);
        $found_ids               = $this->mapRowsToIds($rows_by_pull_request_id);

        self::assertCount(3, $rows_by_pull_request_id);
        self::assertContains($first_comment_id, $found_ids);
        self::assertContains($second_comment_id, $found_ids);
        self::assertContains($comment_on_another_file_id, $found_ids);
        self::assertNotContains($outdated_comment_id, $found_ids);
    }

    /** @psalm-return list<int> */
    private function mapRowsToIds(array $rows): array
    {
        return array_map(static fn(array $row) => $row['id'], $rows);
    }

    /** @psalm-return list<int> */
    private function mapCommentsToIds(array $comments): array
    {
        return array_map(static fn(InlineComment $comment) => $comment->getId(), $comments);
    }

    private function insert(InlineComment $comment): int
    {
        return $this->dao->insert(
            $comment->getPullRequestId(),
            $comment->getUserId(),
            $comment->getFilePath(),
            $comment->getPostDate(),
            $comment->getUnidiffOffset(),
            $comment->getContent(),
            $comment->getPosition(),
            $comment->getParentId(),
            $comment->getFormat(),
        );
    }

    private function markCommentAsOutdated(int $comment_id, int $unidiff_offset): void
    {
        $this->dao->updateComment($comment_id, $unidiff_offset, true);
    }
}
