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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

class ForumRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItReturnsNullIfForumIsNotFound(): void
    {
        $project = Mockery::mock(\Project::class, ['getID' => 101]);
        $user    = Mockery::mock(\PFUser::class);

        $dao = Mockery::mock(ForumDao::class, ['searchActiveForum' => null]);

        $retriever = new ForumRetriever($dao);

        self::assertNull($retriever->getForumUserCanView(1, $project, $user));
    }

    public function testItReturnsNullIfForumIsPrivateAndUserIsNotAProjectMember(): void
    {
        $project = Mockery::mock(\Project::class, ['getID' => 101]);
        $user    = Mockery::mock(\PFUser::class, ['isMember' => false]);

        $dao = Mockery::mock(
            ForumDao::class,
            ['searchActiveForum' => ['forum_name' => 'Open Discussions', 'is_public' => 0]]
        );

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
        $project = Mockery::mock(\Project::class, ['getID' => 101]);
        $user    = Mockery::mock(\PFUser::class, ['isMember' => $is_member]);

        $dao = Mockery::mock(
            ForumDao::class,
            ['searchActiveForum' => ['forum_name' => 'Open Discussions', 'is_public' => $is_public]]
        );

        $retriever = new ForumRetriever($dao);

        $forum = $retriever->getForumUserCanView(1, $project, $user);

        self::assertEquals('Open Discussions', $forum->getName());
    }
}
