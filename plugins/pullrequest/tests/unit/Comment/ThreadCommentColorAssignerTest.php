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

namespace Tuleap\PullRequest\Comment;

use Tuleap\PullRequest\Tests\Stub\ParentCommentSearcherStub;
use Tuleap\PullRequest\Tests\Stub\ThreadColorUpdaterStub;
use Tuleap\Test\PHPUnit\TestCase;

final class ThreadCommentColorAssignerTest extends TestCase
{
    private const PARENT_ID = 1;
    private ParentCommentSearcherStub $parent_comment_searcher;
    private ThreadColorUpdaterStub $color_updater;

    protected function setUp(): void
    {
        $this->parent_comment_searcher = ParentCommentSearcherStub::withNotFound();
        $this->color_updater           = ThreadColorUpdaterStub::withCallCount();
    }

    private function assignColor(int $parent_id, string $color_name): void
    {
        $color_assigner = new ThreadCommentColorAssigner($this->parent_comment_searcher, $this->color_updater);
        $color_assigner->assignColor($parent_id, $color_name);
    }

    public function testItDoesDoesNothingForRootComment(): void
    {
        $this->assignColor(0, ThreadColors::TLP_COLORS[0]);
        self::assertSame(0, $this->color_updater->getCallCount());
    }

    public function testItDoesNothingWhenReplyIsNotStartingAThread(): void
    {
        $this->parent_comment_searcher = ParentCommentSearcherStub::withParent(self::PARENT_ID, 456, '');

        $this->assignColor(self::PARENT_ID, ThreadColors::TLP_COLORS[0]);
        self::assertSame(0, $this->color_updater->getCallCount());
    }

    public function testItDoesNotAssignColorForExistingThreads(): void
    {
        $this->parent_comment_searcher = ParentCommentSearcherStub::withParent(self::PARENT_ID, 0, ThreadColors::TLP_COLORS[7]);

        $this->assignColor(self::PARENT_ID, ThreadColors::TLP_COLORS[1]);
        self::assertSame(0, $this->color_updater->getCallCount());
    }

    public function testItAssignColorToANewThread(): void
    {
        $this->parent_comment_searcher = ParentCommentSearcherStub::withParent(self::PARENT_ID, 0, '');
        $color_name                    = ThreadColors::TLP_COLORS[1];

        $this->assignColor(self::PARENT_ID, $color_name);
        self::assertSame(1, $this->color_updater->getCallCount());
        self::assertSame($color_name, $this->color_updater->getLastArgument());
    }
}
