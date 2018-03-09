<?php
/**
 * Copyright Enalean (c) 2018. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\Timetracking\REST;

use RestBase;
use TimetrackingDataBuilder;

require_once dirname(__FILE__) . '/../bootstrap.php';

class TimetrackingTest extends RestBase
{
    public function testGetTimesForUser()
    {
        $response = $this->getResponse(
            $this->client->get('timetracking'),
            TimetrackingDataBuilder::USER_TESTER_NAME
        );

        $times = $response->json();

        $this->assertTrue(count($times) === 1);
        $this->assertEquals($times[0]['id'], 1);
        $this->assertEquals($times[0]['minutes'], 600);
        $this->assertEquals($times[0]['date'], '2018-03-09');
    }
}
