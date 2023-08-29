<?php
/**
* Copyright Enalean (c) 2015 - Present. All rights reserved.
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
* MERCHANTABILITY or FITNESS FOR A PARTIC
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
*/

class Statistics_DiskUsagePurger
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var Statistics_DiskUsageDao
     */
    private $disk_usage_dao;

    /**
     * @var array
     */
    public static $STATISTIC_TABLES = [
        'plugin_statistics_diskusage_group',
        'plugin_statistics_diskusage_site',
    ];

    public function __construct(Statistics_DiskUsageDao $disk_usage_dao, \Psr\Log\LoggerInterface $logger)
    {
        $this->disk_usage_dao = $disk_usage_dao;
        $this->logger         = $logger;
    }

    public function purge($from_date)
    {
        $this->disk_usage_dao->startTransaction();
        $this->logger->debug("Gathering stats to purge...");

        foreach (self::$STATISTIC_TABLES as $statistic_table) {
            $this->logger->debug("* Opening database table '$statistic_table'");
            $this->purgeDataOlderThanTwoYears($from_date, $statistic_table);
            $this->purgeDataBetweenTwoYearsAndThreeMonths($from_date, $statistic_table);
        }

        $this->logger->debug("Purging all data marked for removal");

        $this->disk_usage_dao->commit();
    }

    /**
     * Keep only the first found data of each month older than two years
     */
    private function purgeDataOlderThanTwoYears($from_date, $table)
    {
        $two_years_ago                      = date('Y-m-d 00:00:00', strtotime('-2 years', $from_date));
        $first_day_with_data_of_each_months = $this->findFirstDayWithDataOfEachMonthsOlderThan($table, $two_years_ago);

        if ($first_day_with_data_of_each_months) {
            $this->logger->debug("-- Parsing stats older than two years");
            $this->disk_usage_dao->purgeDataOlderThan($first_day_with_data_of_each_months, $two_years_ago, $table);
        }
    }

    private function findFirstDayWithDataOfEachMonthsOlderThan($table, $threshold_date)
    {
        $oldest_date = $this->fetchOldestDate($table);
        if (! $oldest_date || strtotime($oldest_date) >= strtotime($threshold_date)) {
            return false;
        }

        $first_day_of_each_months = $this->getFirstDayOfEachMonthsBetweenTwoDates($oldest_date, $threshold_date);
        if (! $first_day_of_each_months) {
            return false;
        }

        $first_day_with_data_of_each_months = [];
        foreach ($first_day_of_each_months as $day) {
            $first_day_with_data = $this->disk_usage_dao->findFirstDateGreaterEqualThan($day, $table);

            if ($first_day_with_data && ! in_array($first_day_with_data, $first_day_with_data_of_each_months)) {
                $first_day_with_data_of_each_months[] = $first_day_with_data;
            }
        }

        return $first_day_with_data_of_each_months;
    }

    /**
     * Keep only the first found data of each month older than two years
     */
    private function purgeDataBetweenTwoYearsAndThreeMonths($from_date, $table)
    {
        $two_years_ago                     = date('Y-m-d 00:00:00', strtotime('-2 years', $from_date));
        $three_months_ago                  = date('Y-m-d 00:00:00', strtotime('-3 months', $from_date));
        $first_day_with_data_of_each_weeks = $this->findFirstDayWithDataOfEachWeeksBetweenTwoDates($table, $two_years_ago, $three_months_ago);

        if ($first_day_with_data_of_each_weeks) {
            $this->logger->debug("-- Parsing stats between three months and two years old");
            $this->disk_usage_dao->purgeDataBetweenTwoDates($first_day_with_data_of_each_weeks, $two_years_ago, $three_months_ago, $table);
        }
    }

    private function findFirstDayWithDataOfEachWeeksBetweenTwoDates($table, $threshold_date_min, $threshold_date_max)
    {
        $oldest_date = $this->fetchOldestDate($table);
        if (! $oldest_date || strtotime($oldest_date) >= strtotime($threshold_date_max)) {
            return false;
        }

        $first_day_of_each_weeks = $this->getFirstDayOfEachWeeksBetweenTwoDates($threshold_date_min, $threshold_date_max);
        if (! $first_day_of_each_weeks) {
            return false;
        }

        $first_day_with_data_of_each_weeks = [];
        foreach ($first_day_of_each_weeks as $day) {
            $first_day_with_data = $this->disk_usage_dao->findFirstDateGreaterEqualThan($day, $table);

            if ($first_day_with_data && ! in_array($first_day_with_data, $first_day_with_data_of_each_weeks)) {
                $first_day_with_data_of_each_weeks[] = $first_day_with_data;
            }
        }

        return $first_day_with_data_of_each_weeks;
    }

    private function fetchOldestDate($table)
    {
        return $this->disk_usage_dao->searchOldestDate($table);
    }

    public function getFirstDayOfEachMonthsBetweenTwoDates($date_min, $date_max)
    {
        if (strtotime($date_min) >= strtotime($date_max)) {
            return false;
        }

        $first_day_of_each_months = [];
        $first_day_of_month       = date('Y-m-01 00:00:00', strtotime($date_min));

        do {
            $first_day_of_each_months[] = $first_day_of_month;
            $first_day_of_month         = date('Y-m-01 00:00:00', strtotime('+1 month', strtotime($first_day_of_month)));
        } while (strtotime($first_day_of_month) < strtotime($date_max));

        return $first_day_of_each_months;
    }

    public function getFirstDayOfEachWeeksBetweenTwoDates($date_min, $date_max)
    {
        if (strtotime($date_min) >= strtotime($date_max)) {
            return false;
        }

        $first_day_of_each_weeks = [];
        $first_day_of_week       = date('Y-m-d 00:00:00', strtotime('monday this week', strtotime($date_min)));

        do {
            $first_day_of_each_weeks[] = $first_day_of_week;
            $first_day_of_week         = date('Y-m-d 00:00:00', strtotime('+1 week', strtotime($first_day_of_week)));
        } while (strtotime($first_day_of_week) < strtotime($date_max));

        return $first_day_of_each_weeks;
    }
}
