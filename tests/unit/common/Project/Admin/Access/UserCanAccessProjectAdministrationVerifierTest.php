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

namespace Tuleap\Project\Admin\Access;

use Tuleap\Project\Admin\MembershipDelegationDao;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\AnonymousUserTestProvider;
use Tuleap\User\CurrentUserWithLoggedInInformation;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class UserCanAccessProjectAdministrationVerifierTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\Stub|MembershipDelegationDao
     */
    private $membership_delegation_dao;
    private \Project $project;

    protected function setUp(): void
    {
        $this->membership_delegation_dao = $this->createStub(MembershipDelegationDao::class);
        $this->project                   = ProjectTestBuilder::aProject()->withId(101)->build();
    }

    public function testItReturnsFalseWhenUserIsAnonymous(): void
    {
        self::assertFalse(
            $this->getVerifier()->canUserAccessProjectAdministration(
                CurrentUserWithLoggedInInformation::fromAnonymous(new AnonymousUserTestProvider()),
                $this->project
            )
        );
    }

    public function testItReturnsTrueWhenUserIsSiteAdministrator(): void
    {
        $user = $this->createStub(\PFUser::class);
        $user->method('isSuperUser')->willReturn(true);
        $user->method('isAnonymous')->willReturn(false);
        self::assertTrue($this->getVerifier()->canUserAccessProjectAdministration(CurrentUserWithLoggedInInformation::fromLoggedInUser($user), $this->project));
    }

    public function testItReturnsTrueWhenUserIsProjectAdministrator(): void
    {
        $user = $this->createStub(\PFUser::class);
        $user->method('isSuperUser')->willReturn(false);
        $user->method('isAdmin')->willReturn(true);
        $user->method('isAnonymous')->willReturn(false);
        self::assertTrue($this->getVerifier()->canUserAccessProjectAdministration(CurrentUserWithLoggedInInformation::fromLoggedInUser($user), $this->project));
    }

    public function testItReturnsTrueWhenUserHasDelegatedAdministrationPermission(): void
    {
        $user = $this->buildUserNotAdministrator();
        $this->membership_delegation_dao->method('doesUserHasMembershipDelegation')->willReturn(true);
        self::assertTrue($this->getVerifier()->canUserAccessProjectAdministration($user, $this->project));
    }

    public function testItReturnsFalseOtherwise(): void
    {
        $user = $this->buildUserNotAdministrator();
        $this->membership_delegation_dao->method('doesUserHasMembershipDelegation')->willReturn(false);
        self::assertFalse($this->getVerifier()->canUserAccessProjectAdministration($user, $this->project));
    }

    private function buildUserNotAdministrator(): CurrentUserWithLoggedInInformation
    {
        $user = $this->createStub(\PFUser::class);
        $user->method('isSuperUser')->willReturn(false);
        $user->method('isAdmin')->willReturn(false);
        $user->method('getId')->willReturn(201);
        $user->method('isAnonymous')->willReturn(false);

        return CurrentUserWithLoggedInInformation::fromLoggedInUser($user);
    }

    private function getVerifier(): VerifyUserCanAccessProjectAdministration
    {
        return new UserCanAccessProjectAdministrationVerifier($this->membership_delegation_dao);
    }
}
