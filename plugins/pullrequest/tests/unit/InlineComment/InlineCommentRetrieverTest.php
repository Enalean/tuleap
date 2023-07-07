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

use PHPUnit\Framework\MockObject\MockObject;

final class InlineCommentRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private Dao&MockObject $dao;
    private InlineCommentRetriever $retriever;

    protected function setUp(): void
    {
        $this->dao = $this->createMock(Dao::class);

        $this->retriever = new InlineCommentRetriever($this->dao);
    }

    public function testInlineCommentCanBeRetrievedWhenItExists(): void
    {
        $this->dao->method('searchByCommentID')->willReturn([
            'id' => 12,
            'pull_request_id' => 147,
            'user_id' => 102,
            'post_date' => 10,
            'file_path' => 'path',
            'unidiff_offset' => 2,
            'content' => 'My comment',
            'is_outdated' => 0,
            'parent_id' => 0,
            'position' => 'right',
            "color" => '',
        ]);

        self::assertNotNull($this->retriever->getInlineCommentByID(12));
    }

    public function testInlineCommentCannotBeRetrievedWhenItDoesNotExist(): void
    {
        $this->dao->method('searchByCommentID')->willReturn(null);

        self::assertNull($this->retriever->getInlineCommentByID(404));
    }
}
