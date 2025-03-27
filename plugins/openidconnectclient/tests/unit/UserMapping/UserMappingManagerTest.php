<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

namespace Tuleap\OpenIDConnectClient\UserMapping;

use Tuleap\FakeDataAccessResult;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class UserMappingManagerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private UserMappingManager $user_mapping_manager;
    private UserMappingDao&\PHPUnit\Framework\MockObject\MockObject $dao;
    private \UserDao&\PHPUnit\Framework\MockObject\MockObject $user_dao;
    private CanRemoveUserMappingChecker&\PHPUnit\Framework\MockObject\MockObject $can_remove_user_mapping_checker;

    protected function setUp(): void
    {
        $this->dao                             = $this->createMock(UserMappingDao::class);
        $this->user_dao                        = $this->createMock(\UserDao::class);
        $this->can_remove_user_mapping_checker = $this->createMock(CanRemoveUserMappingChecker::class);
        $this->user_mapping_manager            = new UserMappingManager($this->dao, $this->user_dao, $this->can_remove_user_mapping_checker, new DBTransactionExecutorPassthrough());
    }

    public function testItThrowsAnExceptionIfTheMappingCanNotBeFound(): void
    {
        $this->dao->method('searchByProviderIdAndUserId')->willReturn(false);
        $provider = $this->createMock(\Tuleap\OpenIDConnectClient\Provider\Provider::class);
        $provider->method('getId');

        $user = UserTestBuilder::buildWithDefaults();

        $this->expectException(UserMappingNotFoundException::class);
        $this->user_mapping_manager->getByProviderAndUser($provider, $user);
    }

    public function testCannotRemoveMappingWhenGivenUserDoesNotMatchTheMapping(): void
    {
        $user         = UserTestBuilder::aUser()->withId(102)->build();
        $user_mapping = new UserMapping(1, 103, 1, 'identifier', 10);

        $this->expectException(\InvalidArgumentException::class);
        $this->user_mapping_manager->remove($user, $user_mapping);
    }

    public function testDoesNotRemoveMappingWhenItIsNotAcceptable(): void
    {
        $user         = UserTestBuilder::aUser()->withId(102)->build();
        $user_mapping = new UserMapping(1, 102, 1, 'identifier', 10);

        $this->dao->method('searchUsageByUserId')->willReturn(\TestHelper::emptyDar());
        $this->can_remove_user_mapping_checker->method('canAUserMappingBeRemoved')->willReturn(false);

        $this->expectException(UserMappingDataAccessException::class);
        $this->user_mapping_manager->remove($user, $user_mapping);
    }

    public function testCanRemoveMapping(): void
    {
        $user         = UserTestBuilder::aUser()->withId(102)->build();
        $user_mapping = new UserMapping(1, 102, 1, 'identifier', 10);

        $this->dao->method('searchUsageByUserId')->willReturn(\TestHelper::emptyDar());
        $this->can_remove_user_mapping_checker->method('canAUserMappingBeRemoved')->willReturn(true);
        $this->dao->expects($this->once())->method('deleteById')->willReturn(true);

        $this->user_mapping_manager->remove($user, $user_mapping);
    }

    public function testUpdatesLastUsedInformation(): void
    {
        $this->user_dao->expects($this->once())->method('storeLoginSuccess');
        $this->dao->expects($this->once())->method('updateLastUsed')->willReturn(true);

        $user_mapping = new UserMapping(1, 102, 1, 'identifier', 10);

        $this->user_mapping_manager->updateLastUsed($user_mapping, 20);
    }

    public function testCreatesAMapping(): void
    {
        $this->user_dao->expects($this->once())->method('storeLoginSuccess');
        $this->dao->expects($this->once())->method('save')->willReturn(true);

        $this->user_mapping_manager->create(102, 1, 'identifier', 10);
    }

    public function testCannotCreateAMappingWithASpecialUser(): void
    {
        $this->expectException(CannotCreateAMappingForASpecialUserException::class);
        $this->user_mapping_manager->create(10, 1, 'identifier', 10);
    }

    public function testItDoesntHaveMappingWhenDBIsMad(): void
    {
        $user = UserTestBuilder::aUser()->withId(102)->build();
        $this->dao->method('searchUsageByUserId')->with(102)->willReturn(false);

        self::assertFalse($this->user_mapping_manager->userHasProvider($user));
    }

    public function testItDoesntHaveMappingWhenDBReturnsNoResults(): void
    {
        $user = UserTestBuilder::aUser()->withId(102)->build();
        $this->dao->method('searchUsageByUserId')->with(102)->willReturn(new \DataAccessResultEmpty());

        self::assertFalse($this->user_mapping_manager->userHasProvider($user));
    }

    public function testItHasMapping(): void
    {
        $user = UserTestBuilder::aUser()->withId(102)->build();
        $this->dao->method('searchUsageByUserId')->with(102)->willReturn(new FakeDataAccessResult([1]));

        self::assertTrue($this->user_mapping_manager->userHasProvider($user));
    }
}
