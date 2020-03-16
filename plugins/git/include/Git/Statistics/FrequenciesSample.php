<?php
/**
 * Copyright (c) Enalean, 2016 - 2018. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>
 */

namespace Tuleap\Git\Statistics;

use DateTime;
use Tuleap\Statistics\Frequencies\GraphDataBuilder\Sample;

class FrequenciesSample extends Sample
{
    public function __construct()
    {
        $this->field = 'day';
        $this->table = 'plugin_git_log_read_daily';
        parent::__construct();
    }

    protected function getDataSQLQuery($filter, $startDate, $endDate)
    {
        $sql = sprintf('SELECT %s(FROM_UNIXTIME(UNIX_TIMESTAMP(day))) as ' . $this->getFilter() . ', SUM(git_read) as c' .
            ' FROM plugin_git_log_read_daily' .
            ' WHERE day >= %d' .
            ' AND day < %d' .
            ' GROUP BY %s', db_escape_string($filter), db_escape_int($this->getDateFromTimestamp($startDate)), db_escape_int($this->getDateFromTimestamp($endDate)), db_escape_string($filter));
        return $sql;
    }

    protected function getMonthDataSQLQuery($startDate, $endDate)
    {
        $sql = sprintf('SELECT month(FROM_UNIXTIME(UNIX_TIMESTAMP(day))) as month, SUM(git_read) as c, YEAR(FROM_UNIXTIME(UNIX_TIMESTAMP(day))) as year' .
            ' FROM plugin_git_log_read_daily' .
            ' WHERE day >= %d' .
            ' AND day < %d' .
            ' GROUP BY month, year' .
            ' ORDER BY year, month', db_escape_int($this->getDateFromTimestamp($startDate)), db_escape_int($this->getDateFromTimestamp($endDate)));
        return $sql;
    }

    protected function getDayDataSQLQuery($startDate, $endDate)
    {
        $sql = sprintf('SELECT day(FROM_UNIXTIME(UNIX_TIMESTAMP(day))) as day, SUM(git_read) as c, MONTH(FROM_UNIXTIME(UNIX_TIMESTAMP(day))) as month, YEAR(FROM_UNIXTIME(UNIX_TIMESTAMP(day))) as year' .
            ' FROM plugin_git_log_read_daily' .
            ' WHERE day >= %d' .
            ' AND day < %d' .
            ' GROUP BY day, month, year' .
            ' ORDER BY year, month, day', db_escape_int($this->getDateFromTimestamp($startDate)), db_escape_int($this->getDateFromTimestamp($endDate)));
        return $sql;
    }

    private function getDateFromTimestamp($timestamp)
    {
        $date = new DateTime('@' . $timestamp);
        return $date->format('Ymd');
    }
}
