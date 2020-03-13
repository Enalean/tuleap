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
 */

namespace Tuleap\Statistics\Frequencies\GraphDataBuilder;

/**
 * Global data processing
 *
 */
class Sample
{
    /**
     * $start look for timestamps superior or equals to this timestamp
     *
     * @type int $start
     */
    public $start;

    /**
     * $end look for timestamps strickly inferior to this timestamp
     *
     * @type int $end
     */
    public $end;

    /**
     * $filter filter of data display
     *
     * @type string $filter
     */
    public $filter;

    /**
     * $year the selected year
     *
     * @type int $year
     */
    public $year;

    /**
     * $month the selected month
     *
     * @type int $month
     */
    public $month;

    /**
     * $day the selected day
     *
     * @type int $day
     */
    public $day;

    /**
     * $startdate the start date in the advanced search
     *
     * @type string $startdate
     */
    public $startdate;

    /**
     * $enddate the end date in the advanced search
     *
     * @type string $enddate
     */
    public $enddate;

    /**
     * the selected period
     *
     * @type string $titlePeriod
     */
    public $titlePeriod;

    /**
     * the table in which we looking for data
     *
     * @type string $table
     */
    protected $table;

    /**
     * field selected in the table
     *
     * @type string $field
     */
    protected $field;

    /**
     * constructor
     *
     */
    public function __construct()
    {
        $this->year      = null;
        $this->month     = null;
        $this->day       = null;
        $this->startdate = null;
        $this->enddate   = null;
        $this->filter    = "month";
    }

    /**
     * indicate the search mode
     *
     * @param string $startdate the date the graph start
     * @param string $enddate   the date teh graph end
     *
     * @return bool true if advanced search, else return false
     */
    public function isAdvanced($startdate, $enddate)
    {
        if (($startdate != '') && ($enddate != '')) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * init parameter related with date in the simple search
     * ($start, $end, $filter, $titlePeriod)
     *
     * @param int $year  the selected year
     * @param int $month the selected month
     * @param int $day   the selected day
     *
     * @return void
     */
    public function initDateSimple($year, $month, $day)
    {
        if ($year != 0 && $month != 0 && $day != 0) {
            $this->start = mktime(0, 0, 0, $month, $day, $year);
        } elseif ($year != 0 && $month != 0) {
            $this->start = mktime(0, 0, 0, $month, 1, $year);
        } elseif ($year != 0) {
            $this->start = mktime(0, 0, 0, 1, 1, $year);
        } elseif ($year == 0) {
            $year        = date("Y");
            $this->start = mktime(0, 0, 0, 1, 1, $year);
        }

        if ($day != 0) {
            $this->filter = 'hour';
        } elseif ($month != 0) {
            $this->filter = 'day';
        } else {
            $this->filter = 'month';
        }

        switch ($this->filter) {
            case 'month':
                $this->end = mktime(0, 0, 0, 1, 1, $year + 1); //I search timestamps strictly inferior to the end timestamps. That's why I use $year+! instead of 12,31,$year

                $this->titlePeriod = $year;
                break;

            case 'day':
                $this->end = mktime(0, 0, 0, $month + 1, 1, $year); //$month+1 is used for the same reason

                $this->titlePeriod = $month . '/' . $year;
                break;

            case 'hour':
                $this->end = mktime(0, 0, 0, $month, $day + 1, $year); //$day+1 is used for the same reason

                $this->titlePeriod = $day . '/' . $month . '/' . $year;
                break;
        }

        $this->year  = $year;
        $this->month = $month;
        $this->day   = $day;
    }

    /**
     * init parameter related with date in the advanced search
     * ($start, $end, $filter, $titlePeriod)
     *
     * @param string $startdate the date the graph start
     * @param string $enddate   the date the graph end
     * @param string $filter    the filter of display
     * (group by month, group by day,group by hour, month, day)
     *
     * @return void
     */
    public function initDateAdvanced($startdate, $enddate, $filter)
    {
        $this->start = strtotime($startdate);
        $this->end   = strtotime($enddate);

        switch ($filter) {
            case 'month1':
                $this->filter = 'month';
                break;

            case 'day1':
                $this->filter = 'day';
                break;

            case 'hour1':
                $this->filter = 'hour';
                break;

            default:
                $this->filter = $filter;
                break;
        }

        $date1 = preg_replace('#(\d{4})-(\d{1,2})-(\d{1,2})#', '$3/$2/$1', $startdate);
        $date2 = preg_replace('#(\d{4})-(\d{1,2})-(\d{1,2})#', '$3/$2/$1', $enddate);

        $this->titlePeriod = $date1 . ' and ' . $date2;
        $this->startdate   = $startdate;
        $this->enddate     = $enddate;
    }

    /**
     * getFilter()
     *
     * @return string the filter
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * getStartDate()
     *
     * @return int the timestamp of start
     */
    public function getStartDate()
    {
        return $this->start;
    }

    /**
     * getendDate()
     *
     * @return int the timestamp of end
     */
    public function getEndDate()
    {
        return $this->end;
    }

    /**
     * getTitlePeriod()
     *
     * @return string the title period
     */
    public function getTitlePeriod()
    {
        return $this->titlePeriod;
    }

    protected function getDataSQLQuery($filter, $startDate, $endDate)
    {
        return sprintf('SELECT %s(FROM_UNIXTIME(' . $this->field . ')) as ' . $this->getFilter() . ',COUNT(*) as c' .
            ' FROM ' . $this->table .
            ' WHERE ' . $this->field . ' >= %d' .
            ' AND  ' . $this->field . ' < %d' .
            ' GROUP BY %s', db_escape_string($filter), db_escape_int($startDate), db_escape_int($endDate), db_escape_string($filter));
    }

    /**
     * fetchData()
     *
     * @return array an array of data according to the parameter choosen by user
     */
    public function fetchData()
    {
        $paramarray = [];
        $filter     = $this->getFilter();
        $startDate  = $this->getStartDate();
        $endDate    = $this->getEndDate();

        $res = db_query($this->getDataSQLQuery($filter, $startDate, $endDate));

        if ($this->getFilter() == 'month') {
            $nbr = 11;
        } elseif ($this->getFilter() == 'day') {
            $nbr = date("t", mktime(0, 0, 0, date('m', $this->start), 1, date('Y', $this->start))) - 1;
        } elseif ($this->getFilter() == 'hour') {
            $nbr = 23;
        }

        $i = 0;
        while ($i <= $nbr) {
            $paramarray[] = 0;
            $i++;
        }

        while ($paramrow = db_fetch_array($res)) {
            if ($this->getFilter() == 'hour') {
                $i = $paramrow[$this->getFilter()];
            } else {
                $i = $paramrow[$this->getFilter()] - 1;
            }

            $paramarray[$i] = $paramrow['c'];
        }

        return $paramarray;
    }

    protected function getMonthDataSQLQuery($startDate, $endDate)
    {
        return sprintf('SELECT month(FROM_UNIXTIME(' . $this->field . ')) as month,COUNT(*) as c, YEAR(FROM_UNIXTIME(' . $this->field . ')) as year' .
            ' FROM ' . $this->table .
            ' WHERE ' . $this->field . ' >= %d' .
            ' AND  ' . $this->field . ' < %d' .
            ' GROUP BY month, year' .
            ' ORDER BY year, month', db_escape_int($startDate), db_escape_int($endDate));
    }

    /**
     * fetchMonthData()
     *
     * @return mixed an array of data according to the parameter choosen by user,
     * advanced search display by month
     */
    public function fetchMonthData()
    {
        $paramarray = [];
        $startDate  = $this->getStartDate();
        $endDate    = $this->getEndDate();

        $res = db_query($this->getMonthDataSQLQuery($startDate, $endDate));

        while ($paramrow = db_fetch_array($res)) {
            $year  = $paramrow['year'];
            $month = $paramrow['month'];

            $paramarray[$year][$month] = $paramrow['c'];
        }

        return $paramarray;
    }

    protected function getDayDataSQLQuery($startDate, $endDate)
    {
        return sprintf('SELECT day(FROM_UNIXTIME(' . $this->field . ')) as day,COUNT(*) as c, MONTH(FROM_UNIXTIME(' . $this->field . ')) as month, YEAR(FROM_UNIXTIME(' . $this->field . ')) as year' .
            ' FROM ' . $this->table .
            ' WHERE ' . $this->field . ' >= %d' .
            ' AND  ' . $this->field . ' < %d' .
            ' GROUP BY day, month, year' .
            ' ORDER BY year, month, day', db_escape_int($startDate), db_escape_int($endDate));
    }

    /**
     * fetchDayData()
     *
     * @return mixed an array of data according to the parameter choosen by user, advanced search display by day
     */
    public function fetchDayData()
    {
        $paramarray = [];
        $startDate  = $this->getStartDate();
        $endDate    = $this->getEndDate();

        $res = db_query($this->getDayDataSQLQuery($startDate, $endDate));

        while ($paramrow = db_fetch_array($res)) {
            $year  = $paramrow['year'];
            $month = $paramrow['month'];
            $day   = $paramrow['day'];

            $paramarray[$year][$month][$day] = $paramrow['c'];
        }
        return $paramarray;
    }
}
