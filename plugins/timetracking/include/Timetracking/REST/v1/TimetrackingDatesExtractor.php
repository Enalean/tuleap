<?php
/**
 * Copyright Enalean (c) 2019 - Present. All rights reserved.
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

namespace Tuleap\Timetracking\REST\v1;

use Tuleap\REST\JsonDecoder;

class TimetrackingDatesExtractor
{
    /**
     * @var JsonDecoder
     */
    private $json_decoder;

    public function __construct(JsonDecoder $json_decoder)
    {
        $this->json_decoder = $json_decoder;
    }

    public function getDatesFromRoute(?string $query): DateTrackingTimesPeriod
    {
        $json_query = $this->json_decoder->decodeAsAnArray('query', $query);

        if (empty($query) || (! isset($json_query['start_date']) && ! isset($json_query['end_date']))) {
            $date       = new \DateTimeImmutable();
            $start_date = $date->modify('-1 month');
            return new DateTrackingTimesPeriod($start_date, $date);
        }

        $checker = new TimetrackingQueryChecker();
        $checker->checkQuery($json_query);

        $start_date = \DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, $json_query['start_date']);
        $end_date   = \DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, $json_query['end_date']);

        if ($start_date === false || $end_date === false) {
            throw new \LogicException('start_date and end_date are supposed to be checked by TimetrackingQueryChecker::checkQuery');
        }

        return new DateTrackingTimesPeriod($start_date, $end_date);
    }
}
