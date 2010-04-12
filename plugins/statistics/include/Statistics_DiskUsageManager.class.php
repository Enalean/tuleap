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

require_once 'Statistics_DiskUsageDao.class.php';
require_once('common/dao/include/DataAccessObject.class.php');

class Statistics_DiskUsageManager {
    private $_dao = null;

    const SVN = 'svn';
    const CVS = 'cvs';
    const FRS = 'frs';
    const FTP = 'ftp';
    const GRP_HOME = 'grp_home';
    const USR_HOME = 'usr_home';
    const WIKI = 'wiki';
    const PLUGIN_DOCMAN = 'plugin_docman';
    const PLUGIN_WEBDAV = 'plugin_webdav';
    const MAILMAN = 'mailman';
    const PLUGIN_FORUMML = 'plugin_forumml';
    const MYSQL = 'mysql';
    const CODENDI_LOGS = 'codendi_log';
    const BACKUP = 'backup';
    const BACKUP_OLD = 'backup_old';
    const PATH = 'path_';

    public function __construct() {
    }
    
    public function getProjectServices() {
        return array(self::SVN, self::CVS, self::FRS, self::FTP, self::GRP_HOME, self::WIKI, self::PLUGIN_DOCMAN, self::PLUGIN_WEBDAV, self::MAILMAN, self::PLUGIN_FORUMML);
    }
    
    public function getGeneralData($date) {
        $res = array();
        $dao  = $this->_getDao();
        if ($date) {
            $res['date'] = $date;

            $dar = $dao->searchSizePerService($date);
            if ($dar && !$dar->isError()) {
                foreach ($dar as $row) {
                    $res['service'][$row['service']] = $row['size'];
                }
            }

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
    
    public function getWeeklyEvolutionServiceData($services, $groupBy, $startDate, $endDate) {
        $groupBy = strtoupper($groupBy);
        $dao  = $this->_getDao();
        $dar = $dao->searchSizePerServiceForPeriod($services, $groupBy, $startDate, $endDate);
        if ($dar && !$dar->isError()) {
            foreach ($dar as $row) {
                $res[$row['service']][$this->getKeyFromGroupBy($row, $groupBy)] = $row['size'];
            }
            return $res;
         }
         return false;
         
    }

    public function getTopProjects($startDate, $endDate, $service, $order) {
        $dao = $this->_getDao();
        return $dao->getProjectContributionForService($startDate, $endDate, $service, $order);
    }
   
    public function getProjectContributionForService($startDate, $endDate, $service, $order) {
        $dao = $this->_getDao();
        return $dao->getProjectContributionForService($startDate, $endDate, $service, $order);
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
    
    public function returnServiceEvolutionForPeriod($startDate , $endDate){
        $dao = $this->_getDao();
        $dar = $dao->returnServiceEvolutionForPeriod($startDate , $endDate);
        if ($dar && !$dar->isError()) {
            return $dar;
        }
        return false;
        
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
    
    
    public function returnUserEvolutionForPeriod($userId, $startDate ,$endDate ){
        $dao = $this->_getDao();
        $res = array();
        $dar = $dao->returnUserEvolutionForPeriod($userId, $startDate ,$endDate);
        if ($dar && !$dar->isError()) {
            return $dar;
        }
        return false;
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
    
    public function getTopUsers($startDate, $endDate, $order) {
        $dao = $this->_getDao();
        return $dao->searchTopUsers($startDate, $endDate, $order);
    }
    
    public function getUserDetails($userId) {
        $dao = $this->_getDao();
        $date = $dao->searchMostRecentDate();
        if ($date) {
            return $dao->returnUserDetails($userId , $date);
        }
        return false;
    }
    
    public function getWeeklyEvolutionUserData($userId,$groupBy, $startDate, $endDate){
        $groupBy = strtoupper($groupBy);
        $dao  = $this->_getDao();
        $dar = $dao->searchSizePerUserForPeriod($userId, $groupBy, $startDate, $endDate);
        if ($dar && !$dar->isError()) {
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
        if ($dar && !$dar->isError()) {
            foreach ($dar as $row) {
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
        //We start the transaction, it is not stored in the DB unless we COMMIT
        //With START TRANSACTION, autocommit remains disabled until we end the transaction with COMMIT or ROLLBACK. 
        $sql = db_query('START TRANSACTION');
        
        $dao = $this->_getDao();
        $dar = $dao->searchAllGroups();
        foreach($dar as $row) {
            $this->storeForGroup($row['group_id'], 'svn', $GLOBALS['svn_prefix']."/".$row['unix_group_name']);
            $this->storeForGroup($row['group_id'], 'cvs', $GLOBALS['cvs_prefix']."/".$row['unix_group_name']);
            $this->storeForGroup($row['group_id'], 'frs', $GLOBALS['ftp_frs_dir_prefix']."/".$row['unix_group_name']);
            $this->storeForGroup($row['group_id'], 'ftp', $GLOBALS['ftp_anon_dir_prefix']."/".$row['unix_group_name']);
            $this->storeForGroup($row['group_id'], self::GRP_HOME, $GLOBALS['grpdir_prefix']."/".$row['unix_group_name']);
            $this->storeForGroup($row['group_id'], 'wiki', $GLOBALS['sys_wiki_attachment_data_dir']."/".$row['group_id']);
            $this->storeForGroup($row['group_id'], 'plugin_docman', '/var/lib/codendi/docman'."/".strtolower($row['unix_group_name']));
            $this->storeForGroup($row['group_id'], 'plugin_webdav', '/var/lib/codendi/webdav'."/".$row['unix_group_name']);
        }
        $this->collectMailingLists();
    }

    public function collectMailingLists() {
        $mmArchivesPath = '/var/lib/mailman/archives/private';
        $fmlPath        = '/var/lib/codendi/forumml';

        $dao = $this->_getDao();
        $dar = $dao->searchAllLists();
        $previous = -1;
        $sMailman = 0;
        $sForumML = 0;
        foreach($dar as $row) {
            if ($row['group_id'] != $previous) {
                if ($previous != -1) {
                    $dao->addGroup($previous, 'mailman', $sMailman, $_SERVER['REQUEST_TIME']);
                    $dao->addGroup($previous, 'plugin_forumml', $sForumML, $_SERVER['REQUEST_TIME']);
                }
                $sMailman = 0;
                $sForumML = 0;
            }
            $sMailman += $this->getDirSize($mmArchivesPath.'/'.$row['list_name'].'/');
            $sMailman += $this->getDirSize($mmArchivesPath.'/'.$row['list_name'].'.mbox/');
            $sForumML += $this->getDirSize($fmlPath.'/'.$row['list_name'].'/');
            $sForumML += $this->getDirSize($fmlPath.'/'.$row['group_list_id'].'/');

            $previous = $row['group_id'];
        }
        // Last one, don't forget it!
        if ($sMailman != 0) {
            $dao->addGroup($previous, 'mailman', $sMailman, $_SERVER['REQUEST_TIME']);
            $dao->addGroup($previous, 'plugin_forumml', $sForumML, $_SERVER['REQUEST_TIME']);
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
}

?>
