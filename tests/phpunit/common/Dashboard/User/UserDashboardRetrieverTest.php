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

class UserDashboardRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var \PFUser */
    private $user_without_dashboard;

    /** @var \PFUser */
    private $user_with_a_dashboard;

    /** @var UserDashboardRetriever */
    private $user_retriever;

    protected function setUp(): void
    {
        $dao = \Mockery::spy(\Tuleap\Dashboard\User\UserDashboardDao::class);

        $this->user_with_a_dashboard = \Mockery::spy(\PFUser::class);
        $this->user_with_a_dashboard->shouldReceive('getId')->andReturns(1);

        $this->user_without_dashboard = \Mockery::spy(\PFUser::class);
        $this->user_without_dashboard->shouldReceive('getId')->andReturns(2);

        $dao->shouldReceive('searchAllUserDashboards')->with($this->user_with_a_dashboard)->andReturns(\TestHelper::arrayToDar(array(
            'id'      => 1,
            'user_id' => 1,
            'name'    => 'dashboard_one'
        )));
        $dao->shouldReceive('searchAllUserDashboards')->with($this->user_without_dashboard)->andReturns(\TestHelper::emptyDar());

        $this->user_retriever = new UserDashboardRetriever($dao);
    }

    public function testItGetsAllDashboards()
    {
        $result = $this->user_retriever->getAllUserDashboards($this->user_with_a_dashboard);

        $expected_result = array(
            new UserDashboard(1, 1, 'dashboard_one')
        );

        $this->assertEquals($expected_result, $result);
    }

    public function testItReturnNothingIfThereAreNoDashboards()
    {
        $result = $this->user_retriever->getAllUserDashboards($this->user_without_dashboard);

        $this->assertEmpty($result);
    }
}
