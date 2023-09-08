<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2009. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2009
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface;

class Statistics_DiskUsageDao extends DataAccessObject // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    // A day spreads From 00:00:00 ---> 23:59:59
    private function returnDateStatement(?string $date): string
    {
        if ($date === null || ! str_contains($date, ' ')) {
            return '(0=1)';
        }
        $dateList              = explode(" ", $date, 2);
        $interval['dateStart'] = trim($dateList[0] . " 00:00:00");
        $interval['dateEnd']   = trim($dateList[0] . " 23:59:59");

        $statement = ' ( date > ' . $this->da->quoteSmart($interval['dateStart']) .
            ' AND date < ' . $this->da->quoteSmart($interval['dateEnd']) . ' ) ';
        return $statement;
    }

    public function searchOldestDate($table)
    {
        $sql = "SELECT min(date) as date FROM $table";

        $dar = $this->retrieve($sql);

        if ($dar && ! $dar->isError()) {
            $row = $dar->getRow();
            if ($row !== false) {
                return $row['date'];
            }
        }

        return false;
    }

    public function findFirstDateGreaterEqualThan($date, $table)
    {
        $date = $this->da->quoteSmart($date);
        $sql  = "SELECT date
                 FROM $table
                 WHERE date >= $date
                 ORDER BY date ASC LIMIT 1";

        $dar = $this->retrieve($sql);

        if ($dar && ! $dar->isError()) {
            $row = $dar->getRow();
            if ($row !== false) {
                return $row['date'];
            }
        }

        return false;
    }

    private function findFirstDateGreaterThan(string $date, string $table, string $field = 'date'): false|string
    {
        $sql = 'SELECT date' .
               ' FROM ' . $table .
               ' WHERE ' . $field . '>"' . $date . ' 00:00:00"' .
               ' ORDER BY date ASC LIMIT 1';
        $dar = $this->retrieve($sql);
        if ($dar && ! $dar->isError()) {
            $row = $dar->getRow();
            if ($row !== false) {
                return 'date = "' . $row['date'] . '"';
            }
        }
        return false;
    }

    public function findFirstDateLowerThan($date, $table, $field = 'date')
    {
        $sql = 'SELECT date' .
               ' FROM ' . $table .
               ' WHERE ' . $field . '<"' . $date . ' 23:59:59"' .
               ' ORDER BY date DESC LIMIT 1';
        //echo $sql.'<br>';
        $dar = $this->retrieve($sql);
        if ($dar && ! $dar->isError()) {
            $row = $dar->getRow();
            if ($row !== false) {
                return 'date = "' . $row['date'] . '"';
            }
        }
        return false;
    }

    public function searchAllGroups()
    {
        $sql = 'SELECT group_id, unix_group_name FROM `groups`';
        return $this->retrieve($sql);
    }

    public function searchAllOpenProjects()
    {
        $sql = "SELECT group_id, unix_group_name
                FROM `groups`
                WHERE status != 'D'";

        return $this->retrieve($sql);
    }

    public function searchMostRecentDate()
    {
        $sql = 'SELECT max(date) as date FROM plugin_statistics_diskusage_site';
        $dar = $this->retrieve($sql);
        if ($dar && ! $dar->isError()) {
            $row = $dar->current();
            return $row['date'];
        }
        return false;
    }

    public function searchSizePerService($date, $groupId = null)
    {
        $stm = '';
        if ($groupId != null) {
            $stm =   '   AND group_id=' . $this->da->escapeInt($groupId);
        }
        $sql = ' SELECT service, sum(size) as size FROM plugin_statistics_diskusage_group' .
               ' WHERE ' . $this->returnDateStatement($date) .
               $stm . ' GROUP BY service order by service';
        return $this->retrieve($sql);
    }

    protected function _getGroupByFromDateMethod($dateMethod, &$select, &$groupBy) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        switch ($dateMethod) {
            case 'DAY':
                $select  = ', MONTH(date) as month, DAY(date) as day';
                $groupBy = ', month, day';
                break;
            case 'MONTH':
                $select  = ', MONTH(date) as month';
                $groupBy = ', month';
                break;
            case 'WEEK':
                $select  = ', WEEK(date, 3) as week';
                $groupBy = ', week';
                break;
            default:
            case 'YEAR':
                $select  = '';
                $groupBy = '';
                break;
        }
    }

    /**
     * Compute average size of a list of service
     *
     * If group Id is given, the average size will be computed for this project
     * else it is for all projects
     *
     * @param Array   $service
     * @param String  $dateMethod
     * @param Date    $startDate
     * @param Date    $endDate
     * @param int $groupId
     *
     * @return LegacyDataAccessResultInterface
     */
    public function searchSizePerServiceForPeriod($service, $dateMethod, $startDate, $endDate, $groupId = null)
    {
        $stm = '';
        if ($groupId != null) {
            $stm =   '   AND group_id=' . $this->da->escapeInt($groupId);
        }
        $this->_getGroupByFromDateMethod($dateMethod, $select, $groupBy);
        $sql = 'SELECT service, avg(size) as size, YEAR(date) as year' . $select .
               ' FROM (SELECT service, sum(size) as size, date' .
               '       FROM plugin_statistics_diskusage_group' .
               '       WHERE service IN (' . $this->da->quoteSmartImplode(',', $service) . ')' .
               $stm .
               '       AND date > "' . $startDate . ' 00:00:00"' .
               '       AND date < "' . $endDate . ' 23:59:59"' .
               '       GROUP BY service, date) as p' .
               ' GROUP BY service, year' . $groupBy .
               ' ORDER BY date ASC, size DESC';
        return $this->retrieve($sql);
    }

    /**
     * Search for services size values at a given date
     */
    private function searchServiceSize(string|false $date_statement, ?int $project_id): \IProvideDataAccessResult|false
    {
        $where_clause = '';

        $project_statement = $project_id ? 'group_id=' . $this->da->escapeInt($project_id) : false;

        $statements = array_filter([$date_statement, $project_statement]);
        if ($statements) {
            $where_clause .= 'WHERE ' . implode(' AND ', $statements);
        }

        $sql = 'SELECT service, sum(size) as size' .
            ' FROM plugin_statistics_diskusage_group dug ' . $where_clause . ' GROUP BY service';

        return $this->retrieve($sql);
    }

    /**
     * Search for services size at the first date greater than the given one
     *
     * @param String  $date    Date (YYYY-MM-DD)
     * @param int $groupId To restrict to a groupId if needed
     */
    public function searchServiceSizeStart($date, $groupId = null): \IProvideDataAccessResult|false
    {
        $dateStmt = $this->findFirstDateGreaterThan($date, 'plugin_statistics_diskusage_group');
        return $this->searchServiceSize($dateStmt, $groupId);
    }

    /**
     * Search for services size at the first date lower than the given one
     *
     * @param String  $date    Date (YYYY-MM-DD)
     * @param int $groupId To restrict to a groupId if needed
     */
    public function searchServiceSizeEnd($date, $groupId = null): \IProvideDataAccessResult|false
    {
        $dateStmt = $this->findFirstDateLowerThan($date, 'plugin_statistics_diskusage_group');
        return $this->searchServiceSize($dateStmt, $groupId);
    }

    public function searchSiteSize($date)
    {
        $sql = 'SELECT service, size FROM plugin_statistics_diskusage_site WHERE ' . $this->returnDateStatement($date);
        return $this->retrieve($sql);
    }

    public function searchServicesSizesPerProject($groupId, $date)
    {
        $sql = 'SELECT service, size' .
            ' FROM plugin_statistics_diskusage_group ' .
            ' WHERE ' . $this->returnDateStatement($date) .
            ' AND group_id = ' . $this->da->escapeInt($groupId);
        return $this->retrieve($sql);
    }

    public function returnTotalSizeProjectNearDate($group_id, $date)
    {
        $sql = 'SELECT sum(size) as size' .
            ' FROM plugin_statistics_diskusage_group ' .
            ' WHERE ' . $this->findFirstDateLowerThan($date, 'plugin_statistics_diskusage_group') .
            ' AND group_id = ' . $this->da->escapeInt($group_id);
        return $this->retrieve($sql);
    }

    /**
     * Compute average size of user_id
     */
    public function searchSizePerProjectForPeriod($groupId, $dateMethod, $startDate, $endDate): LegacyDataAccessResultInterface|false
    {
        $this->_getGroupByFromDateMethod($dateMethod, $select, $groupBy);
        $sql = 'SELECT  avg(size) as size, YEAR(date) as year' . $select .
               ' FROM (SELECT  sum(size) as size, date' .
               '       FROM plugin_statistics_diskusage_group' .
               '       WHERE group_id = ' . $this->da->escapeInt($groupId) .
               '       AND date > "' . $startDate . ' 00:00:00"' .
               '       AND date < "' . $endDate . ' 23:59:59"' .
               '       GROUP BY date) as p' .
               ' GROUP BY year' . $groupBy .
               ' ORDER BY date ASC, size DESC';
        return $this->retrieve($sql);
    }

    public function updateGroup(Project $project, DateTimeImmutable $time, string $service, string $size): void
    {
        $sql = 'UPDATE plugin_statistics_diskusage_group
                SET size = ' . $this->da->quoteSmart($size) . '
                WHERE group_id = ' . $this->da->escapeInt($project->getID()) . '
                    AND service = ' . $this->da->quoteSmart($service) . '
                    AND date = FROM_UNIXTIME(' . $this->da->escapeInt($time->getTimestamp()) . ')';
        $this->update($sql);
    }

    public function addGroup($groupId, $service, $size, $time)
    {
        $sql = 'INSERT INTO plugin_statistics_diskusage_group' .
            ' (group_id, service, date, size)' .
            ' VALUES (' . $this->da->quoteSmart($groupId) . ',' . $this->da->quoteSmart($service) . ',FROM_UNIXTIME(' . $this->da->escapeInt($time) . '),' . $this->da->quoteSmart($size) . ')';
        //echo $sql.PHP_EOL;
        return $this->update($sql);
    }

    public function addSite($service, $size, $time)
    {
        $sql = 'INSERT INTO plugin_statistics_diskusage_site' .
            ' (service, date, size)' .
            ' VALUES (' . $this->da->quoteSmart($service) . ',FROM_UNIXTIME(' . $this->da->escapeInt($time) . '),' . $this->da->quoteSmart($size) . ')';
        //echo $sql.PHP_EOL;
        return $this->update($sql);
    }

    /**
     * Computes size of project for a given service
     * @param Date    $startDate
     * @param Date    $endDate
     * @param String  $service
     * @param String  $order
     * @param int $limit
     */
    public function getProjectContributionForService($startDate, $endDate, $service, $order, $offset = 0, $limit = 10)
    {
        $sql = 'SELECT SQL_CALC_FOUND_ROWS group_id, group_name, end_size, start_size, (end_size - start_size) as evolution, (end_size-start_size)/start_size as evolution_rate' .
               ' FROM (SELECT group_id, service, sum(size) as start_size
                       FROM plugin_statistics_diskusage_group
                       WHERE ' . ($this->findFirstDateGreaterThan($startDate, 'plugin_statistics_diskusage_group') ?: 1) . '
                       AND  service IN (' . $this->da->quoteSmartImplode(',', $service) . ') group by group_id) as start' .
               ' LEFT JOIN (SELECT group_id, service, sum(size) as end_size
                       FROM plugin_statistics_diskusage_group
                       WHERE ' . ($this->findFirstDateLowerThan($endDate, 'plugin_statistics_diskusage_group') ?: 1) . '
                       AND service IN (' . $this->da->quoteSmartImplode(',', $service) . ') group by group_id) as end' .
                ' USING (group_id)' .
                ' LEFT JOIN `groups` USING (group_id)' .
                ' Group by group_id' .
                 ' ORDER BY ' . $order . ' DESC' .
                ' LIMIT ' . $this->da->escapeInt($offset) . ',' . $this->da->escapeInt($limit);

        return $this->retrieve($sql);
    }

    public function purgeDataOlderThan($dates_to_keep, $threshold_date, $table)
    {
        $dates_to_keep  = $this->da->quoteSmartImplode(',', $dates_to_keep);
        $threshold_date = $this->da->quoteSmart($threshold_date);

        $sql = "DELETE FROM $table
                WHERE date NOT IN ($dates_to_keep)
                AND date < $threshold_date";

        return $this->update($sql);
    }

    public function purgeDataBetweenTwoDates($dates_to_keep, $threshold_date_min, $threshold_date_max, $table)
    {
        $dates_to_keep      = $this->da->quoteSmartImplode(',', $dates_to_keep);
        $threshold_date_min = $this->da->quoteSmart($threshold_date_min);
        $threshold_date_max = $this->da->quoteSmart($threshold_date_max);

        $sql = "DELETE FROM $table
                WHERE date NOT IN ($dates_to_keep)
                AND date > $threshold_date_min
                AND date < $threshold_date_max";

        return $this->update($sql);
    }

    /**
     * @return array{size:string}|false
     */
    public function getLastSizeForService($project_id, $service_name)
    {
        $project_id   = $this->da->escapeInt($project_id);
        $service_name = $this->da->quoteSmart($service_name);

        $sql = "SELECT size
                FROM plugin_statistics_diskusage_group
                WHERE group_id = $project_id
                  AND service = $service_name
                ORDER BY date
                DESC LIMIT 1";

        return $this->retrieveFirstRow($sql);
    }
}
