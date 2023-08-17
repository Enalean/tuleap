<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2009. All Rights Reserved.
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

use Tuleap\DB\Compat\Legacy2018\CompatPDODataAccessResult;
use Tuleap\Statistics\Events\StatisticsRefreshDiskUsage;
use Tuleap\Statistics\DiskUsage\Subversion\Collector as SVNCollector;
use Tuleap\Statistics\DiskUsage\ConcurrentVersionsSystem\Collector as CVSCollector;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class Statistics_DiskUsageManager
{
    private array $services = [];

    public const SVN           = 'svn';
    public const CVS           = 'cvs';
    public const FRS           = 'frs';
    public const FTP           = 'ftp';
    public const GRP_HOME      = 'grp_home';
    public const USR_HOME      = 'usr_home';
    public const WIKI          = 'wiki';
    public const PLUGIN_WEBDAV = 'plugin_webdav';
    public const MAILMAN       = 'mailman';
    public const MYSQL         = 'mysql';
    public const CODENDI_LOGS  = 'codendi_log';
    public const BACKUP        = 'backup';
    public const BACKUP_OLD    = 'backup_old';
    public const PATH          = 'path_';

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
        EventManager $event_manager,
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
     * @param bool $siteAdminView
     *
     * @return Array
     */
    public function getProjectServices($siteAdminView = true)
    {
        if (count($this->services) == 0) {
            $this->services = [self::SVN           => 'Subversion',
                self::CVS           => 'CVS',
                self::FRS           => 'File releases',
                self::FTP           => 'Public FTP',
                self::GRP_HOME      => 'Home page',
                self::WIKI          => 'Wiki',
                self::MAILMAN       => 'Mailman',
            ];
            if ($siteAdminView) {
                $this->services[self::PLUGIN_WEBDAV] = 'SVN/Webdav';
            }

            $params = ['services' => &$this->services];
            $this->event_manager->processEvent('plugin_statistics_disk_usage_service_label', $params);
        }
        return $this->services;
    }

    /**
     * Return a human readable string for service
     *
     * @param String $service
     *
     * @return String
     */
    public function getServiceColor($service)
    {
        switch ($service) {
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
                $color  = 'white';
                $params = ['service' => $service, 'color' => &$color];
                $this->event_manager->processEvent('plugin_statistics_color', $params);
                return $color;
        }
    }

    public function getGeneralData($date)
    {
        $res = [];
        $dao = $this->_getDao();
        if ($date) {
            $res['date'] = $date;

            $dar = $dao->searchTotalUserSize($date);
            if ($dar && ! $dar->isError()) {
                $row                            = $dar->getRow();
                $res['service'][self::USR_HOME] = $row['size'];
            }

            $dar = $dao->searchSiteSize($date);
            if ($dar && ! $dar->isError()) {
                foreach ($dar as $row) {
                    if (strpos($row['service'], self::PATH) !== false) {
                        $path               = substr($row['service'], strlen(self::PATH . '_') - 1);
                        $res['path'][$path] = $row['size'];
                    } else {
                        $res['service'][$row['service']] = $row['size'];
                    }
                }
            }
        }
        return $res;
    }

    public function getLatestData()
    {
        $dao  = $this->_getDao();
        $date = $dao->searchMostRecentDate();
        return $this->getGeneralData($date);
    }

    private function getKeyFromGroupBy($row, $groupBy)
    {
        switch ($groupBy) {
            case 'DAY':
                return $row['year'] . '-' . $row['month'] . '-' . $row['day'];
            case 'MONTH':
                return $row['year'] . '-' . $row['month'];
            case 'WEEK':
                return $row['year'] . '-' . $row['week'];
            default:
            case 'YEAR':
                return $row['year'];
        }
    }

    private function getRangeDates($dar, $groupBy)
    {
        $dates = [];
        foreach ($dar as $row) {
            $dates[$this->getKeyFromGroupBy($row, $groupBy)] = 0;
        }
        return $dates;
    }

    public function getWeeklyEvolutionServiceData($services, $groupBy, $startDate, $endDate)
    {
        $res     = [];
        $groupBy = strtoupper($groupBy);
        $dao     = $this->_getDao();
        $dar     = $dao->searchSizePerServiceForPeriod($services, $groupBy, $startDate, $endDate);
        if ($dar && ! $dar->isError()) {
            $dates = $this->getRangeDates($dar, $groupBy);
            foreach ($dar as $row) {
                if (! isset($res[$row['service']])) {
                    $res[$row['service']] = $dates;
                }
                $res[$row['service']][$this->getKeyFromGroupBy($row, $groupBy)] = $row['size'];
            }
            return $res;
        }
         return false;
    }

    public function getUsagePerProject($startDate, $endDate, $service, $order, $offset, $limit)
    {
        $dao   = $this->_getDao();
        $dar   = $dao->getProjectContributionForService($startDate, $endDate, $service, $order, $offset, $limit);
        $nbPrj = $dao->foundRows();
        return [$dar, $nbPrj];
    }

    /**
     * Retrieve data for the two given dates and compute some statistics
     *
     * @param String  $startDate
     * @param String  $endDate
     * @param int $groupId
     *
     * @return Array
     */
    public function returnServiceEvolutionForPeriod($startDate, $endDate, $groupId = null)
    {
        // Build final array based on services (ensure always same order)
        $values = [];
        foreach ($this->getProjectServices() as $k => $v) {
            $values[$k] = ['service'        => $k,
                'start_size'     => 0,
                'end_size'       => 0,
                'evolution'      => 0,
                'evolution_rate' => 0,
            ];
        }

        // Start values
        $dao = $this->_getDao();
        $dar = $dao->searchServiceSizeStart($startDate, $groupId);
        if ($dar && ! $dar->isError()) {
            foreach ($dar as $row) {
                if (isset($values[$row['service']])) {
                    $values[$row['service']]['service']    = $row['service'];
                    $values[$row['service']]['start_size'] = $row['size'];
                }
            }
        }

        // End values
        $dar = $dao->searchServiceSizeEnd($endDate, $groupId);
        if ($dar && ! $dar->isError()) {
            foreach ($dar as $row) {
                if (isset($values[$row['service']])) {
                    $values[$row['service']]['service']  = $row['service'];
                    $values[$row['service']]['end_size'] = $row['size'];
                    if (isset($values[$row['service']]['start_size'])) {
                        $values[$row['service']]['evolution'] = $row['size'] - $values[$row['service']]['start_size'];
                        if ($values[$row['service']]['start_size'] != 0) {
                            $values[$row['service']]['evolution_rate'] = ($row['size'] / $values[$row['service']]['start_size']) - 1;
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

    public function returnUserEvolutionForPeriod($userId, $startDate, $endDate)
    {
        $dao = $this->_getDao();
        $res = [];
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
                $res['evolution_rate'] = ($res['end_size'] / $res['start_size']) - 1;
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

    public function returnTotalProjectSize($group_id)
    {
        $dao           = $this->_getDao();
        $recentDate    = $dao->searchMostRecentDate();
        $dar           = $dao->searchServicesSizesPerProject($group_id, $recentDate);
        $usage_refresh = new StatisticsRefreshDiskUsage($group_id);

        $this->event_manager->processEvent($usage_refresh);

        if ($dar && ! $dar->isError()) {
            return $this->computeTotalSizeOfProject($dar, $usage_refresh);
        }
        return false;
    }

    private function computeTotalSizeOfProject(CompatPDODataAccessResult $dar, StatisticsRefreshDiskUsage $refresh_usage): int
    {
        $disk_usages = $this->refreshUsages(
            $this->extractSavedServicesDiskUsages($dar),
            $refresh_usage
        );

        return array_sum($disk_usages);
    }

    private function refreshUsages(array $saved_usages, StatisticsRefreshDiskUsage $refreshed_usages): array
    {
        return array_merge($saved_usages, $refreshed_usages->getRefreshedUsages());
    }

    private function extractSavedServicesDiskUsages(CompatPDODataAccessResult $dar): array
    {
        $saved_usages = [];

        foreach ($dar as $row) {
            $saved_usages[$row['service']] = $row['size'];
        }

        return $saved_usages;
    }

    public function returnTotalSizeOfProjects($date)
    {
        $dao            = $this->_getDao();
        $projects_sizes = [];
        $projects       = $dao->searchAllGroups();

        if ($projects && ! $projects->isError()) {
            foreach ($projects as $project) {
                $projects_sizes[] = $this->returnTotalSizeOfProjectNearDate($project['group_id'], $date);
            }
        }
        return $projects_sizes;
    }

    public function returnTotalSizeOfProjectNearDate($group_id, $date)
    {
        $dao    = $this->_getDao();
        $result = [];

        $project_size_dar = $dao->returnTotalSizeProjectNearDate($group_id, $date);
        if ($project_size_dar && ! $project_size_dar->isError()) {
            $project_row  = $project_size_dar->getRow();
            $project_size = $project_row['size'];
            $result       = [
                'group_id' => $group_id,
                'result'   => $project_size,
            ];
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
    public function returnTotalServiceSizeByProject($group_id)
    {
        $dao               = $this->_getDao();
        $recentDate        = $dao->searchMostRecentDate();
        $size_per_services = $dao->searchSizePerService($recentDate, $group_id);
        $usage_refresh     = new StatisticsRefreshDiskUsage($group_id);

        $this->event_manager->processEvent($usage_refresh);

        return $this->refreshUsages(
            $this->extractSavedServicesDiskUsages($size_per_services),
            $usage_refresh
        );
    }

    public function getTopUsers($endDate, $order)
    {
        $dao = $this->_getDao();
        return $dao->searchTopUsers($endDate, $order);
    }

    public function getWeeklyEvolutionProjectTotalSize($groupId, $groupBy, $startDate, $endDate)
    {
        $groupBy = strtoupper($groupBy);
        $dao     = $this->_getDao();
        $dar     = $dao->searchSizePerProjectForPeriod($groupId, $groupBy, $startDate, $endDate);
        if ($dar && ! $dar->isError() && $dar->rowCount()) {
            foreach ($dar as $row) {
                $res[$this->getKeyFromGroupBy($row, $groupBy)] = $row['size'];
            }
            return $res;
        }
        return false;
    }

    public function getWeeklyEvolutionUserData($userId, $groupBy, $startDate, $endDate)
    {
        $groupBy = strtoupper($groupBy);
        $dao     = $this->_getDao();
        $dar     = $dao->searchSizePerUserForPeriod($userId, $groupBy, $startDate, $endDate);
        if ($dar && ! $dar->isError() && $dar->rowCount()) {
            foreach ($dar as $row) {
                $res[$this->getKeyFromGroupBy($row, $groupBy)] = $row['size'];
            }
            return $res;
        }
        return false;
    }

    public function getWeeklyEvolutionProjectData($services, $groupId, $groupBy, $startDate, $endDate)
    {
        $groupBy = strtoupper($groupBy);
        $dao     = $this->_getDao();
        $dar     = $dao->searchSizePerServiceForPeriod($services, $groupBy, $startDate, $endDate, $groupId);
        if ($dar && ! $dar->isError() && $dar->rowCount()) {
            $dates = $this->getRangeDates($dar, $groupBy);
            foreach ($dar as $row) {
                if (! isset($res[$row['service']])) {
                    $res[$row['service']] = $dates;
                }
                $res[$row['service']][$this->getKeyFromGroupBy($row, $groupBy)] = $row['size'];
            }
            return $res;
        }
        return false;
    }

    /**
     * @return int|false
     */
    public function getDirSize($dir)
    {
        if (is_dir($dir)) {
            $output = [];
            exec("nice -n 19 du -s --block-size=1 " . escapeshellarg($dir), $output, $returnValue);
            if ($returnValue === 0) {
                $size = explode("\t", $output[0]);
                return (int) $size[0];
            }
        }
        return false;
    }

    public function storeForGroup(
        DateTimeImmutable $collect_date,
        $project_id,
        $service,
        $path,
        array &$time_to_collect,
    ) {
        $start = microtime(true);
        $size  = $this->getDirSize($path . '/');
        if ($size) {
            $this->dao->addGroup($project_id, $service, $size, $collect_date->getTimestamp());
        }

        $end  = microtime(true);
        $time = $end - $start;

        $time_to_collect[$service] += $time;
    }

    public function storeForUser(DateTimeImmutable $collect_date, $userId, $service, $path)
    {
        $size = $this->getDirSize($path . '/');
        if ($size) {
            $dao = $this->_getDao();
            $dao->addUser($userId, $service, $size, $collect_date->getTimestamp());
        }
    }

    public function storeForSite(DateTimeImmutable $collect_date, $service, $path)
    {
        $size = $this->getDirSize($path . '/');
        if ($size) {
            $dao = $this->_getDao();
            $dao->addSite($service, $size, $collect_date->getTimestamp());
        }
    }

    /**
     * @return array
     */
    public function collectAll()
    {
        $collect_date    = new DateTimeImmutable();
        $time_to_collect = $this->collectProjects($collect_date);
        $this->collectUsers($collect_date);
        $this->collectSite($collect_date);

        return $time_to_collect;
    }

    /**
     * 'SVN', 'CVS', 'FRS', 'FTP', 'HOME', 'WIKI', 'MAILMAN', 'DOCMAN', 'WEBDAV',
     */
    private function collectProjects(DateTimeImmutable $collect_date)
    {
        $time_to_collect = [
            Service::SVN => 0,
            Service::CVS => 0,
            self::FRS => 0,
            self::FTP => 0,
            self::GRP_HOME => 0,
            Service::WIKI => 0,
            self::PLUGIN_WEBDAV => 0,
            self::MAILMAN => 0,
        ];

        $dar = $this->dao->searchAllOpenProjects();
        foreach ($dar as $row) {
            $this->dao->getDa()->startTransaction();

            $project = new Project($row);
            $this->collectSVNDiskUsage($project, $collect_date, $time_to_collect);
            $this->collectCVSDiskUsage($project, $collect_date, $time_to_collect);

            $this->storeForGroup($collect_date, $row['group_id'], self::FRS, ForgeConfig::get('ftp_frs_dir_prefix') . "/" . $row['unix_group_name'], $time_to_collect);
            $this->storeForGroup($collect_date, $row['group_id'], self::FTP, ForgeConfig::get('ftp_anon_dir_prefix') . "/" . $row['unix_group_name'], $time_to_collect);
            if (ForgeConfig::areUnixGroupsAvailableOnSystem()) {
                $this->storeForGroup($collect_date, $row['group_id'], self::GRP_HOME, ForgeConfig::get('grpdir_prefix') . "/" . $row['unix_group_name'], $time_to_collect);
            }
            $this->storeForGroup($collect_date, $row['group_id'], Service::WIKI, ForgeConfig::get('sys_wiki_attachment_data_dir') . "/" . $row['group_id'], $time_to_collect);
            // Fake plugin for webdav/subversion
            $this->storeForGroup($collect_date, $row['group_id'], self::PLUGIN_WEBDAV, '/var/lib/codendi/webdav' . "/" . $row['unix_group_name'], $time_to_collect);

            $params = [
                'DiskUsageManager' => $this,
                'project_row'      => $row,
                'project'          => $project,
                'collect_date'     => $collect_date,
                'time_to_collect'  => &$time_to_collect,
            ];

            $this->event_manager->processEvent(
                'plugin_statistics_disk_usage_collect_project',
                $params
            );

            $this->dao->getDa()->commit();
        }

        $this->collectMailingLists($collect_date, $time_to_collect);

        return $time_to_collect;
    }

    private function collectCVSDiskUsage(Project $project, DateTimeImmutable $collect_date, array &$time_to_collect)
    {
        $start = microtime(true);
        $size  = $this->cvs_collector->collectForCVSRepositories($project);
        if (! $size) {
            $path = ForgeConfig::get('cvs_prefix') . '/' . $project->getUnixNameMixedCase();
            $size = $this->getDirSize($path . '/');
        }

        $this->dao->addGroup(
            $project->getID(),
            self::CVS,
            $size,
            $collect_date->getTimestamp()
        );

        $end  = microtime(true);
        $time = $end - $start;

        $time_to_collect[Service::CVS] += $time;
    }

    private function collectSVNDiskUsage(Project $project, DateTimeImmutable $collect_date, array &$time_to_collect)
    {
        $start = microtime(true);
        $size  = $this->svn_collector->collectForSubversionRepositories($project);
        if (! $size) {
            $path = $project->getSVNRootPath();
            $size = $this->getDirSize($path . '/');
        }

        $this->dao->addGroup(
            $project->getID(),
            self::SVN,
            $size,
            $collect_date->getTimestamp()
        );

        $end  = microtime(true);
        $time = $end - $start;

        $time_to_collect[Service::SVN] += $time;
    }

    private function collectMailingLists(DateTimeImmutable $collect_date, array &$time_to_collect)
    {
        $start          = microtime(true);
        $mmArchivesPath = '/var/lib/mailman/archives/private';
        $dao            = $this->_getDao();
        $dao->startTransaction();
        $dar      = $dao->searchAllLists();
        $previous = -1;
        $sMailman = 0;

        foreach ($dar as $row) {
            if ($row['group_id'] != $previous) {
                if ($previous != -1) {
                    $dao->addGroup($previous, 'mailman', $sMailman, $collect_date->getTimestamp());
                }
                $sMailman = 0;
            }
            $sMailman += $this->getDirSize($mmArchivesPath . '/' . $row['list_name'] . '/') ?: 0;
            $sMailman += $this->getDirSize($mmArchivesPath . '/' . $row['list_name'] . '.mbox/') ?: 0;

            $previous = $row['group_id'];
        }
        // Last one, don't forget it!
        if ($sMailman != 0) {
            $dao->addGroup($previous, 'mailman', $sMailman, $collect_date->getTimestamp());
        }
        //We commit all the DB modification
        $dao->commit();

        $end  = microtime(true);
        $time = $end - $start;

        $time_to_collect[self::MAILMAN] += $time;
    }

    private function collectUsers(DateTimeImmutable $collect_date)
    {
        if (ForgeConfig::areUnixUsersAvailableOnSystem()) {
            $dao = $this->_getDao();
            $dao->startTransaction();
            $dar = $dao->searchAllUsers();
            foreach ($dar as $row) {
                $this->storeForUser(
                    $collect_date,
                    $row['user_id'],
                    self::USR_HOME,
                    ForgeConfig::get('homedir_prefix') . "/" . $row['user_name']
                );
            }
            $dao->commit();
        }
    }

    // dfMYSQL, LOG, backup
    private function collectSite(DateTimeImmutable $collect_date)
    {
        $this->_getDao()->startTransaction();
        $this->storeForSite($collect_date, 'mysql', '/var/lib/mysql');
        $this->storeForSite($collect_date, 'codendi_log', '/var/log/codendi');
        $this->storeForSite($collect_date, 'backup', '/var/lib/codendi/backup');
        $this->storeForSite($collect_date, 'backup_old', '/var/lib/codendi/backup/old');
        $this->storeDf($collect_date);
        $this->_getDao()->commit();
    }

    public function storeDf(DateTimeImmutable $collect_date)
    {
        $output      = [];
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
                        $dao->addSite('path_' . $df[5], $df[2], $collect_date->getTimestamp());
                    }
                }
            }
        }
    }

    //phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    public function _getDao()
    {
        return $this->dao;
    }

    /**
     * Retreive a param config giving its name
     *
     * @param String $name
     *
     * @return String
     */
    public function getProperty($name)
    {
        $pluginManager = PluginManager::instance();
        $p             = $pluginManager->getPluginByName('statistics');
        assert($p instanceof StatisticsPlugin);
        $info = $p->getPluginInfo();

        return $info->getPropertyValueForName($name);
    }
}
