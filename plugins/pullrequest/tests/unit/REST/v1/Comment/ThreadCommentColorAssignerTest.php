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
use Tuleap\Test\PHPUnit\TestCase;

final class ThreadCommentColorAssignerTest extends TestCase
{
    /**
     * @var Dao&\PHPUnit\Framework\MockObject\MockObject
     */
    private $global_dao;
    private ThreadCommentColorAssigner $color_assigner;

    protected function setUp(): void
    {
        $this->global_dao     = $this->createMock(Dao::class);
        $this->color_assigner = new ThreadCommentColorAssigner($this->global_dao, $this->global_dao);
    }

    public function testItDoesDoesNothingForRootComment(): void
    {
        $this->global_dao
            ->expects(self::never())
            ->method('setThreadColor');
        $this->global_dao->method('searchByCommentID')->willReturn(['parent_id' => 456, 'id' => 1, "color" => ""]);
        $this->color_assigner->assignColor(1, "graffity-yellow");
    }

    public function testItDoesNothingWhenReplyIsNOtStartingAThread(): void
    {
        $this->global_dao
            ->expects(self::never())
            ->method('setThreadColor');
        $this->global_dao->method('searchByCommentID')->willReturn(['parent_id' => 456, 'id' => 1, "color" => ""]);
        $this->color_assigner->assignColor(1, "graffity-yellow");
    }

    public function testItDoesNotAssignColorForExistingThreads(): void
    {
        $parent_row = ['parent_id' => 0, 'id' => 1, "color" => "flamingo_pink"];
        $this->global_dao->method('searchByCommentID')->willReturn($parent_row);
        $this->global_dao
            ->expects(self::never())
            ->method('setThreadColor');
        $this->color_assigner->assignColor(1, "daphne-blue");
    }

    public function testItAssignColorToANewThread(): void
    {
        $parent_row = ['parent_id' => 0, 'id' => 1, 'color' => ''];
        $this->global_dao->method('searchByCommentID')->willReturn($parent_row);
        $this->global_dao->expects(self::once())->method("setThreadColor")->with($parent_row['id'], "daphne-blue");
        $this->color_assigner->assignColor(1, "daphne-blue");
    }
}
