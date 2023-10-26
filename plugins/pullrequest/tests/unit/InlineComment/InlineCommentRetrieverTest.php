<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

use Tuleap\Option\Option;
use Tuleap\PullRequest\Tests\Builders\InlineCommentTestBuilder;
use Tuleap\PullRequest\Tests\Stub\InlineCommentSearcherStub;

final class InlineCommentRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const INLINE_COMMENT_ID = 12;
    private InlineCommentSearcherStub $dao;

    protected function setUp(): void
    {
        $this->dao = InlineCommentSearcherStub::withNoComment();
    }

    /**
     * @return Option<InlineComment>
     */
    private function getInlineComment(): Option
    {
        $retriever = new InlineCommentRetriever($this->dao);
        return $retriever->getInlineCommentByID(self::INLINE_COMMENT_ID);
    }

    public function testInlineCommentCanBeRetrievedWhenItExists(): void
    {
        $comment   = InlineCommentTestBuilder::aTextComment('My comment')
            ->withId(self::INLINE_COMMENT_ID)
            ->build();
        $this->dao = InlineCommentSearcherStub::withComment($comment);

        self::assertTrue($this->getInlineComment()->isValue());
    }

    public function testInlineCommentCannotBeRetrievedWhenItDoesNotExist(): void
    {
        self::assertTrue($this->getInlineComment()->isNothing());
    }
}
