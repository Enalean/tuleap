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

class UserDashboardRetrieverTest extends \TuleapTestCase
{
    /** @var \PFUser */
    private $user_without_dashboard;

    /** @var \PFUser */
    private $user_with_a_dashboard;

    /** @var UserDashboardRetriever */
    private $user_retriever;

    public function setUp()
    {
        parent::setUp();

        $dao = mock('Tuleap\Dashboard\User\UserDashboardDao');

        $this->user_with_a_dashboard = mock('PFUser');
        stub($this->user_with_a_dashboard)->getId()->returns(1);

        $this->user_without_dashboard = mock('PFUser');
        stub($this->user_without_dashboard)->getId()->returns(2);

        stub($dao)->searchAllUserDashboards($this->user_with_a_dashboard)->returnsDar(array(
            'id'      => 1,
            'user_id' => 1,
            'name'    => 'dashboard_one'
        ));
        stub($dao)->searchAllUserDashboards($this->user_without_dashboard)->returnsEmptyDar();

        $this->user_retriever = new UserDashboardRetriever($dao);
    }

    public function itGetsAllDashboards()
    {
        $result = $this->user_retriever->getAllUserDashboards($this->user_with_a_dashboard);

        $expected_result = array(
            new UserDashboard(1, 1, 'dashboard_one')
        );

        $this->assertEqual($expected_result, $result);
    }

    public function itReturnNothingIfThereAreNoDashboards()
    {

        $result = $this->user_retriever->getAllUserDashboards($this->user_without_dashboard);

        $this->assertArrayEmpty($result);
    }
}
