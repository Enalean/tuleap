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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class UserDashboardSaverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var UserDashboardDao */
    private $dao;

    /** @var \PFUser */
    private $user;

    /** @var UserDashboardSaver */
    private $user_saver;

    protected function setUp(): void
    {
        $this->dao  = \Mockery::spy(\Tuleap\Dashboard\User\UserDashboardDao::class);
        $this->user = new \PFUser(['user_id' => 1, 'language_id' => 'en_US']);

        $this->dao->shouldReceive('searchByUserIdAndName')->with($this->user, 'new_dashboard')->andReturns(\TestHelper::emptyDar());
        $this->dao->shouldReceive('searchByUserIdAndName')->with($this->user, 'existing_dashboard')->andReturns(\TestHelper::arrayToDar([
            'id'      => 1,
            'user_id' => 1,
            'name'    => 'existing_dashboard'
        ]));

        $this->user_saver = new UserDashboardSaver($this->dao);
    }

    public function testItSavesDashboard()
    {
        $this->dao->shouldReceive('save')->with($this->user, 'new_dashboard')->once();

        $this->user_saver->save($this->user, 'new_dashboard');
    }

    public function testItThrowsExceptionWhenDashboardExists()
    {
        $this->dao->shouldReceive('save')->never();
        $this->expectException('Tuleap\Dashboard\NameDashboardAlreadyExistsException');

        $this->user_saver->save($this->user, 'existing_dashboard');
    }

    public function testItThrowsExceptionWhenNameDoesNotExist()
    {
        $this->dao->shouldReceive('save')->never();
        $this->expectException('Tuleap\Dashboard\NameDashboardDoesNotExistException');

        $this->user_saver->save($this->user, '');
    }
}
