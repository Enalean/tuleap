<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
 *
 */

namespace Tuleap\Dashboard;

use PHPUnit\Framework\TestCase;

class DashboardTest extends TestCase
{

    public function dashboardLayoutProvider()
    {
        $dashboard = new Dashboard(1, 'foo');
        return [
            [ $dashboard, '', -1, true ],
            [ $dashboard, '', 0, true ],
            [ $dashboard, 'one-column', 0, false ],
            [ $dashboard, 'sdf', 0, false ],
            [ $dashboard, 'one-column', 1, true ],
            [ $dashboard, 'one-column', 2, false ],
            [ $dashboard, 'two-columns', 2, true ],
            [ $dashboard, 'two-columns', 1, false ],
            [ $dashboard, 'two-columns-foo', 2, false ],
            [ $dashboard, 'two-columns-small-big', 2, true ],
            [ $dashboard, 'two-columns-small-big', 3, false ],
            [ $dashboard, 'three-columns', 1, false ],
            [ $dashboard, 'three-columns', 2, false ],
            [ $dashboard, 'three-columns', 3, true ],
            [ $dashboard, 'three-columns', 4, false ],
            [ $dashboard, 'three-columns-big-small-small', 3, true ],
            [ $dashboard, 'three-columns-big-small--small', 3, false ],
            [ $dashboard, 'three-columns-big-small-small', 4, false ],
            [ $dashboard, 'too-many-columns', 4, true ],
            [ $dashboard, 'too-many-columns-foo', 4, false ],
            [ $dashboard, 'too-many-columns', 5, true ],
        ];
    }

    /**
     * @dataProvider dashboardLayoutProvider
     */
    public function testOneColumnIsValidWithOneColumn(Dashboard $dashboard, $layout, $column_count, $expected)
    {
        $this->assertEquals($dashboard->isLayoutValid($layout, $column_count), $expected);
    }
}
