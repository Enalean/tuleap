<?php
/**
 * Copyright (c) STMicroelectronics, 2009. All Rights Reserved.
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'Statistics_DiskUsageOutput.class.php';
require_once 'ProjectQuotaManager.class.php';
require_once 'common/chart/Chart.class.php';

class Statistics_DiskUsageHtml extends Statistics_DiskUsageOutput {

    protected function _displayEvolutionData($row) {
        echo '<td>'.$this->sizeReadable($row['start_size']).'</td>';
        echo '<td>'.$this->sizeReadable($row['end_size']).'</td>';
        echo '<td>'.$this->sizeReadable($row['evolution']).'</td>';
        if ($row['evolution'] == 0) {
            echo '<td>-</td>';
        } else {
            echo '<td>'.sprintf('%01.2f %%', (($row['evolution_rate']))*100).'</td>';
        }
    }

    public function getDataPerService($groupId = NULL) {
        $res = $this->_dum->getLatestData($groupId);

        echo '<table border="1">';
        echo '<thead>';
        echo '<tr>';
        echo "<th>Date</th>";

        if (isset($res['service'][Statistics_DiskUsageManager::USR_HOME])) {
            echo "<th>Users</th>";
        }
        if (isset($res['service'][Statistics_DiskUsageManager::MYSQL])) {
            echo "<th>MySQL</th>";
        }
        if (isset($res['service'][Statistics_DiskUsageManager::CODENDI_LOGS])) {
            echo "<th>Codendi Logs</th>";
        }
        if (isset($res['service'][Statistics_DiskUsageManager::BACKUP])) {
            echo "<th>Backup</th>";
        }
        if (isset($res['service'][Statistics_DiskUsageManager::BACKUP_OLD])) {
            echo "<th>BackupOld</th>";
        }
        foreach ($res['path'] as $path => $size) {
            echo "<th>".$path."</th>";
        }

        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        echo '<tr>';
        echo '<td>'.date('Y-m-d', strtotime($res['date'])).'</td>';
        echo $this->getReadable($res, Statistics_DiskUsageManager::USR_HOME);
        echo $this->getReadable($res, Statistics_DiskUsageManager::MYSQL);
        echo $this->getReadable($res, Statistics_DiskUsageManager::CODENDI_LOGS);
        echo $this->getReadable($res, Statistics_DiskUsageManager::BACKUP);
        echo $this->getReadable($res, Statistics_DiskUsageManager::BACKUP_OLD);
        foreach ($res['path'] as $path => $size) {
            echo "<td>".$this->sizeReadable($size)."</td>";
        }
        echo '</tr>';
        echo '</tbody>';
        echo '</table>';
    }

    public function getTopProjects($startDate, $endDate, $service, $order, $url, $offset) {
        $limit  = 100;
        list($res, $nbRows) = $this->_dum->getTopProjects($startDate, $endDate, $service, $order, $offset, $limit);
        if ($res) {
            $titles = array('Rank', 'Id', 'Name', 'Start size', 'End size', 'Evolution Size ', 'Evolution Rate (%)');
            $links  = array('', '', '', $url.'&order=start_size', $url.'&order=end_size', $url.'&order=evolution', $url.'&order=evolution_rate');
            echo html_build_list_table_top($titles, $links);
            $i = 1;
            $onProjectUrl = str_replace('func=show_top_projects', 'func=show_one_project', $url);
            foreach ($res as $row) {
                echo '<tr>';
                echo '<td>'.($offset+$i++).'</td>';
                echo '<td><a href="'.$onProjectUrl.'&group_id='.$row['group_id'].'">'.$row['group_id'].'</a></td>';
                echo '<td>'.$row['group_name'].'</td>';
                $this->_displayEvolutionData($row);
                echo '</tr>';
            }

            // Paginate
            $url .= '&order='.$order;
            echo '<tr>';
            echo '<td colspan="7" align="center">';
            if ($offset > 0) {
                echo '<a href="'.$url.'&offset='.($offset-$limit).'">[ Previous ]</a>';
            } else {
                echo '[ Previous ]';
            }
            echo '&nbsp;';
            echo ($offset+$limit).'/'.$nbRows;
            echo '&nbsp;';
            if (($offset + $limit) < $nbRows) {
                echo '<a href="'.$url.'&offset='.($offset+$limit).'">[ Next ]</a>';
            } else {
                echo '[ Next ]';
            }
            echo '</td>';
            echo '</tr>';
            echo '</table>';
        }
    }

    public function getProject($groupId) {
        $res = $this->_dum->getProject($groupId);
        if ($res) {
            echo '<table border="1">';
            echo '<thead>';
            echo '<tr>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';
            foreach ($res as $row) {
                echo '<tr>';
                echo '<td>'.$row['service'].'</td>';
                echo '<td>'.$this->sizeReadable($row['size']).'</td>';
                echo '</tr>';
            }
            echo '</tbody>';
            echo '</table>';
        }
    }


    public function getUserDetails($userId){
        $res = $this->_dum->getUserDetails($userId);
        if ($res) {
            echo '<table border="1">';
            echo '<thead>';
            echo '<tr>';
            echo "<th>User Id</th>";
            echo "<th>User Name</th>";
            echo "<th>Service</th>";
            echo "<th>Size</th>";
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';
            foreach ($res as $row) {
                echo '<tr>';
                echo '<td><A HREF="/admin/usergroup.php?user_id='.$row['user_id'].'">'.$row['user_id'].'</A></td>';
                echo '<td>'.$row['user_name'].'</td>';
                echo '<td>'.$row['service'].'</td>';
                echo '<td>'.$this->sizeReadable($row['size']).'</td>';
                echo '</tr>';
            }
            echo '</tbody>';
            echo '</table>';
        }
    }

    /**
     * Apply a jpgraph compliant color modifier on color and return a css rgb() rule
     */
    function applyColorModifier($color) {
        $jpgraphRgb = new RGB();
        $newColor   = $jpgraphRgb->color($color.':1.5');
        // Unset alpha channel
        unset($newColor[3]);

        // floor value to match jpgraph behaviour
        $col = implode(',', array_map('floor', $newColor));
        return 'rgb('.$col.')';
    }

    /**
     *
     * Displays the table of service evolution for a given period
     * for a specific project if the group_id is given else for all projects
     *
     * @param Date $startDate
     * @param Date $endDate
     * @param Integer $groupId
     * @param Boolean $colored
     *
     */
    public function getServiceEvolutionForPeriod($startDate , $endDate, $groupId = NULL, $colored = false) {
        $res = $this->_dum->returnServiceEvolutionForPeriod($startDate , $endDate, $groupId);
        if ($res) {
            $services = $this->_dum->getProjectServices();

            $titles = array('Service', 'Start size', 'End size', 'Size evolution', 'Rate evolution');

            echo html_build_list_table_top($titles);
            $totalStartSize = 0;
            $totalEndSize   = 0;
            $totalEvolution = 0;
            $i = 0;
            foreach ($res as $row){
                echo '<tr class="'. util_get_alt_row_color($i++) .'">';
                echo '<td>';
                if ($colored) {
                    $color = $GLOBALS['HTML']->getColorCodeFromColorName($this->_dum->getServiceColor($row['service']));
                    $color = $this->applyColorModifier($color.':1.5');
                    echo '<span class="plugin_statistics_table_legend" style="background-color:'.$color.';">&nbsp;</span>';
                }
                echo $services[$row['service']].'</td>';
                $totalStartSize  +=$row['start_size'];
                $totalEndSize    +=$row['end_size'];
                $totalEvolution  +=$row['evolution'];
                $this->_displayEvolutionData($row);
                echo '</tr>';
            }
            echo '<tr class="'. util_get_alt_row_color($i++) .'">';
            echo '<th>Total size</th>';
            echo '<td>'.$this->sizeReadable($totalStartSize).'</td>';
            echo '<td>'.$this->sizeReadable($totalEndSize).'</td>';
            echo '<td>'.$this->sizeReadable($totalEvolution).'</td>';
            if ($totalEvolution == 0 || $totalStartSize == 0) {
                echo '<td>-</td>';
            } else {
                echo '<td>'.sprintf('%01.2f %%', (($totalEndSize/$totalStartSize)-1)*100).'</td>';
            }
            echo '</tr>';
            echo '</tbody>';
            echo '</table>';
        }
    }

    public function getUserEvolutionForPeriod($userId, $startDate , $endDate) {
        $res = $this->_dum->returnUserEvolutionForPeriod($userId, $startDate, $endDate);
        if ($res) {
            echo '<table border="1">';
            echo '<thead>';
            echo '<tr>';
            echo "<th>User Id</th>";
            echo "<th>Start size</th>";
            echo "<th>End size</th>";
            echo "<th>Size Evolution</th>";
            echo "<th>Rate Evolution (%)</th>";
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';
                echo '<tr>';
                echo '<td>'.$userId.'</td>';
                $this->_displayEvolutionData($res);
                echo '</tr>';
            echo '</tbody>';
            echo '</table>';
        }
    }

    /**
     *
     * Displays the disk usage for a given project
     *
     * @param Integer $groupId Id of the project we want retrieve its disk usage
     *
     */
    public function getTotalProjectSize($groupId) {
        $totalSize = $this->_dum->returnTotalProjectSize($groupId);

        $allowedQuota = $this->_dum->getProperty('allowed_quota');
        $pqm          = new ProjectQuotaManager();
        $allowedQuota = $pqm->getProjectCustomQuota($groupId);
        if ($allowedQuota) {
            $html = '<div style="text-align:center"><p>'.$GLOBALS['Language']->getText('plugin_statistics_admin_page', 'disk_usage_proportion', array($this->sizeReadable($totalSize), $allowedQuota.'GiB')).'</p></div>';
        } else {
            $html  = '<LABEL><b>';
            $html .= $GLOBALS['Language']->getText('plugin_statistics', 'widget_total_project_size');
            $html .= '</b></LABEL>';
            $html .= $this->sizeReadable($totalSize);
        }

        $html .= '<div style="text-align:center"><p>';
        $graph = '<img src="/plugins/statistics/project_cumulativeDiskUsage_graph.php?func=progress&group_id='.$groupId.'" title="Project total disk usage graph" />';
        $user  = UserManager::instance()->getCurrentUser();
        $project = ProjectManager::instance()->getProject($groupId);
        if ($project->userIsAdmin($user)) {
            $pluginManager = PluginManager::instance();
            $p     = $pluginManager->getPluginByName('statistics');
            $html .= '<a href="'.$p->getPluginPath().'/project_stat.php?group_id='.$groupId.'">'.$graph.'<a>';
        } else {
            $html .= $graph;
        }
        $html .= '</p></div>';

        return $html;
    }

    public function getReadable($result, $key) {
        if (isset($result['service'][$key])) {
            return '<td>'.$this->sizeReadable($result['service'][$key]).'</td>';
        }
        return '';
    }
}
