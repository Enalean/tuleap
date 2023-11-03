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
use Tuleap\PullRequest\Tests\Builders\InlineCommentTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class InlineCommentTest extends TestCase
{
    public function testItBuildsFromDatabaseRow(): void
    {
        $id              = 949;
        $pull_request_id = 44;
        $user_id         = 185;
        $post_date       = 1491399127;
        $file_path       = 'relative/path/to/file.txt';
        $unidiff_offset  = 66;
        $content         = 'Seshat asphyxiation';
        $parent_id       = 426;
        $position        = 'right';
        $color           = 'army-green';
        $format          = 'commonmark';

        $comment = InlineComment::buildFromRow([
            'id'              => $id,
            'pull_request_id' => $pull_request_id,
            'user_id'         => $user_id,
            'post_date'       => $post_date,
            'file_path'       => $file_path,
            'unidiff_offset'  => $unidiff_offset,
            'content'         => $content,
            'is_outdated'     => 0,
            'parent_id'       => $parent_id,
            'position'        => $position,
            'color'           => $color,
            'format'          => $format,
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
        $comment = InlineCommentTestBuilder::aMarkdownComment('initial')->build();

        $modified_comment = InlineComment::buildWithNewContent($comment, 'updated');

        self::assertNotSame($comment, $modified_comment);
        self::assertSame('updated', $modified_comment->getContent());
    }
}
