<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report\Query;

use Tuleap\Tracker\Report\Query\ParametrizedFrom;

class ParametrizedFromTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testArrayUniqueOfArrayOfParametrizedFromObjectsExcludSameObjects()
    {
        $o1 = new ParametrizedFrom('some sql', [1, 2]);
        $o2 = new ParametrizedFrom('another sql', [1, 2]);
        $o3 = new ParametrizedFrom('some sql', [1, 2]);
        $o4 = new ParametrizedFrom('some sql', [1, 2, 3]);

        $this->assertEquals([$o1, $o2, $o4], array_values(array_unique([$o1, $o2, $o3, $o4])));
    }
}
