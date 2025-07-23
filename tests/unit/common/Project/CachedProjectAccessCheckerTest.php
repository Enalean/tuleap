<?php
/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Project;

use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CachedProjectAccessCheckerTest extends TestCase
{
    public function testItCachesSuccess(): void
    {
        $original_check = new class implements CheckProjectAccess
        {
            public int $call_count = 0;
            #[\Override]
            public function checkUserCanAccessProject(\PFUser $user, \Project $project): void
            {
                $this->call_count++;
            }
        };

        $cached_check = new CachedProjectAccessChecker($original_check);

        $cached_check->checkUserCanAccessProject(UserTestBuilder::aUser()->withId(101)->build(), ProjectTestBuilder::aProject()->withId(102)->build());
        $cached_check->checkUserCanAccessProject(UserTestBuilder::aUser()->withId(101)->build(), ProjectTestBuilder::aProject()->withId(102)->build());

        self::assertEquals(1, $original_check->call_count);
    }

    public function testItCachesOnlyForOneProjectAndOneUserSuccess(): void
    {
        $original_check = new class implements CheckProjectAccess
        {
            public array $called = [];
            #[\Override]
            public function checkUserCanAccessProject(\PFUser $user, \Project $project): void
            {
                $this->called[$user->getId()][$project->getID()] = true;
            }
        };

        $cached_check = new CachedProjectAccessChecker($original_check);

        $cached_check->checkUserCanAccessProject(UserTestBuilder::aUser()->withId(101)->build(), ProjectTestBuilder::aProject()->withId(102)->build());
        $cached_check->checkUserCanAccessProject(UserTestBuilder::aUser()->withId(350)->build(), ProjectTestBuilder::aProject()->withId(573)->build());

        self::assertEquals(true, $original_check->called[101][102]);
        self::assertEquals(true, $original_check->called[350][573]);
    }

    public function testItThrowsExceptions(): void
    {
        $original_check = new class implements CheckProjectAccess
        {
            #[\Override]
            public function checkUserCanAccessProject(\PFUser $user, \Project $project): void
            {
                throw new \Project_AccessDeletedException();
            }
        };

        $cached_check = new CachedProjectAccessChecker($original_check);

        $this->expectException(\Project_AccessDeletedException::class);

        $cached_check->checkUserCanAccessProject(UserTestBuilder::aUser()->withId(101)->build(), ProjectTestBuilder::aProject()->withId(102)->build());
    }

    public function testItThrowsCachedExceptions(): void
    {
        $original_check = new class implements CheckProjectAccess
        {
            public int $nb_called = 0;
            #[\Override]
            public function checkUserCanAccessProject(\PFUser $user, \Project $project): void
            {
                $this->nb_called++;
                throw new \Project_AccessDeletedException();
            }
        };

        $cached_check = new CachedProjectAccessChecker($original_check);

        try {
            $cached_check->checkUserCanAccessProject(UserTestBuilder::aUser()->withId(101)->build(), ProjectTestBuilder::aProject()->withId(102)->build());
        } catch (\Project_AccessDeletedException) {
        }

        $this->expectException(\Project_AccessDeletedException::class);

        try {
            $cached_check->checkUserCanAccessProject(UserTestBuilder::aUser()->withId(101)->build(), ProjectTestBuilder::aProject()->withId(102)->build());
        } catch (\Project_AccessDeletedException $e) {
            self::assertEquals(1, $original_check->nb_called);
            throw $e;
        }
    }
}
