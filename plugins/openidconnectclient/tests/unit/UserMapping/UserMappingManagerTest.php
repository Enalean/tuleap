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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\FakeDataAccessResult;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;

final class UserMappingManagerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|UserMappingDao
     */
    private $dao;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\UserDao
     */
    private $user_dao;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|CanRemoveUserMappingChecker
     */
    private $can_remove_user_mapping_checker;
    /**
     * @var UserMappingManager
     */
    private $user_mapping_manager;

    protected function setUp(): void
    {
        $this->dao                             = \Mockery::mock(UserMappingDao::class);
        $this->user_dao                        = \Mockery::mock(\UserDao::class);
        $this->can_remove_user_mapping_checker = \Mockery::mock(CanRemoveUserMappingChecker::class);
        $this->user_mapping_manager = new UserMappingManager($this->dao, $this->user_dao, $this->can_remove_user_mapping_checker, new DBTransactionExecutorPassthrough());
    }

    public function testItThrowsAnExceptionIfTheMappingCanNotBeFound(): void
    {
        $this->dao->shouldReceive('searchByProviderIdAndUserId')->andReturns(false);
        $provider = \Mockery::spy(\Tuleap\OpenIDConnectClient\Provider\Provider::class);
        $user     = \Mockery::spy(\PFUser::class);

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

        $this->dao->shouldReceive('searchUsageByUserId')->andReturn(\TestHelper::emptyDar());
        $this->can_remove_user_mapping_checker->shouldReceive('canAUserMappingBeRemoved')->andReturn(false);

        $this->expectException(UserMappingDataAccessException::class);
        $this->user_mapping_manager->remove($user, $user_mapping);
    }

    public function testCanRemoveMapping(): void
    {
        $user         = UserTestBuilder::aUser()->withId(102)->build();
        $user_mapping = new UserMapping(1, 102, 1, 'identifier', 10);

        $this->dao->shouldReceive('searchUsageByUserId')->andReturn(\TestHelper::emptyDar());
        $this->can_remove_user_mapping_checker->shouldReceive('canAUserMappingBeRemoved')->andReturn(true);
        $this->dao->shouldReceive('deleteById')->once()->andReturn(true);

        $this->user_mapping_manager->remove($user, $user_mapping);
    }

    public function testUpdatesLastUsedInformation(): void
    {
        $this->user_dao->shouldReceive('storeLoginSuccess')->once();
        $this->dao->shouldReceive('updateLastUsed')->once()->andReturn(true);

        $user_mapping = new UserMapping(1, 102, 1, 'identifier', 10);

        $this->user_mapping_manager->updateLastUsed($user_mapping, 20);
    }

    public function testCreatesAMapping(): void
    {
        $this->user_dao->shouldReceive('storeLoginSuccess')->once();
        $this->dao->shouldReceive('save')->once()->andReturn(true);

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
        $this->dao->shouldReceive('searchUsageByUserId')->with(102)->andReturn(false);

        self::assertFalse($this->user_mapping_manager->userHasProvider($user));
    }

    public function testItDoesntHaveMappingWhenDBReturnsNoResults(): void
    {
        $user = UserTestBuilder::aUser()->withId(102)->build();
        $this->dao->shouldReceive('searchUsageByUserId')->with(102)->andReturn(new \DataAccessResultEmpty());

        self::assertFalse($this->user_mapping_manager->userHasProvider($user));
    }

    public function testItHasMapping(): void
    {
        $user = UserTestBuilder::aUser()->withId(102)->build();
        $this->dao->shouldReceive('searchUsageByUserId')->with(102)->andReturn(new FakeDataAccessResult([1]));

        self::assertTrue($this->user_mapping_manager->userHasProvider($user));
    }
}
