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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

final class InlineCommentRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Dao
     */
    private $dao;

    /**
     * @var InlineCommentRetriever
     */
    private $retriever;

    protected function setUp(): void
    {
        $this->dao = \Mockery::mock(Dao::class);

        $this->retriever = new InlineCommentRetriever($this->dao);
    }

    public function testInlineCommentCanBeRetrievedWhenItExists(): void
    {
        $this->dao->shouldReceive('searchByID')->andReturn([
            'id'              => 12,
            'pull_request_id' => 147,
            'user_id'         => 102,
            'post_date'       => 10,
            'file_path'       => 'path',
            'unidiff_offset'  => 2,
            'content'         => 'My comment',
            'is_outdated'     => 0
        ]);

        $this->assertNotNull($this->retriever->getInlineCommentByID(12));
    }

    public function testInlineCommentCannotBeRetrievedWhenItDoesNotExist(): void
    {
        $this->dao->shouldReceive('searchByID')->andReturn(null);

        $this->assertNull($this->retriever->getInlineCommentByID(404));
    }
}
