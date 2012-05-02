<?php
/**
 * Copyright (c) STMicroelectronics, 2009. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2009
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class Statistics_DiskUsageDao extends DataAccessObject {

    /**
     * Constructor
     *
     * @param DataAccess $da Data access details
     * 
     * @return Statistics_DiskUsageDao
     */
    public function __construct(DataAccess $da) {
        parent::__construct($da);
    }

    // A day spreads From 00:00:00 ---> 23:59:59
    public function returnDateStatement($date) {
        $dateList    = split(" ", $date, 2);
        $interval['dateStart'] = $dateList[0]." 00:00:00";
        $interval['dateEnd'] = $dateList[0]." 23:59:59";

        $statement = ' ( date > '.$this->da->quoteSmart($interval['dateStart']).
            ' AND date < '.$this->da->quoteSmart($interval['dateEnd']).' ) ';
        return $statement;
    }

    public function findFirstDateGreaterThan($date, $table, $field='date') {
        $sql = 'SELECT date'.
               ' FROM '.$table.
               ' WHERE '.$field.'>"'.$date.' 00:00:00"'.
               ' ORDER BY date ASC LIMIT 1';
        //echo $sql.'<br>';
        $dar = $this->retrieve($sql);
        if ($dar && !$dar->isError()) {
            $row = $dar->getRow();
            return 'date = "'.$row['date'].'"';
        }
        return false;
    }

    public function findFirstDateLowerThan($date, $table, $field='date') {
        $sql = 'SELECT date'.
               ' FROM '.$table.
               ' WHERE '.$field.'<"'.$date.' 23:59:59"'.
               ' ORDER BY date DESC LIMIT 1';
        //echo $sql.'<br>';
        $dar = $this->retrieve($sql);
        if ($dar && !$dar->isError()) {
            $row = $dar->getRow();
            return 'date = "'.$row['date'].'"';
        }
        return false;
    }

    public function searchAllGroups() {
        $sql = 'SELECT group_id, unix_group_name FROM groups';
        return $this->retrieve($sql);
    }

    public function searchAllLists() {
        $sql = 'SELECT group_list_id, group_id, list_name FROM mail_group_list ORDER BY group_id';
        return $this->retrieve($sql);
    }

    public function searchAllUsers() {
        $sql = 'SELECT user_id, user_name FROM user';
        return $this->retrieve($sql);
    }

    public function searchMostRecentDate() {
        $sql = 'SELECT max(date) as date FROM plugin_statistics_diskusage_site';
        $dar = $this->retrieve($sql);
        if ($dar && !$dar->isError()) {
            $row = $dar->current();
            return $row['date'];
        }
        return false;
    }

    public function searchSizePerService($date, $groupId = NULL) {
        $stm ='';
        if ($groupId != NULL) {
            $stm =   '   AND group_id='.$this->da->escapeInt($groupId);
        }
        $sql = ' SELECT service, sum(size) as size FROM plugin_statistics_diskusage_group'. 
               ' WHERE '.$this->returnDateStatement($date).
               $stm.' GROUP BY service order by service';
        return $this->retrieve($sql);
    }

    protected function _getGroupByFromDateMethod($dateMethod, &$select, &$groupBy) {
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
                $select  = ', WEEK(date) as week';
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
     * @param Integer $groupId
     * @return DataAccessResult
     */
    public function searchSizePerServiceForPeriod($service, $dateMethod='DAY', $startDate, $endDate, $groupId = NULL) {
        $stm ='';
        if ($groupId != NULL) {
            $stm =   '   AND group_id='.$this->da->escapeInt($groupId);
        }
        $this->_getGroupByFromDateMethod($dateMethod, $select, $groupBy);
        $sql = 'SELECT service, avg(size) as size, YEAR(date) as year'.$select.
               ' FROM (SELECT service, sum(size) as size, date'.
               '       FROM plugin_statistics_diskusage_group'.
               '       WHERE service IN ('.$this->da->quoteSmartImplode(',', $service).')'.
               $stm.
               '       AND date > "'.$startDate.' 00:00:00"'.
               '       AND date < "'.$endDate.' 23:59:59"'.
               '       GROUP BY service, date) as p'.
               ' GROUP BY service, year'.$groupBy.
               ' ORDER BY date ASC, size DESC';
        return $this->retrieve($sql);
    }

    /**
     * Search for services size values at a given date
     *
     * @param String  $dateStmt Date Statement
     * @param Integer $groupId  To restrict to a groupId if needed
     *
     * @return DataAccessResult
     */
    public function searchServiceSize($dateStmt, $groupId = null) {
        $stmClause = '';
        if ($groupId !== null) {
            $stmClause =   ' AND group_id='.$this->da->escapeInt($groupId);
        }
        $sql = 'SELECT service, sum(size) as size'.
               ' FROM plugin_statistics_diskusage_group dug  WHERE '.$dateStmt.
               $stmClause.' GROUP BY service';
        return $this->retrieve($sql);
    }

    /**
     * Search for services size at the first date greater than the given one
     *
     * @param String  $date    Date (YYYY-MM-DD)
     * @param Integer $groupId To restrict to a groupId if needed
     *
     * @return DataAccessResult
     */
    public function searchServiceSizeStart($date, $groupId = null) {
        $dateStmt = $this->findFirstDateGreaterThan($date, 'plugin_statistics_diskusage_group');
        return $this->searchServiceSize($dateStmt, $groupId);
    }

    /**
     * Search for services size at the first date lower than the given one
     *
     * @param String  $date    Date (YYYY-MM-DD)
     * @param Integer $groupId To restrict to a groupId if needed
     *
     * @return DataAccessResult
     */
    public function searchServiceSizeEnd($date, $groupId = null) {
        $dateStmt = $this->findFirstDateLowerThan($date, 'plugin_statistics_diskusage_group');
        return $this->searchServiceSize($dateStmt, $groupId);
    }

    public function searchTotalUserSize($date) {
        $sql = 'SELECT sum(size) as size FROM plugin_statistics_diskusage_user WHERE '.$this->returnDateStatement($date);
        return $this->retrieve($sql);
    }

    public function searchSiteSize($date) {
        $sql = 'SELECT service, size FROM plugin_statistics_diskusage_site WHERE '.$this->returnDateStatement($date);
        return $this->retrieve($sql);
    }

    public function searchTopProjects($startDate, $endDate, $order, $limit=10) {
        $sql = 'SELECT group_id, group_name, end_size, start_size, (end_size - start_size) as evolution, (end_size-start_size)/start_size as evolution_rate'.
               ' FROM (SELECT group_id, sum(size) as start_size 
                       FROM plugin_statistics_diskusage_group
                       WHERE '.$this->findFirstDateGreaterThan($startDate, 'plugin_statistics_diskusage_group').' 
                       GROUP BY group_id) as start'. 
               ' LEFT JOIN (SELECT group_id, sum(size) as end_size 
                       FROM plugin_statistics_diskusage_group 
                       WHERE '.$this->findFirstDateLowerThan($endDate, 'plugin_statistics_diskusage_group').' 
                       GROUP BY group_id) as end'.
                ' USING (group_id)'.
                ' LEFT JOIN groups USING (group_id)'.
                ' ORDER BY '.$order.' DESC'.
                ' LIMIT '.$this->da->escapeInt($limit);
      
        return $this->retrieve($sql);
    }

    public function searchTopUsers($endDate, $order, $limit=10) {
        $sql = 'SELECT user_id, user_name, end_size '.
               ' FROM ( SELECT user_id, sum(size) as end_size 
                       FROM plugin_statistics_diskusage_user 
                       WHERE '.$this->findFirstDateLowerThan($endDate, 'plugin_statistics_diskusage_user').' 
                       GROUP BY user_id) as end'.
               ' LEFT JOIN user USING (user_id)'.
               ' ORDER BY '.$order.' DESC'.
               ' LIMIT '.$this->da->escapeInt($limit);
        return $this->retrieve($sql);
    }
    
    public function returnUserDetails($userId, $date){
        $sql = 'SELECT user_id, user_name, service, sum(size) as size'.
            ' FROM plugin_statistics_diskusage_user '.
            ' LEFT JOIN user USING (user_id)'.   
            ' WHERE '.$this->returnDateStatement($date).
            ' AND user_id = '.$this->da->escapeInt($userId).
            ' GROUP BY user_id';
        return $this->retrieve($sql);
   }
   
     /**
     * Compute average size of user_id
     * 
     * @param int $user_id
     * 
     * @return DataAccessResult
     */
    public function searchSizePerUserForPeriod($userId, $dateMethod='DAY', $startDate, $endDate) {
        $this->_getGroupByFromDateMethod($dateMethod, $select, $groupBy);
        $sql = 'SELECT  avg(size) as size, YEAR(date) as year'.$select.
               ' FROM (SELECT service, sum(size) as size, date'.
               '       FROM plugin_statistics_diskusage_user'.
               '       WHERE user_id = '.$this->da->escapeInt($userId).
               '       AND date > "'.$startDate.' 00:00:00"'.
               '       AND date < "'.$endDate.' 23:59:59"'.
               '       GROUP BY date) as p'.
               ' GROUP BY year'.$groupBy.
               ' ORDER BY date ASC, size DESC';
        return $this->retrieve($sql);
    }


    public function searchProject($groupId, $date) {
        $sql = 'SELECT service, size'.
            ' FROM plugin_statistics_diskusage_group '.
            ' WHERE '.$this->returnDateStatement($date).
            ' AND group_id = '.$this->da->escapeInt($groupId).
            ' ORDER BY size DESC';
        //echo $sql.PHP_EOL;
        return $this->retrieve($sql);
    }
    
   public function returnTotalSizeProject($groupId, $date) {
        $sql = 'SELECT sum(size) as size'.
            ' FROM plugin_statistics_diskusage_group '.
            ' WHERE '.$this->returnDateStatement($date).
            ' AND group_id = '.$this->da->escapeInt($groupId);
        return $this->retrieve($sql);
    }
    
     /**
     * Compute evolution size of  project for a given period
     * 
     * @param date $endDate , date $startDate
     * 
     * @return DataAccessResult
     */
    public function returnProjectEvolutionForPeriod($groupId, $startDate ,$endDate){
        $sql = 'SELECT  group_name, end_size, start_size, (end_size - start_size) as evolution, (end_size-start_size)/start_size as evolution_rate'.
               ' FROM (SELECT group_id,  sum(size) as start_size 
                       FROM plugin_statistics_diskusage_group
                       WHERE '.$this->findFirstDateGreaterThan($startDate, 'plugin_statistics_diskusage_group').'
                       AND group_id = '.$this->da->escapeInt($groupId).'
                       GROUP BY group_id ) as start'. 
               ' LEFT JOIN (SELECT group_id,  sum(size) as end_size 
                       FROM plugin_statistics_diskusage_group 
                       WHERE '.$this->findFirstDateLowerThan($endDate, 'plugin_statistics_diskusage_group').'
                       AND group_id = '.$this->da->escapeInt($groupId).'
                       GROUP BY group_id ) as end'.
                ' USING (group_id)'.
                ' LEFT JOIN groups using(group_id)';
        return $this->retrieve($sql);
    }
    
    /**
     * Compute average size of user_id
     * 
     * @param int $user_id
     * 
     * @return DataAccessResult
     */
    public function searchSizePerProjectForPeriod($groupId, $dateMethod='DAY', $startDate, $endDate) {
        $this->_getGroupByFromDateMethod($dateMethod, $select, $groupBy);
        $sql = 'SELECT  avg(size) as size, YEAR(date) as year'.$select.
               ' FROM (SELECT  sum(size) as size, date'.
               '       FROM plugin_statistics_diskusage_group'.
               '       WHERE group_id = '.$this->da->escapeInt($groupId).
               '       AND date > "'.$startDate.' 00:00:00"'.
               '       AND date < "'.$endDate.' 23:59:59"'.
               '       GROUP BY date) as p'.
               ' GROUP BY year'.$groupBy.
               ' ORDER BY date ASC, size DESC';
        return $this->retrieve($sql);
    }
    
    
    /**
     * Compute evolution size of  user for a given period
     * 
     * @param date $endDate , date $startDate
     * 
     * @return DataAccessResult
     */
    public function returnUserEvolutionForPeriod($userId, $startDate ,$endDate){
        $sql = 'SELECT  end_size, start_size, (end_size - start_size) as evolution, (end_size-start_size)/start_size as evolution_rate'.
               ' FROM (SELECT user_id,  sum(size) as start_size 
                       FROM plugin_statistics_diskusage_user
                       WHERE '.$this->findFirstDateGreaterThan($startDate, 'plugin_statistics_diskusage_user').'
                       AND user_id = '.$this->da->escapeInt($userId).'
                       GROUP BY user_id ) as start'. 
               ' LEFT JOIN (SELECT user_id,  sum(size) as end_size 
                       FROM plugin_statistics_diskusage_user 
                       WHERE '.$this->findFirstDateLowerThan($endDate, 'plugin_statistics_diskusage_user').'
                       AND user_id = '.$this->da->escapeInt($userId).'
                       GROUP BY user_id ) as end'.
                ' USING (user_id)';
                
        return $this->retrieve($sql);
    }

    public function addGroup($groupId, $service, $size, $time) {
        $sql = 'INSERT INTO plugin_statistics_diskusage_group'.
            ' (group_id, service, date, size)'.
            ' VALUES ('.$this->da->quoteSmart($groupId).','.$this->da->quoteSmart($service).',FROM_UNIXTIME('.$this->da->escapeInt($time).'),'.$this->da->quoteSmart($size).')';
        //echo $sql.PHP_EOL;
        return $this->update($sql);
    }

    public function addUser($userId, $service, $size, $time) {
        $sql = 'INSERT INTO plugin_statistics_diskusage_user'.
            ' (user_id, service, date, size)'.
            ' VALUES ('.$this->da->quoteSmart($userId).','.$this->da->quoteSmart($service).',FROM_UNIXTIME('.$this->da->escapeInt($time).'),'.$this->da->quoteSmart($size).')';
        //echo $sql.PHP_EOL;
        return $this->update($sql);
    }

    public function addSite($service, $size, $time) {
        $sql = 'INSERT INTO plugin_statistics_diskusage_site'.
            ' (service, date, size)'.
            ' VALUES ('.$this->da->quoteSmart($service).',FROM_UNIXTIME('.$this->da->escapeInt($time).'),'.$this->da->quoteSmart($size).')';
        //echo $sql.PHP_EOL;
        return $this->update($sql);
    }
    /**
     * Computes size of project for a given service
     * @param Date    $startDate
     * @param Date    $endDate
     * @param String  $service
     * @param String  $order
     * @param Integer $limit 
     */
    public function getProjectContributionForService($startDate, $endDate, $service, $order, $offset=0, $limit=10) {
        $sql = 'SELECT SQL_CALC_FOUND_ROWS group_id, group_name, end_size, start_size, (end_size - start_size) as evolution, (end_size-start_size)/start_size as evolution_rate'.
               ' FROM (SELECT group_id, service, sum(size) as start_size 
                       FROM plugin_statistics_diskusage_group
                       WHERE '.$this->findFirstDateGreaterThan($startDate, 'plugin_statistics_diskusage_group').' 
                       AND  service IN ('.$this->da->quoteSmartImplode(',', $service).') group by group_id) as start'. 
               ' LEFT JOIN (SELECT group_id, service, sum(size) as end_size 
                       FROM plugin_statistics_diskusage_group 
                       WHERE '.$this->findFirstDateLowerThan($endDate, 'plugin_statistics_diskusage_group').' 
                       AND service IN ('.$this->da->quoteSmartImplode(',', $service).') group by group_id) as end'.
                ' USING (group_id)'.
                ' LEFT JOIN groups USING (group_id)'.
                ' Group by group_id'.
                 ' ORDER BY '.$order.' DESC'.
                ' LIMIT '.$this->da->escapeInt($offset).','.$this->da->escapeInt($limit);
      
        
        return $this->retrieve($sql);
    
    }
}

?>
