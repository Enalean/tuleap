<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Workspace;

use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\UserCanPrioritize;
use Tuleap\ProgramManagement\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Stub\UserPermissionsStub;
use Tuleap\ProgramManagement\Stub\VerifyPrioritizeFeaturesPermissionStub;
use Tuleap\Test\Builders\UserTestBuilder;

final class UserIdentifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItBuildsFromId(): void
    {
        $user = UserIdentifier::fromId(118);
        self::assertSame(118, $user->id);
    }

    public function testItBuildsFromPFUser(): void
    {
        $pfuser = UserTestBuilder::aUser()->withId(101)->build();
        $user   = UserIdentifier::fromPFUser($pfuser);
        self::assertSame(101, $user->id);
    }

    public function testItBuildsFromUserCanPrioritize(): void
    {
        $first_user_identifier = UserIdentifier::fromId(164);
        $program               = ProgramIdentifier::fromId(
            BuildProgramStub::stubValidProgram(),
            101,
            $first_user_identifier
        );
        $can_prioritize        = UserCanPrioritize::fromUser(
            VerifyPrioritizeFeaturesPermissionStub::canPrioritize(),
            UserPermissionsStub::aRegularUser(),
            $first_user_identifier,
            $program
        );

        $second_user_identifier = UserIdentifier::fromUserCanPrioritize($can_prioritize);
        self::assertSame($first_user_identifier->id, $second_user_identifier->id);
    }
}
