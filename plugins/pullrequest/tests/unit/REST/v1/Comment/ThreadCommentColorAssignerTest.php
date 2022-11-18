<?php
/**
 * Copyright (c) Enalean 2022 - Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\REST\v1\Comment;

use Tuleap\PullRequest\Comment\Dao;
use Tuleap\PullRequest\Comment\ThreadCommentDao;
use Tuleap\Test\PHPUnit\TestCase;

final class ThreadCommentColorAssignerTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|Dao|Dao&\PHPUnit\Framework\MockObject\MockObject
     */
    private $global_dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ThreadCommentDao|ThreadCommentDao&\PHPUnit\Framework\MockObject\MockObject
     */
    private $thread_global;
    private ThreadCommentColorAssigner $color_assigner;

    protected function setUp(): void
    {
        $this->global_dao     = $this->createMock(Dao::class);
        $this->thread_global  = $this->createMock(ThreadCommentDao::class);
        $this->color_assigner = new ThreadCommentColorAssigner($this->global_dao, $this->thread_global, $this->global_dao);
    }

    public function testItDoesDoesNothingForRootComment(): void
    {
        $this->global_dao
            ->expects(self::never())
            ->method('setThreadColor');
        $this->color_assigner->assignColor(1, 0);
    }

    public function testItDoesNothingWhenReplyIsNOtStartingAThread(): void
    {
        $this->global_dao
            ->expects(self::never())
            ->method('setThreadColor');
        $this->global_dao->method('searchByCommentID')->willReturn(['parent_id' => 456, 'id' => 1]);
        $this->color_assigner->assignColor(1, 123);
    }

    public function testItAssignColorToANewThread(): void
    {
        $parent_row = ['parent_id' => 0, 'id' => 1];
        $this->global_dao->method('searchByCommentID')->willReturn($parent_row);
        $this->thread_global->method('searchAllThreadByPullRequestId')->willReturn([1]);

        $this->global_dao->expects(self::once())->method("setThreadColor")->with($parent_row['id'], "daphne-blue");
        $this->color_assigner->assignColor(1, 123);
    }
}
