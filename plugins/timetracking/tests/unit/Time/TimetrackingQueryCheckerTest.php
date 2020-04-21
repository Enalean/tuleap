<?php
/**
 * Copyright Enalean (c) 2018 - Present. All rights reserved.
 *
 *  Tuleap and Enalean names and logos are registrated trademarks owned by
 *  Enalean SAS. All other trademarks or names are properties of their respective
 *  owners.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Tuleap\Timetracking\Time;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Timetracking\REST\v1\TimetrackingQueryChecker;
use Luracast\Restler\RestException;

class TimetrackingQueryCheckerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * GetTimesByTrackersQueryChecker
     */
    private $checker;

    public function setUp(): void
    {
        $this->checker = new TimetrackingQueryChecker();
    }

    public function testPassWhenQueryIsValid()
    {
        $json_query = ["trackers_id" => [1, 2], "start_date" => "2018-11-20T00:00:00+01", "end_date" => "2018-12-30T00:00:00+01"];
        $this->assertNull($this->checker->checkQuery($json_query));
    }

    public function testPassWhenNoTrackers()
    {
        $json_query = ["start_date" => "2018-11-20T00:00:00+01", "end_date" => "2018-12-30T00:00:00+01"];
        $this->assertNull($this->checker->checkQuery($json_query));
    }

    public function testPassWhenStartDateEqualsEndDate()
    {
        $json_query = ["trackers_id" => [1, 2], "start_date" => "2018-11-20T00:00:00+01", "end_date" => "2018-11-20T00:00:00+01"];

        $this->assertNull($this->checker->checkQuery($json_query));
    }

    public function testItRaiseExeptionWhenBadDates()
    {
        $json_query = ["trackers_id" => [1, 2], "start_date" => "2018-11-20T00:00:00+01", "end_date" => "Banane"];

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);
        $this->expectExceptionMessage('Please provide valid ISO-8601 dates');

        $this->checker->checkQuery($json_query);
    }

    public function testItRaiseExeptionWhenStartDateGreaterThanEndDate()
    {
        $json_query = ["trackers_id" => [1, 2], "start_date" => "2019-11-20T00:00:00+01", "end_date" => "2018-11-20T00:00:00+01"];

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);
        $this->expectExceptionMessage('end_date must be greater than start_date');

        $this->checker->checkQuery($json_query);
    }

    public function testItRaiseExeptionWhenNoDates()
    {
        $json_query = ["trackers_id" => [1, 2]];

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);
        $this->expectExceptionMessage('Please provide a start date and an end date');

        $this->checker->checkQuery($json_query, true);
    }

    public function testItRaiseExeptionWhenBadIds()
    {
        $json_query = ["trackers_id" => [1, "bad id"], "start_date" => "2018-11-20T00:00:00+01", "end_date" => "2018-12-30T00:00:00+01"];

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);
        $this->expectExceptionMessage('Please provide valid trackers\' ids');

        $this->checker->checkQuery($json_query, true);
    }
}
