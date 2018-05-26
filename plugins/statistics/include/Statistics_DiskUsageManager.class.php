<?php
/**
 * Copyright (c) STMicroelectronics, 2009. All Rights Reserved.
 * Copyright (c) Enalean, 2016 - 2018. All Rights Reserved.
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

use Tuleap\SVN\DiskUsage\Collector as SVNCollector;
use Tuleap\CVS\DiskUsage\Collector as CVSCollector;

class Statistics_DiskUsageManager {

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

    /**
     * @var Statistics_DiskUsageDao
     */
    private $dao;

    /**
     * @var SVNCollector
     */
    private $svn_collector;

    /**
     * @var EventManager
     */
    private $event_manager;

    /**
     * @var CVSCollector
     */
    private $cvs_collector;

    public function __construct(
        Statistics_DiskUsageDao $dao,
        SVNCollector $svn_collector,
        CVSCollector $cvs_collector,
        EventManager $event_manager
    ) {
        $this->dao           = $dao;
        $this->svn_collector = $svn_collector;
        $this->cvs_collector = $cvs_collector;
        $this->event_manager = $event_manager;
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

            $params = array('services' => &$this->_services);
            $this->event_manager->processEvent('plugin_statistics_disk_usage_service_label', $params);
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
                $this->event_manager->processEvent('plugin_statistics_color', $params);
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

    public function getDirSize($dir) {
        if (is_dir($dir)) {
            $output = array();
            exec("nice -n 19 du -s --block-size=1 $dir", $output, $returnValue);
            if ($returnValue === 0) {
                $size = explode("\t", $output[0]);
                return $size[0];
            }
        }
        return false;
    }

    public function storeForGroup(
        $project_id,
        $service,
        $path,
        array &$time_to_collect
    ) {
        $start = microtime(true);
        $size  = $this->getDirSize($path.'/');
        if ($size) {
            $this->dao->addGroup($project_id, $service, $size, $_SERVER['REQUEST_TIME']);
        }

        $end  = microtime(true);
        $time = $end - $start;

        $time_to_collect[$service] += $time;
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

    /**
     * @return array
     */
    public function collectAll() {
        $time_to_collect = $this->collectProjects();
        $this->collectUsers();
        $this->collectSite();

        return $time_to_collect;
    }

    /**
     * 'SVN', 'CVS', 'FRS', 'FTP', 'HOME', 'WIKI', 'MAILMAN', 'DOCMAN', 'FORUMML', 'WEBDAV',
     */
    public function collectProjects() {
        $time_to_collect = array(
            Service::SVN => 0,
            Service::CVS => 0,
            self::FRS => 0,
            self::FTP => 0,
            self::GRP_HOME => 0,
            Service::WIKI => 0,
            self::PLUGIN_WEBDAV => 0,
            self::MAILMAN => 0,
        );

        $dar = $this->dao->searchAllOpenProjects();
        foreach($dar as $row) {
            $this->dao->getDa()->startTransaction();

            $project = new Project($row);
            $this->collectSVNDiskUsage($project, $time_to_collect);
            $this->collectCVSDiskUsage($project, $time_to_collect);

            $this->storeForGroup($row['group_id'], self::FRS, $GLOBALS['ftp_frs_dir_prefix']."/".$row['unix_group_name'], $time_to_collect);
            $this->storeForGroup($row['group_id'], self::FTP, $GLOBALS['ftp_anon_dir_prefix']."/".$row['unix_group_name'], $time_to_collect);
            if (ForgeConfig::areUnixGroupsAvailableOnSystem()) {
                $this->storeForGroup($row['group_id'], self::GRP_HOME, ForgeConfig::get('grpdir_prefix') . "/" . $row['unix_group_name'], $time_to_collect);
            }
            $this->storeForGroup($row['group_id'], Service::WIKI, $GLOBALS['sys_wiki_attachment_data_dir']."/".$row['group_id'], $time_to_collect);
            // Fake plugin for webdav/subversion
            $this->storeForGroup($row['group_id'], self::PLUGIN_WEBDAV, '/var/lib/codendi/webdav'."/".$row['unix_group_name'], $time_to_collect);

            $params = array(
                'DiskUsageManager' => $this,
                'project_row'      => $row,
                'project'          => $project,
                'time_to_collect'  => &$time_to_collect
            );

            $this->event_manager->processEvent(
                'plugin_statistics_disk_usage_collect_project',
                $params
            );

            $this->dao->getDa()->commit();
        }

        $this->collectMailingLists($time_to_collect);

        return $time_to_collect;
    }

    private function collectCVSDiskUsage(Project $project, array &$time_to_collect)
    {
        $start = microtime(true);
        $size  = $this->cvs_collector->collectForCVSRepositories($project);
        if (! $size) {
            $path = ForgeConfig::get('cvs_prefix').'/'.$project->getUnixNameMixedCase();
            $size = $this->getDirSize($path.'/');
        }

        $this->dao->addGroup(
            $project->getID(),
            self::CVS,
            $size,
            $_SERVER['REQUEST_TIME']
        );

        $end  = microtime(true);
        $time = $end - $start;

        $time_to_collect[Service::CVS] += $time;
    }

    private function collectSVNDiskUsage(Project $project, array &$time_to_collect)
    {
        $start = microtime(true);
        $size  = $this->svn_collector->collectForSubversionRepositories($project);
        if (! $size) {
            $path = $project->getSVNRootPath();
            $size = $this->getDirSize($path.'/');
        }

        $this->dao->addGroup(
            $project->getID(),
            self::SVN,
            $size,
            $_SERVER['REQUEST_TIME']
        );

        $end  = microtime(true);
        $time = $end - $start;

        $time_to_collect[Service::SVN] += $time;
    }

    private function collectMailingLists(array &$time_to_collect)
    {
        $start          = microtime(true);
        $mmArchivesPath = '/var/lib/mailman/archives/private';
        $sql            = db_query('START TRANSACTION');
        $dao            = $this->_getDao();
        $dar            = $dao->searchAllLists();
        $previous       = -1;
        $sMailman       = 0;

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

        $end  = microtime(true);
        $time = $end - $start;

        $time_to_collect[self::MAILMAN] += $time;
    }

    public function collectUsers() {
        if (ForgeConfig::areUnixUsersAvailableOnSystem()) {
            $sql = db_query('START TRANSACTION');
            $dao = $this->_getDao();
            $dar = $dao->searchAllUsers();
            foreach ($dar as $row) {
                $this->storeForUser(
                    $row['user_id'], self::USR_HOME,
                    ForgeConfig::get('homedir_prefix') . "/" . $row['user_name']
                );
            }
            $sql = db_query('COMMIT');
        }
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
        return $this->dao;
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
