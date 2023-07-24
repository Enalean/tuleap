<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Forum;

use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class ForumRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItReturnsNullIfForumIsNotFound(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(101)->build();
        $user    = UserTestBuilder::aUser()->build();

        $dao = $this->createMock(ForumDao::class);
        $dao->method('searchActiveForum')->willReturn(null);

        $retriever = new ForumRetriever($dao);

        self::assertNull($retriever->getForumUserCanView(1, $project, $user));
    }

    public function testItReturnsNullIfForumIsPrivateAndUserIsNotAProjectMember(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(101)->build();
        $user    = $this->createMock(\PFUser::class);
        $user->method('isMember')->willReturn(false);

        $dao = $this->createMock(ForumDao::class);
        $dao->method('searchActiveForum')->willReturn(['forum_name' => 'Open Discussions', 'is_public' => 0]);

        $retriever = new ForumRetriever($dao);

        self::assertNull($retriever->getForumUserCanView(1, $project, $user));
    }

    /**
     * @testWith [false, 1]
     *           [true, 0]
     *           [true, 1]
     */
    public function testItReturnsTheForumInOtherCases(bool $is_member, int $is_public): void
    {
        $project = ProjectTestBuilder::aProject()->withId(101)->build();
        $user    = $this->createMock(\PFUser::class);
        $user->method('isMember')->willReturn($is_member);

        $dao = $this->createMock(ForumDao::class);
        $dao->method('searchActiveForum')->willReturn(['forum_name' => 'Open Discussions', 'is_public' => $is_public]);

        $retriever = new ForumRetriever($dao);

        $forum = $retriever->getForumUserCanView(1, $project, $user);

        self::assertEquals('Open Discussions', $forum->getName());
    }
}
