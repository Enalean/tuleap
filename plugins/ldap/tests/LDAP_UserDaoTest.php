<?php
/**
 * Copyright (c) Enalean, 2017-2018. All Rights Reserved.
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

class LDAP_UserDaoTest extends TuleapTestCase
{
    private $dao;

    public function setUp()
    {
        parent::setUp();

        $this->dao = partial_mock(LDAP_UserDao::class, ['retrieve']);
    }

    public function testUserNameIsAvailable()
    {
        $darUser = mock(DataAccessResult::class);
        $darUser->setReturnValue('rowCount', 0);
        $darGroup = mock(DataAccessResult::class);
        $darGroup->setReturnValue('rowCount', 0);
        
        $this->dao->da = mock(\Tuleap\DB\Compat\Legacy2018\LegacyDataAccessInterface::class);
        $this->dao->da->expectCallCount('quoteSmart', 2);
        $this->dao->setReturnValueAt(0, 'retrieve', $darUser);
        $this->dao->setReturnValueAt(1, 'retrieve', $darGroup);
        $this->dao->expectCallCount('retrieve', 2);
        
        $this->assertTrue($this->dao->userNameIsAvailable('coincoin'));
    }
    
    public function testUserNameIsAlreadyUser()
    {
        $darUser = mock(DataAccessResult::class);
        $darUser->setReturnValue('rowCount', 1);
        
        $this->dao->da = mock(\Tuleap\DB\Compat\Legacy2018\LegacyDataAccessInterface::class);
        $this->dao->da->expectCallCount('quoteSmart', 1);
        $this->dao->setReturnValueAt(0, 'retrieve', $darUser);
        $this->dao->expectCallCount('retrieve', 1);
        
        $this->assertFalse($this->dao->userNameIsAvailable('coincoin'));
    }
    
    public function testUserNameIsAlreadyGroup()
    {
        $darUser = mock(DataAccessResult::class);
        $darUser->setReturnValue('rowCount', 0);
        $darGroup = mock(DataAccessResult::class);
        $darGroup->setReturnValue('rowCount', 1);
        
        $this->dao->da = mock(\Tuleap\DB\Compat\Legacy2018\LegacyDataAccessInterface::class);
        $this->dao->da->expectCallCount('quoteSmart', 2);
        $this->dao->setReturnValueAt(0, 'retrieve', $darUser);
        $this->dao->setReturnValueAt(1, 'retrieve', $darGroup);
        $this->dao->expectCallCount('retrieve', 2);
        
        $this->assertFalse($this->dao->userNameIsAvailable('coincoin'));
    }
}
