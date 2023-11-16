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

namespace Tuleap\PullRequest\Tests\InlineComment;

use Tuleap\PullRequest\InlineComment\InlineComment;
use Tuleap\PullRequest\InlineComment\NewInlineComment;
use Tuleap\PullRequest\PullRequest\Timeline\TimelineComment;
use Tuleap\PullRequest\REST\v1\Comment\ThreadColors;
use Tuleap\PullRequest\Tests\Builders\InlineCommentTestBuilder;
use Tuleap\PullRequest\Tests\Builders\PullRequestTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class InlineCommentTest extends TestCase
{
    public function testItBuildsFromDatabaseRow(): void
    {
        $id                = 949;
        $pull_request_id   = 44;
        $user_id           = 185;
        $post_date         = 1491399127;
        $file_path         = 'relative/path/to/file.txt';
        $unidiff_offset    = 66;
        $content           = 'Seshat asphyxiation';
        $parent_id         = 426;
        $position          = 'right';
        $color             = ThreadColors::TLP_COLORS[1];
        $format            = TimelineComment::FORMAT_MARKDOWN;
        $last_edition_date = new \DateTimeImmutable('@1439105675');

        $comment = InlineComment::buildFromRow([
            'id'                => $id,
            'pull_request_id'   => $pull_request_id,
            'user_id'           => $user_id,
            'post_date'         => $post_date,
            'file_path'         => $file_path,
            'unidiff_offset'    => $unidiff_offset,
            'content'           => $content,
            'is_outdated'       => 0,
            'parent_id'         => $parent_id,
            'position'          => $position,
            'color'             => $color,
            'format'            => $format,
            'last_edition_date' => $last_edition_date->getTimestamp(),
        ]);

        self::assertSame($id, $comment->getId());
        self::assertSame($pull_request_id, $comment->getPullRequestId());
        self::assertSame($user_id, $comment->getUserId());
        self::assertSame($post_date, $comment->getPostDate());
        self::assertSame($file_path, $comment->getFilePath());
        self::assertSame($unidiff_offset, $comment->getUnidiffOffset());
        self::assertSame($content, $comment->getContent());
        self::assertFalse($comment->isOutdated());
        self::assertSame($parent_id, $comment->getParentId());
        self::assertSame($position, $comment->getPosition());
        self::assertSame($color, $comment->getColor());
        self::assertSame($format, $comment->getFormat());
        self::assertSame($last_edition_date->getTimestamp(), $comment->getLastEditionDate()->unwrapOr(0));
    }

    public function testItBuildsFromDatabaseRowWithoutLastEditionDate(): void
    {
        $comment = InlineComment::buildFromRow([
            'id'                => 40,
            'pull_request_id'   => 53,
            'user_id'           => 126,
            'post_date'         => 1885725284,
            'file_path'         => 'path/to/file.php',
            'unidiff_offset'    => 3,
            'content'           => 'sphaerite',
            'is_outdated'       => 0,
            'parent_id'         => 0,
            'position'          => 'left',
            'color'             => 'inca-silver',
            'format'            => TimelineComment::FORMAT_MARKDOWN,
            'last_edition_date' => null,
        ]);
        self::assertTrue($comment->getLastEditionDate()->isNothing());
    }

    public function testItBuildsFromNewInlineComment(): void
    {
        $pull_request_id = 78;
        $author_id       = 144;
        $post_timestamp  = 1700000000;
        $file_path       = 'putatively/reconstrue/unapproximate.php';
        $unidiff_offset  = 71;
        $position        = 'left';
        $content         = 'expansile reask';
        $format          = TimelineComment::FORMAT_TEXT;
        $parent_id       = 470;
        $id              = 549;
        $color           = ThreadColors::TLP_COLORS[2];

        $pull_request       = PullRequestTestBuilder::aPullRequestInReview()->withId($pull_request_id)->build();
        $author             = UserTestBuilder::buildWithId($author_id);
        $post_date          = new \DateTimeImmutable('@' . $post_timestamp);
        $new_inline_comment = new NewInlineComment(
            $pull_request,
            192,
            $file_path,
            $unidiff_offset,
            $content,
            $format,
            $position,
            $parent_id,
            $author,
            $post_date
        );

        $comment = InlineComment::fromNewInlineComment($new_inline_comment, $id, $color);

        self::assertSame($id, $comment->getId());
        self::assertSame($pull_request_id, $comment->getPullRequestId());
        self::assertSame($author_id, $comment->getUserId());
        self::assertSame($post_timestamp, $comment->getPostDate());
        self::assertSame($file_path, $comment->getFilePath());
        self::assertSame($unidiff_offset, $comment->getUnidiffOffset());
        self::assertSame($content, $comment->getContent());
        self::assertFalse($comment->isOutdated());
        self::assertSame($parent_id, $comment->getParentId());
        self::assertSame($position, $comment->getPosition());
        self::assertSame($color, $comment->getColor());
        self::assertSame($format, $comment->getFormat());
        self::assertTrue($comment->getLastEditionDate()->isNothing());
    }

    public function testOutdatedIsMutable(): void
    {
        $comment = InlineCommentTestBuilder::aMarkdownComment('disproven Monongahela')
            ->thatIsUpToDate()
            ->build();

        $comment->markAsOutdated();

        self::assertTrue($comment->isOutdated());
    }

    public function testUnidiffOffsetIsMutable(): void
    {
        $comment = InlineCommentTestBuilder::aMarkdownComment('parabaptization monkshood')
            ->onUnidiffOffset(92)
            ->build();

        $comment->setUnidiffOffset(229);

        self::assertSame(229, $comment->getUnidiffOffset());
    }

    public function testBuildWithNewContentDoesNotMutateInlineComment(): void
    {
        $comment      = InlineCommentTestBuilder::aMarkdownComment('initial')->build();
        $new_content  = 'updated';
        $edition_date = new \DateTimeImmutable('@1507600600');

        $modified_comment = InlineComment::buildWithNewContent($comment, $new_content, $edition_date);

        self::assertNotSame($comment, $modified_comment);
        self::assertSame($new_content, $modified_comment->getContent());
        self::assertSame($edition_date->getTimestamp(), $modified_comment->getLastEditionDate()->unwrapOr(0));
    }
}
