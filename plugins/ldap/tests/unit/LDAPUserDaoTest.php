<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Manuel VACELET, 2008.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\LDAP;

use DataAccessResult;
use LDAP_UserDao;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\DB\Compat\Legacy2018\LegacyDataAccessInterface;

require_once __DIR__ . '/bootstrap.php';

class LDAPUserDaoTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \LDAP_UserDao|\Mockery\Mock
     */
    private $dao;

    public function setUp(): void
    {
        parent::setUp();

        $this->dao = Mockery::mock(LDAP_UserDao::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
    }

    public function testUserNameIsAvailable(): void
    {
        $darUser = Mockery::mock(DataAccessResult::class);
        $darUser->shouldReceive('rowCount')->andReturns(0);
        $darGroup = Mockery::mock(DataAccessResult::class);
        $darGroup->shouldReceive('rowCount')->andReturns(0);

        $this->dao->da = Mockery::mock(LegacyDataAccessInterface::class);
        $this->dao->da->shouldReceive('quoteSmart')->times(2);
        $this->dao->shouldReceive('retrieve')->andReturns($darUser, $darGroup);

        $this->assertTrue($this->dao->userNameIsAvailable('coincoin'));
    }

    public function testUserNameIsAlreadyUser(): void
    {
        $darUser = Mockery::mock(DataAccessResult::class);
        $darUser->shouldReceive('rowCount')->andReturns(1);

        $this->dao->da = Mockery::mock(LegacyDataAccessInterface::class);
        $this->dao->da->shouldReceive('quoteSmart')->once();
        $this->dao->shouldReceive('retrieve')->once()->andReturns($darUser);

        $this->assertFalse($this->dao->userNameIsAvailable('coincoin'));
    }

    public function testUserNameIsAlreadyGroup(): void
    {
        $darUser = Mockery::mock(DataAccessResult::class);
        $darUser->shouldReceive('rowCount')->andReturns(0);
        $darGroup = Mockery::mock(DataAccessResult::class);
        $darGroup->shouldReceive('rowCount')->andReturns(1);

        $this->dao->da = Mockery::mock(LegacyDataAccessInterface::class);
        $this->dao->da->shouldReceive('quoteSmart')->times(2);
        $this->dao->shouldReceive('retrieve')->andReturns($darUser, $darGroup);

        $this->assertFalse($this->dao->userNameIsAvailable('coincoin'));
    }
}
