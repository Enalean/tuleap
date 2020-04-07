<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2004-2009. All rights reserved
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

class AdminDelegation_UserServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function setUp(): void
    {
        $this->user = \Mockery::spy(PFUser::class);
        $this->user->shouldReceive('getId')->andReturn(112);

        $this->user_service_dao     = \Mockery::spy(\AdminDelegation_UserServiceDao::class);
        $this->user_service_log_dao = \Mockery::spy(\AdminDelegation_UserServiceLogDao::class);

        $this->user_service_manager = new AdminDelegation_UserServiceManager(
            $this->user_service_dao,
            $this->user_service_log_dao
        );
    }

    public function testAddUserToPrivilegeList()
    {
        $this->user_service_dao->shouldReceive('addUserService')->with(112, AdminDelegation_Service::SHOW_PROJECT_ADMINS)->once()->andReturns(true);

        $this->user_service_log_dao->shouldReceive('addLog')->with('grant', AdminDelegation_Service::SHOW_PROJECT_ADMINS, 112, 1259333681)->once();

        $this->user_service_manager->addUserService($this->user, AdminDelegation_Service::SHOW_PROJECT_ADMINS, 1259333681);
    }

    public function testRevokeUserFromPrivilegeList()
    {
        $this->user_service_dao->shouldReceive('removeUser')->with(112)->once()->andReturns(true);
        $this->user_service_dao->shouldReceive('searchUser')->with(112)->once()->andReturns([
            ['service_id' => 101],
            ['service_id' => 102]
        ]);

        $this->user_service_log_dao->shouldReceive('addLog')->with('revoke', AdminDelegation_Service::SHOW_PROJECT_ADMINS, 112, 1259333681)->once();
        $this->user_service_log_dao->shouldReceive('addLog')->with('revoke', AdminDelegation_Service::SHOW_PROJECTS, 112, 1259333681)->once();

        $this->user_service_manager->removeUser($this->user, 1259333681);
    }
}
