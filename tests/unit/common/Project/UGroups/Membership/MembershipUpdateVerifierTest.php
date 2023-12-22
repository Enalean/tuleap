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

namespace Tuleap\Project\UGroups\Membership;

use Tuleap\Test\Builders\UserTestBuilder;

final class MembershipUpdateVerifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private MembershipUpdateVerifier $verifier;

    protected function setUp(): void
    {
        $this->verifier = new MembershipUpdateVerifier();
    }

    public function testAssertUGroupAndUserValidityThrowsWhenUGroupHasNoProject(): void
    {
        $user   = UserTestBuilder::buildWithDefaults();
        $ugroup = $this->createMock(\ProjectUGroup::class);
        $ugroup->method('getProjectId')->willReturn(0);

        self::expectException(InvalidProjectException::class);

        $this->verifier->assertUGroupAndUserValidity($user, $ugroup);
    }

    public function testAssertUGroupAndUserValidityThrowsWhenUGroupHasNoId(): void
    {
        $user   = UserTestBuilder::buildWithDefaults();
        $ugroup = $this->createMock(\ProjectUGroup::class);
        $ugroup->method('getProjectId')->willReturn(105);
        $ugroup->method('getId')->willReturn(0);

        self::expectException(\UGroup_Invalid_Exception::class);

        $this->verifier->assertUGroupAndUserValidity($user, $ugroup);
    }

    public function testAssertUGroupAndUserValidityThrowsWhenUserIsAnonymous(): void
    {
        $user   = UserTestBuilder::anAnonymousUser()->build();
        $ugroup = $this->createMock(\ProjectUGroup::class);
        $ugroup->method('getProjectId')->willReturn(105);
        $ugroup->method('getId')->willReturn(64);

        self::expectException(UserIsAnonymousException::class);

        $this->verifier->assertUGroupAndUserValidity($user, $ugroup);
    }

    public function testAssertUGroupAndUserValiditySucceedsOtherwise(): void
    {
        $user   = UserTestBuilder::anActiveUser()->build();
        $ugroup = $this->createMock(\ProjectUGroup::class);
        $ugroup->method('getProjectId')->willReturn(105);
        $ugroup->method('getId')->willReturn(64);

        $this->verifier->assertUGroupAndUserValidity($user, $ugroup);
        self::expectNotToPerformAssertions();
    }
}
