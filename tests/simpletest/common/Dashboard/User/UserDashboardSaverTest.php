<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Dashboard\User;

class UserDashboardSaverTest extends \TuleapTestCase
{
    /** @var UserDashboardDao */
    private $dao;

    /** @var \PFUser */
    private $user;

    /** @var UserDashboardSaver */
    private $user_saver;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $this->dao  = \Mockery::spy(\Tuleap\Dashboard\User\UserDashboardDao::class);
        $this->user = aUser()->withId(1)->build();

        $this->dao->shouldReceive('searchByUserIdAndName')->with($this->user, 'new_dashboard')->andReturns(\TestHelper::emptyDar());
        $this->dao->shouldReceive('searchByUserIdAndName')->with($this->user, 'existing_dashboard')->andReturns(\TestHelper::arrayToDar(array(
            'id'      => 1,
            'user_id' => 1,
            'name'    => 'existing_dashboard'
        )));

        $this->user_saver = new UserDashboardSaver($this->dao);
    }

    public function itSavesDashboard()
    {
        $this->dao->shouldReceive('save')->with($this->user, 'new_dashboard')->once();

        $this->user_saver->save($this->user, 'new_dashboard');
    }

    public function itThrowsExceptionWhenDashboardExists()
    {
        $this->dao->shouldReceive('save')->never();
        $this->expectException('Tuleap\Dashboard\NameDashboardAlreadyExistsException');

        $this->user_saver->save($this->user, 'existing_dashboard');
    }

    public function itThrowsExceptionWhenNameDoesNotExist()
    {
        $this->dao->shouldReceive('save')->never();
        $this->expectException('Tuleap\Dashboard\NameDashboardDoesNotExistException');

        $this->user_saver->save($this->user, '');
    }
}
