<?php
/**
 * Copyright (c) STMicroelectronics, 2009. All Rights Reserved.
 * Copyright (c) Enalean, 2016. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2009
 *
 * This file is a part of Codendi.
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

require_once 'Statistics_DiskUsageDao.class.php';
require_once('common/dao/include/DataAccessObject.class.php');

class Statistics_DiskUsageManager {
    private $_dao = null;

    private $_services = array();

    const SVN = 'svn';
    const CVS = 'cvs';
    const FRS = 'frs';
    const FTP = 'ftp';
    const GRP_HOME = 'grp_home';
    const USR_HOME = 'usr_home';
    const WIKI = 'wiki';
    const PLUGIN_WEBDAV = 'plugin_webdav';
    const MAILMAN = 'mailman';
    const MYSQL = 'mysql';
    const CODENDI_LOGS = 'codendi_log';
    const BACKUP = 'backup';
    const BACKUP_OLD = 'backup_old';
    const PATH = 'path_';

    public function __construct() {
    }

    /**
     * The SVN/Webdav statistics is dedicated just to the site admin
     * We do not display it in case of project admin
     *
     * @param Boolean $siteAdminView
     *
     * @return Array
     */
    public function getProjectServices($siteAdminView = true) {
        if (count($this->_services) == 0) {
            $this->_services = array(self::SVN           => 'Subversion',
                                     self::CVS           => 'CVS',
                                     self::FRS           => 'File releases',
                                     self::FTP           => 'Public FTP',
                                     self::GRP_HOME      => 'Home page',
                                     self::WIKI          => 'Wiki',
                                     self::MAILMAN       => 'Mailman');
            if ($siteAdminView) {
                $this->_services[self::PLUGIN_WEBDAV] = 'SVN/Webdav';
            }
            $em     = EventManager::instance();
            $params = array('services' => &$this->_services);
            $em->processEvent('plugin_statistics_disk_usage_service_label', $params);
        }
        return $this->_services;
    }

    /**
     * Return a human readable string for service
     *
     * @param String $service
     *
     * @return String
     */
    public function getServiceColor($service) {
        switch($service) {
            case self::SVN:
                return 'darkolivegreen';
            case self::CVS:
                return 'darkgreen';
            case self::FRS:
                return 'pink1';
            case self::FTP:
                return 'purple4';
            case self::GRP_HOME:
                return 'mistyrose';
            case self::WIKI:
                return 'darkturquoise';
            case self::MAILMAN:
                return 'darkkhaki';
            case self::PLUGIN_WEBDAV:
                return 'aquamarine';
            case self::USR_HOME:
                return 'darkturquoise';
            case self::MYSQL:
                return 'sandybrown';
            case self::CODENDI_LOGS:
                return 'forestgreen';
            case self::BACKUP:
                return 'saddlebrown';
            case self::BACKUP_OLD:
                return 'cornflowerblue';
            default:
                // If plugins don't want to color themselves they are white
                $color = 'white';
                $params = array('service' => $service, 'color' => &$color);
                $em = EventManager::instance();
                $em->processEvent('plugin_statistics_color', $params);
                return $color;
        }
    }

    public function getGeneralData($date) {
        $res = array();
        $dao  = $this->_getDao();
        if ($date) {
            $res['date'] = $date;

            $dar = $dao->searchTotalUserSize($date);
            if ($dar && !$dar->isError()) {
                $row = $dar->getRow();
                $res['service'][self::USR_HOME] = $row['size'];
            }

            $dar = $dao->searchSiteSize($date);
            if ($dar && !$dar->isError()) {
                foreach ($dar as $row) {
                    if (strpos($row['service'], self::PATH) !== false) {
                        $path = substr($row['service'], strlen(self::PATH.'_')-1);
                        $res['path'][$path] = $row['size'];
                    } else {
                        $res['service'][$row['service']] = $row['size'];
                    }
                }
            }
        }
        return $res;
    }
    public function getLatestData() {
        $dao  = $this->_getDao();
        $date = $dao->searchMostRecentDate();
        return  $this->getGeneralData($date);
    }

    function getKeyFromGroupBy($row, $groupBy) {
        switch ($groupBy) {
            case 'DAY':
                return $row['year'].'-'.$row['month'].'-'.$row['day'];
                break;
            case 'MONTH':
                return $row['year'].'-'.$row['month'];
                break;
            case 'WEEK':
                return $row['year'].'-'.$row['week'];
                break;
            default:
            case 'YEAR':
                return $row['year'];
                break;
        }
    }

    function getRangeDates($dar, $groupBy) {
        $dates = array();
        foreach ($dar as $row) {
            $dates[$this->getKeyFromGroupBy($row, $groupBy)] = 0;
        }
        return $dates;
    }

    public function getWeeklyEvolutionServiceData($services, $groupBy, $startDate, $endDate) {
        $res     = array();
        $groupBy = strtoupper($groupBy);
        $dao     = $this->_getDao();
        $dar     = $dao->searchSizePerServiceForPeriod($services, $groupBy, $startDate, $endDate);
        if ($dar && !$dar->isError()) {
            $dates = $this->getRangeDates($dar, $groupBy);
            foreach ($dar as $row) {
                if (!isset($res[$row['service']])) {
                    $res[$row['service']] = $dates;
                }
                $res[$row['service']][$this->getKeyFromGroupBy($row, $groupBy)] = $row['size'];
            }
            return $res;
         }
         return false;

    }

    public function getUsagePerProject($startDate, $endDate, $service, $order, $offset, $limit) {
        $dao   = $this->_getDao();
        $dar   = $dao->getProjectContributionForService($startDate, $endDate, $service, $order, $offset, $limit);
        $nbPrj = $dao->foundRows();
        return array($dar, $nbPrj);
    }

    public function returnServiceWeeklyEvolution(){
        $dao = $this->_getDao();
        //the Collect date
        $endDate = $dao->searchMostRecentDate();
        if ($endDate){
            $rowEnd = $dao->searchSizePerService($endDate);
            if ($rowEnd && !$rowEnd->isError()) {
                foreach ($rowEnd as $end) {
                    $res[$end['service']] = $end['size'];
                }
            }
            $timestamp = strtotime($endDate);
            //a week ago
            $startDate = date('Y-m-d h:i:s', strtotime('-1 week',$timestamp));
            $rowStart = $dao->searchSizePerService($startDate);
            if ($rowStart && !$rowStart->isError()) {
                foreach ($rowStart as $start) {
                    $res[$start['service']] = $res[$start['service']] - $start['size'];
                }
            }
            return $res;
        }
        return false;
    }

    /**
     * Retrieve data for the two given dates and compute some statistics
     *
     * @param String  $startDate
     * @param String  $endDate
     * @param Integer $groupId
     *
     * @return Array
     */
    public function returnServiceEvolutionForPeriod($startDate, $endDate, $groupId=null) {
        // Build final array based on services (ensure always same order)
        $values = array();
        foreach ($this->getProjectServices() as $k => $v) {
            $values[$k] = array('service'        => $k,
                                'start_size'     => 0,
                                'end_size'       => 0,
                                'evolution'      => 0,
                                'evolution_rate' => 0);

        }

        // Start values
        $dao = $this->_getDao();
        $dar = $dao->searchServiceSizeStart($startDate, $groupId);
        if ($dar && !$dar->isError()) {
            foreach ($dar as $row) {
                if (isset($values[$row['service']])) {
                    $values[$row['service']]['service']    = $row['service'];
                    $values[$row['service']]['start_size'] = $row['size'];
                }
            }
        }

        // End values
        $dar = $dao->searchServiceSizeEnd($endDate, $groupId);
        if ($dar && !$dar->isError()) {
            foreach ($dar as $row) {
                if (isset($values[$row['service']])) {
                    $values[$row['service']]['service']   = $row['service'];
                    $values[$row['service']]['end_size']  = $row['size'];
                    if (isset($values[$row['service']]['start_size'])) {
                        $values[$row['service']]['evolution'] = $row['size'] - $values[$row['service']]['start_size'];
                        if ($values[$row['service']]['start_size'] != 0) {
                            $values[$row['service']]['evolution_rate'] = ($row['size'] / $values[$row['service']]['start_size'])-1;
                        } else {
                            $values[$row['service']]['evolution_rate'] = 1;
                        }
                    } else {
                        $values[$row['service']]['start_size']     = 0;
                        $values[$row['service']]['evolution']      = $row['size'];
                        $values[$row['service']]['evolution_rate'] = 1;
                    }
                }
            }
        }
        return $values;
    }

    public function returnProjectWeeklyEvolution($group_id){
        $dao = $this->_getDao();
        //the Collect date
        $dateEnd = $dao->searchMostRecentDate();
        if ($dateEnd){
            $rowEnd = $dao->returnTotalSizeProject($group_id,$dateEnd);
            $timestamp = strtotime($dateEnd);
            //a week ago
            $dateStart = date('Y-m-d h:i:s', strtotime('-1 week',$timestamp));
            $rowStart = $dao->returnTotalSizeProject($group_id,$dateStart);
            if ($rowEnd && !$rowEnd->isError()) {
                $end = $rowEnd->getRow();
            }
            if ($rowStart && !$rowStart->isError()) {
                $start = $rowStart->getRow();
            }
           $evolution = array();
           $evolution['size'] = $end['size']-$start['size'];
           $evolution['rate'] = ($evolution['size']/$end['size'])*100;
           return ($evolution);
        }
        return false;
    }


    public function returnUserEvolutionForPeriod($userId, $startDate, $endDate )
    {
        $dao = $this->_getDao();
        $res = array();
        $dar = $dao->returnUserEvolutionForPeriod($userId, $startDate, $endDate);
        if (! $dar || $dar->isError()) {
            return false;
        }
        $res = $dar->getRow();
        if (! $res) {
            return false;
        }

        if (isset($res['start_size'])) {
            if ($res['start_size'] != 0) {
                $res['evolution_rate'] = ($res['end_size'] / $res['start_size'])-1;
            } else {
                $res['evolution_rate'] = 1;
            }
        } else {
            $res['start_size']     = 0;
            $res['evolution']      = $res['end_size'];
            $res['evolution_rate'] = 1;
        }
        return $res;
    }

    public function returnTotalProjectSize($group_id){
        $dao  = $this->_getDao();
        $recentDate = $dao->searchMostRecentDate();
        $dar = $dao->returnTotalSizeProject($group_id, $recentDate);
        if ($dar && !$dar->isError()) {
            $projectSize= $dar->getRow();
            return $projectSize['size'];
        }
        return false;
    }

    public function returnTotalSizeOfProjects($date) {
        $dao            = $this->_getDao();
        $projects_sizes = array();
        $projects       = $dao->searchAllGroups();

        if ($projects && !$projects->isError()) {
            foreach ($projects as $project) {
                $projects_sizes[] = $this->returnTotalSizeOfProjectNearDate($project['group_id'], $date);
            }
        }
        return $projects_sizes;
    }

    public function returnTotalSizeOfProjectNearDate($group_id, $date) {
        $dao    = $this->_getDao();
        $result = array();

        $project_size_dar = $dao->returnTotalSizeProjectNearDate($group_id, $date);
        if ($project_size_dar && !$project_size_dar->isError()) {
            $project_row  = $project_size_dar->getRow();
            $project_size = $project_row['size'];
            $result       = array(
                'group_id' => $group_id,
                'result'   => $project_size
            );
        }

        return $result;
    }

    /**
     * Get the disk cunsumption per service for a given project
     *
     * @param int $group_id The id of the project
     *
     * @return array
     */
    public function returnTotalServiceSizeByProject($group_id) {
        $dao               = $this->_getDao();
        $recentDate        = $dao->searchMostRecentDate();
        $size_per_services = $dao->searchSizePerService($recentDate, $group_id);
        $services          = array();
        foreach ($size_per_services as $row) {
            $services[$row['service']] = $row['size'];
        }
        return $services;
    }

    public function returnProjectEvolutionForPeriod($groupId, $startDate ,$endDate ){
        $dao = $this->_getDao();
        $res = array();
        $dar = $dao->returnProjectEvolutionForPeriod($groupId, $startDate ,$endDate);
        if ($dar && !$dar->isError()) {
            return $dar;
        }
        return false;
    }

    public function getTopUsers($endDate, $order) {
        $dao = $this->_getDao();
        return $dao->searchTopUsers($endDate, $order);
    }

    public function getWeeklyEvolutionProjectTotalSize($groupId,$groupBy, $startDate, $endDate){
        $groupBy = strtoupper($groupBy);
        $dao  = $this->_getDao();
        $dar = $dao->searchSizePerProjectForPeriod($groupId, $groupBy, $startDate, $endDate);
        if ($dar && !$dar->isError() && $dar->rowCount()) {
            foreach ($dar as $row) {
                $res[$this->getKeyFromGroupBy($row, $groupBy)] = $row['size'];
            }
            return $res;
        }
        return false;

    }

    public function getWeeklyEvolutionUserData($userId,$groupBy, $startDate, $endDate){
        $groupBy = strtoupper($groupBy);
        $dao  = $this->_getDao();
        $dar = $dao->searchSizePerUserForPeriod($userId, $groupBy, $startDate, $endDate);
        if ($dar && !$dar->isError() && $dar->rowCount()) {
            foreach ($dar as $row) {
                $res[$this->getKeyFromGroupBy($row, $groupBy)] = $row['size'];
            }
            return $res;
        }
        return false;

    }

    public function getWeeklyEvolutionProjectData($services, $groupId,$groupBy, $startDate, $endDate){
        $groupBy = strtoupper($groupBy);
        $dao  = $this->_getDao();
        $dar = $dao->searchSizePerServiceForPeriod($services, $groupBy, $startDate, $endDate, $groupId);
        if ($dar && !$dar->isError() && $dar->rowCount()) {
            $dates = $this->getRangeDates($dar, $groupBy);
            foreach ($dar as $row) {
                if (!isset($res[$row['service']])) {
                    $res[$row['service']] = $dates;
                }
                $res[$row['service']][$this->getKeyFromGroupBy($row, $groupBy)] = $row['size'];
            }
            return $res;
        }
        return false;

    }

    public function getProject($groupId) {
        $dao = $this->_getDao();
        $date = $dao->searchMostRecentDate();
        if ($date) {
            return $dao->searchProject($groupId, $date);
        }
        return false;
    }

    public function getDirSize($dir) {
        if (is_dir($dir)) {
            $output = array();
            exec("nice -n 19 du -s --block-size=1 $dir", $output, $returnValue);
            if ($returnValue === 0) {
                $size = split("\t", $output[0]);
                return $size[0];
            }
        }
        return false;
    }

    public function storeForGroup($groupId, $service, $path) {
        $size = $this->getDirSize($path.'/');
        if ($size) {
            $dao = $this->_getDao();
            $dao->addGroup($groupId, $service, $size, $_SERVER['REQUEST_TIME']);
        }
    }

    public function storeForUser($userId, $service, $path) {
        $size = $this->getDirSize($path.'/');
        if ($size) {
            $dao = $this->_getDao();
            $dao->addUser($userId, $service, $size, $_SERVER['REQUEST_TIME']);
        }
    }

    public function storeForSite($service, $path) {
        $size = $this->getDirSize($path.'/');
        if ($size) {
            $dao = $this->_getDao();
            $dao->addSite($service, $size, $_SERVER['REQUEST_TIME']);
        }
    }

    public function collectAll() {
        $this->collectProjects();
        $this->collectUsers();
        $this->collectSite();
    }

    /**
     * 'SVN', 'CVS', 'FRS', 'FTP', 'HOME', 'WIKI', 'MAILMAN', 'DOCMAN', 'FORUMML', 'WEBDAV',
     */
    public function collectProjects() {

        $em  = EventManager::instance();

        $dao = $this->_getDao();
        $dar = $dao->searchAllGroups();
        foreach($dar as $row) {
            //We start the transaction, it is not stored in the DB unless we COMMIT
            //With START TRANSACTION, autocommit remains disabled until we end the transaction with COMMIT or ROLLBACK.
            $sql = db_query('START TRANSACTION');
            $this->storeForGroup($row['group_id'], 'svn', $GLOBALS['svn_prefix']."/".$row['unix_group_name']);
            $this->storeForGroup($row['group_id'], 'cvs', $GLOBALS['cvs_prefix']."/".$row['unix_group_name']);
            $this->storeForGroup($row['group_id'], 'frs', $GLOBALS['ftp_frs_dir_prefix']."/".$row['unix_group_name']);
            $this->storeForGroup($row['group_id'], 'ftp', $GLOBALS['ftp_anon_dir_prefix']."/".$row['unix_group_name']);
            $this->storeForGroup($row['group_id'], self::GRP_HOME, $GLOBALS['grpdir_prefix']."/".$row['unix_group_name']);
            $this->storeForGroup($row['group_id'], 'wiki', $GLOBALS['sys_wiki_attachment_data_dir']."/".$row['group_id']);
            // Fake plugin for webdav/subversion
            $this->storeForGroup($row['group_id'], 'plugin_webdav', '/var/lib/codendi/webdav'."/".$row['unix_group_name']);

            $params = array('DiskUsageManager' => $this, 'project_row' => $row);
            $em->processEvent('plugin_statistics_disk_usage_collect_project', $params);

            $sql = db_query('COMMIT');
        }
        $this->collectMailingLists();
    }

    public function collectMailingLists() {
        $mmArchivesPath = '/var/lib/mailman/archives/private';
        $sql = db_query('START TRANSACTION');
        $dao = $this->_getDao();
        $dar = $dao->searchAllLists();
        $previous = -1;
        $sMailman = 0;
        foreach($dar as $row) {
            if ($row['group_id'] != $previous) {
                if ($previous != -1) {
                    $dao->addGroup($previous, 'mailman', $sMailman, $_SERVER['REQUEST_TIME']);
                }
                $sMailman = 0;
            }
            $sMailman += $this->getDirSize($mmArchivesPath.'/'.$row['list_name'].'/');
            $sMailman += $this->getDirSize($mmArchivesPath.'/'.$row['list_name'].'.mbox/');

            $previous = $row['group_id'];
        }
        // Last one, don't forget it!
        if ($sMailman != 0) {
            $dao->addGroup($previous, 'mailman', $sMailman, $_SERVER['REQUEST_TIME']);
        }
        //We commit all the DB modification
        $sql = db_query('COMMIT');

    }

    public function collectUsers() {
        $sql = db_query('START TRANSACTION');
        $dao = $this->_getDao();
        $dar = $dao->searchAllUsers();
        foreach($dar as $row) {
            $this->storeForUser($row['user_id'], self::USR_HOME, $GLOBALS['homedir_prefix']."/".$row['user_name']);
        }
        $sql = db_query('COMMIT');
    }

    // dfMYSQL, LOG, backup
    public function collectSite() {
        $sql = db_query('START TRANSACTION');
        $this->storeForSite('mysql', '/var/lib/mysql');
        $this->storeForSite('codendi_log', '/var/log/codendi');
        $this->storeForSite('backup', '/var/lib/codendi/backup');
        $this->storeForSite('backup_old', '/var/lib/codendi/backup/old');
        $this->storeDf();
        $sql = db_query('COMMIT');

    }

    public function storeDf() {
        $output      = array();
        $returnValue = -1;
        exec("nice -n 19 df --sync -k --portability --block-size=1", $output, $returnValue);
        if ($returnValue === 0) {
            $dao   = $this->_getDao();
            $first = true;
            foreach ($output as $line) {
                if ($first) {
                    $first = false;
                    continue;
                } else {
                    $df = preg_split("/[\s]+/", $line);
                    if ($df[0] != 'tmpfs') {
                        $dao->addSite('path_'.$df[5], $df[2], $_SERVER['REQUEST_TIME']);
                    }
                }
            }
        }
    }

    /**
     */
    public function _getDao() {
        if (!$this->_dao) {
            $this->_dao = new Statistics_DiskUsageDao(CodendiDataAccess::instance());
        }
        return $this->_dao;
    }

    /**
     * Retreive a param config giving its name
     *
     * @param String $name
     *
     * @return String
     */
    public function getProperty($name) {
        $pluginManager = PluginManager::instance();
        $p = $pluginManager->getPluginByName('statistics');
        $info =$p->getPluginInfo();
        return $info->getPropertyValueForName($name);
    }
}

?>
