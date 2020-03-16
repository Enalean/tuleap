<?php
/**
 * Copyright (c) STMicroelectronics, 2009. All Rights Reserved.
 * Copyright (c) Enalean, 2012 - 2017. All Rights Reserved.
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

use Tuleap\Chart\ColorsForCharts;

class Statistics_DiskUsageHtml extends Statistics_DiskUsageOutput
{

    protected function _displayEvolutionData($row)
    {
        echo '<td>' . $this->sizeReadable($row['start_size']) . '</td>';
        echo '<td>' . $this->sizeReadable($row['end_size']) . '</td>';
        echo '<td>' . $this->sizeReadable($row['evolution']) . '</td>';
        if ($row['evolution'] == 0) {
            echo '<td>-</td>';
        } else {
            echo '<td>' . sprintf('%01.2f %%', (($row['evolution_rate'])) * 100) . '</td>';
        }
    }

    /**
     * Apply a jpgraph compliant color modifier on color and return a css rgb() rule
     */
    public function applyColorModifier($color)
    {
        $jpgraphRgb = new RGB();
        $newColor   = $jpgraphRgb->color($color . ':1.5');
        // Unset alpha channel
        unset($newColor[3]);

        // floor value to match jpgraph behaviour
        $col = implode(',', array_map('floor', $newColor));
        return 'rgb(' . $col . ')';
    }

    /**
     *
     * Displays the table of service evolution for a given period
     * for a specific project if the group_id is given else for all projects
     *
     * @param Date $startDate
     * @param Date $endDate
     * @param int $groupId
     * @param bool $colored
     *
     */
    public function getServiceEvolutionForPeriod($startDate, $endDate, $groupId = null, $colored = false)
    {
        $res = $this->_dum->returnServiceEvolutionForPeriod($startDate, $endDate, $groupId);
        if ($res) {
            $services = $this->_dum->getProjectServices();

            $colors_for_charts = new ColorsForCharts();

            $titles = array('Service', 'Start size', 'End size', 'Size evolution', 'Rate evolution');

            echo html_build_list_table_top($titles);
            $totalStartSize = 0;
            $totalEndSize   = 0;
            $totalEvolution = 0;
            $i = 0;
            foreach ($res as $row) {
                echo '<tr class="' . util_get_alt_row_color($i++) . '">';
                echo '<td>';
                if ($colored) {
                    $color = $colors_for_charts->getColorCodeFromColorName($this->_dum->getServiceColor($row['service']));
                    $color = $this->applyColorModifier($color . ':1.5');
                    echo '<span class="plugin_statistics_table_legend" style="background-color:' . $color . ';">&nbsp;</span>';
                }
                echo $services[$row['service']] . '</td>';
                $totalStartSize  += $row['start_size'];
                $totalEndSize    += $row['end_size'];
                $totalEvolution  += $row['evolution'];
                $this->_displayEvolutionData($row);
                echo '</tr>';
            }
            echo '<tr class="' . util_get_alt_row_color($i++) . '">';
            echo '<th>Total size</th>';
            echo '<td>' . $this->sizeReadable($totalStartSize) . '</td>';
            echo '<td>' . $this->sizeReadable($totalEndSize) . '</td>';
            echo '<td>' . $this->sizeReadable($totalEvolution) . '</td>';
            if ($totalEvolution == 0 || $totalStartSize == 0) {
                echo '<td>-</td>';
            } else {
                echo '<td>' . sprintf('%01.2f %%', (($totalEndSize / $totalStartSize) - 1) * 100) . '</td>';
            }
            echo '</tr>';
            echo '</tbody>';
            echo '</table>';
        }
    }

    /**
     *
     * Displays the disk usage for a given project
     *
     * @param int $groupId Id of the project we want retrieve its disk usage
     *
     */
    public function getTotalProjectSize($groupId)
    {
        $totalSize = $this->_dum->returnTotalProjectSize($groupId);

        $allowedQuota = $this->_dum->getProperty('allowed_quota');
        $pqm          = new ProjectQuotaManager();
        $allowedQuota = $pqm->getProjectCustomQuota($groupId);
        if ($allowedQuota) {
            $html = '<div style="text-align:center"><p>' . $GLOBALS['Language']->getText('plugin_statistics_admin_page', 'disk_usage_proportion', array($this->sizeReadable($totalSize), $allowedQuota . 'GiB')) . '</p></div>';
        } else {
            $html  = '<LABEL><b>';
            $html .= $GLOBALS['Language']->getText('plugin_statistics', 'widget_total_project_size');
            $html .= '</b></LABEL>';
            $html .= $this->sizeReadable($totalSize);
        }

        $html .= '<div style="text-align:center"><p>';
        $graph = '<img src="/plugins/statistics/project_cumulativeDiskUsage_graph.php?func=progress&group_id=' . $groupId . '" title="Project total disk usage graph" />';
        $user  = UserManager::instance()->getCurrentUser();
        $project = ProjectManager::instance()->getProject($groupId);
        if ($project->userIsAdmin($user)) {
            $pluginManager = PluginManager::instance();
            $p     = $pluginManager->getPluginByName('statistics');
            $html .= '<a href="' . $p->getPluginPath() . '/project_stat.php?group_id=' . $groupId . '">' . $graph . '</a>';
        } else {
            $html .= $graph;
        }
        $html .= '</p></div>';

        return $html;
    }
}
