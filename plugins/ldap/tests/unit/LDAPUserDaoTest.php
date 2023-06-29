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
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\DB\Compat\Legacy2018\LegacyDataAccessInterface;

final class LDAPUserDaoTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private MockObject&\LDAP_UserDao $dao;

    public function setUp(): void
    {
        parent::setUp();

        $this->dao = $this->getMockBuilder(LDAP_UserDao::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['retrieve'])
            ->getMock();
    }

    public function testUserNameIsAvailable(): void
    {
        $darUser = $this->createMock(DataAccessResult::class);
        $darUser->method('rowCount')->willReturn(0);
        $darGroup = $this->createMock(DataAccessResult::class);
        $darGroup->method('rowCount')->willReturn(0);

        $this->dao->da = $this->createMock(LegacyDataAccessInterface::class);
        $this->dao->da->expects(self::exactly(2))->method('quoteSmart');
        $this->dao->method('retrieve')->willReturn($darUser, $darGroup);

        self::assertTrue($this->dao->userNameIsAvailable('coincoin'));
    }

    public function testUserNameIsAlreadyUser(): void
    {
        $darUser = $this->createMock(DataAccessResult::class);
        $darUser->method('rowCount')->willReturn(1);

        $this->dao->da = $this->createMock(LegacyDataAccessInterface::class);
        $this->dao->da->expects(self::once())->method('quoteSmart');
        $this->dao->expects(self::once())->method('retrieve')->willReturn($darUser);

        self::assertFalse($this->dao->userNameIsAvailable('coincoin'));
    }

    public function testUserNameIsAlreadyGroup(): void
    {
        $darUser = $this->createMock(DataAccessResult::class);
        $darUser->method('rowCount')->willReturn(0);
        $darGroup = $this->createMock(DataAccessResult::class);
        $darGroup->method('rowCount')->willReturn(1);

        $this->dao->da = $this->createMock(LegacyDataAccessInterface::class);
        $this->dao->da->expects(self::exactly(2))->method('quoteSmart');
        $this->dao->method('retrieve')->willReturn($darUser, $darGroup);

        self::assertFalse($this->dao->userNameIsAvailable('coincoin'));
    }
}
