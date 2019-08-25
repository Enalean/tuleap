<?php
/**
 * Copyright Enalean (c) 2019. All rights reserved.
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

declare(strict_types=1);

namespace Tuleap\Timetracking\REST\v1;

use DateTime;
use Luracast\Restler\RestException;

class TimetrackingQueryChecker
{
    /**
     * @param array $json_query
     * @throws RestException
     */
    public function checkQuery(array $json_query)
    {
        if (! isset($json_query["start_date"]) || ! isset($json_query["end_date"])) {
            throw new RestException(400, "Please provide a start date and an end date");
        }

        if (isset($json_query["trackers_id"])) {
            foreach ($json_query["trackers_id"] as $ids) {
                if (! is_int($ids)) {
                    throw new RestException(400, "Please provide valid trackers' ids");
                }
            }
        }

        $this->checkTimePeriodIsValid($json_query["start_date"], $json_query["end_date"]);
    }

    /**
     * @param $start_date
     * @param $end_date
     * @throws RestException
     */
    public function checkTimePeriodIsValid(string $start_date, string $end_date)
    {
        $period_start = DateTime::createFromFormat(DateTime::ATOM, $start_date);
        $period_end   = DateTime::createFromFormat(DateTime::ATOM, $end_date);

        if (! $period_start || ! $period_end) {
            throw new RestException(400, "Please provide valid ISO-8601 dates");
        }

        if ($period_start > $period_end) {
            throw new RestException(400, "end_date must be greater than start_date");
        }
    }
}
