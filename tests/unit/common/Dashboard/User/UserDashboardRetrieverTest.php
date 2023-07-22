<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

use LogicException;
use Tuleap\Test\Builders\UserTestBuilder;

final class UserDashboardRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private \PFUser $user_without_dashboard;
    private \PFUser $user_with_a_dashboard;
    private UserDashboardRetriever $user_retriever;

    protected function setUp(): void
    {
        $dao = $this->createMock(\Tuleap\Dashboard\User\UserDashboardDao::class);

        $this->user_with_a_dashboard  = UserTestBuilder::aUser()->withId(1)->build();
        $this->user_without_dashboard = UserTestBuilder::aUser()->withId(2)->build();

        $dao->method('searchAllUserDashboards')->willReturnCallback(
            function (\PFUser $user_param): \Tuleap\FakeDataAccessResult {
                if ($user_param === $this->user_without_dashboard) {
                    return \TestHelper::emptyDar();
                } elseif ($user_param === $this->user_with_a_dashboard) {
                    return \TestHelper::arrayToDar([
                        'id'      => 1,
                        'user_id' => 1,
                        'name'    => 'dashboard_one',
                    ]);
                }

                throw new LogicException('must no be here.');
            }
        );

        $this->user_retriever = new UserDashboardRetriever($dao);
    }

    public function testItGetsAllDashboards(): void
    {
        $result = $this->user_retriever->getAllUserDashboards($this->user_with_a_dashboard);

        $expected_result = [
            new UserDashboard(1, 1, 'dashboard_one'),
        ];

        self::assertEquals($expected_result, $result);
    }

    public function testItReturnNothingIfThereAreNoDashboards(): void
    {
        $result = $this->user_retriever->getAllUserDashboards($this->user_without_dashboard);

        self::assertEmpty($result);
    }
}
