<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ProjectOwnership\ProjectOwner;

use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Widget\Event\UserWithStarBadgeCollector;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class UserWithStarBadgeFinderTest extends TestCase
{
    public function testItDoesNothingIfProjectHasNoOwnership(): void
    {
        $project = new \Project(['group_id' => 123]);

        $collector = new UserWithStarBadgeCollector(
            $project,
            [
                UserTestBuilder::aUser()->withId(101)->build(),
            ]
        );

        $dao = $this->createMock(ProjectOwnerDAO::class);
        $dao->method('searchByProjectID')
            ->with($project->getID())
            ->willReturn(null);

        $finder = new UserWithStarBadgeFinder($dao);
        $finder->findBadgedUser($collector);

        self::assertNull($collector->getUserWithStarBadge());
    }

    public function testItBadgesProjectOwner(): void
    {
        $project = new \Project(['group_id' => 123]);
        $user    = UserTestBuilder::aUser()->withId(101)->build();
        $other   = UserTestBuilder::aUser()->withId(102)->build();

        $collector = new UserWithStarBadgeCollector(
            $project,
            [$user]
        );

        $dao = $this->createMock(ProjectOwnerDAO::class);
        $dao->method('searchByProjectID')
            ->with($project->getID())
            ->willReturn(['project_id' => 123, 'user_id' => 101]);

        $finder = new UserWithStarBadgeFinder($dao);
        $finder->findBadgedUser($collector);

        $user_with_star_badge = $collector->getUserWithStarBadge();
        self::assertNotNull($user_with_star_badge);
        self::assertTrue($user_with_star_badge->isUserBadged($user));
        self::assertFalse($user_with_star_badge->isUserBadged($other));
    }
}
