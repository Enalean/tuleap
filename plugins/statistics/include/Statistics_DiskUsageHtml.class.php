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

require_once 'Statistics_DiskUsageOutput.class.php';

class Statistics_DiskUsageHtml extends Statistics_DiskUsageOutput {

    protected function _displayEvolutionData($row) {
        echo '<td>'.$this->sizeReadable($row['start_size']).'</td>';
        echo '<td>'.$this->sizeReadable($row['end_size']).'</td>';
        echo '<td>'.$this->sizeReadable($row['evolution']).'</td>';
        if ($row['evolution'] == 0) {
            echo '<td>-</td>';
        } else {
            echo '<td>'.sprintf('%01.2f %%', (($row['evolution_rate'])-1)*100).'</td>';
        }
    }
    
    public function getDataPerService() {
        $res = $this->_dum->getLatestData();

        echo '<table border="1">';
        echo '<thead>';
        echo '<tr>';
        echo "<th>Date</th>";
        if (isset($res['service'][Statistics_DiskUsageManager::SVN])) {
            echo "<th>SVN</th>";
        }
        if (isset($res['service'][Statistics_DiskUsageManager::CVS])) {
            echo "<th>CVS</th>";
        }
        if (isset($res['service'][Statistics_DiskUsageManager::FRS])) {
            echo "<th>FRS</th>";
        }
        if (isset($res['service'][Statistics_DiskUsageManager::FTP])) {
            echo "<th>FTP</th>";
        }
        if (isset($res['service'][Statistics_DiskUsageManager::WIKI])) {
            echo "<th>Wiki</th>";
        }
        if (isset($res['service'][Statistics_DiskUsageManager::MAILMAN])) {
            echo "<th>Mailman</th>";
        }
        if (isset($res['service'][Statistics_DiskUsageManager::PLUGIN_DOCMAN])) {
            echo "<th>Docman</th>";
        }
        if (isset($res['service'][Statistics_DiskUsageManager::PLUGIN_FORUMML])) {
            echo "<th>ForumML</th>";
        }
        if (isset($res['service'][Statistics_DiskUsageManager::PLUGIN_WEBDAV])) {
            echo "<th>Webdav</th>";
        }
        if (isset($res['service'][Statistics_DiskUsageManager::GRP_HOME])) {
            echo "<th>Groups</th>";
        }
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
        echo $this->getReadable($res, Statistics_DiskUsageManager::SVN);
        echo $this->getReadable($res, Statistics_DiskUsageManager::CVS);
        echo $this->getReadable($res, Statistics_DiskUsageManager::FRS);
        echo $this->getReadable($res, Statistics_DiskUsageManager::FTP);
        echo $this->getReadable($res, Statistics_DiskUsageManager::WIKI);
        echo $this->getReadable($res, Statistics_DiskUsageManager::MAILMAN);
        echo $this->getReadable($res, Statistics_DiskUsageManager::PLUGIN_DOCMAN);
        echo $this->getReadable($res, Statistics_DiskUsageManager::PLUGIN_FORUMML);
        echo $this->getReadable($res, Statistics_DiskUsageManager::PLUGIN_WEBDAV);
        echo $this->getReadable($res, Statistics_DiskUsageManager::GRP_HOME);
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

    public function getTopProjects($startDate, $endDate, $order, $url) {
        $res = $this->_dum->getTopProjects($startDate, $endDate, $order);
        if ($res) {
            $titles = array('Rank', 'Id', 'Name', 'Start size', 'End size', 'Evolution Size ', 'Evolution Rate (%)');
            $links  = array('', '', '', $url.'&order=start_size', $url.'&order=end_size', $url.'&order=evolution', $url.'&order=evolution_rate');
            echo html_build_list_table_top($titles, $links);
            $i = 1;
            $url = str_replace('func=show_top_projects', 'func=show_one_project', $url);
            foreach ($res as $row) {
                echo '<tr>';
                echo '<td>'.$i++.'</td>';
                echo '<td><a href="'.$url.'&group_id='.$row['group_id'].'">'.$row['group_id'].'</a></td>';
                echo '<td>'.$row['group_name'].'</td>';
                $this->_displayEvolutionData($row);
                echo '</tr>';
            }
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
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';
            foreach ($res as $row) {
                echo '<tr>';
                echo '<td>'.$row['user_id'].'</td>';
                echo '<td>'.$row['user_name'].'</td>';
                echo '<td>'.$row['service'].'</td>';
                echo '<td>'.$this->sizeReadable($row['size']).'</td>';
                echo '</tr>';
            }
            echo '</tbody>';
            echo '</table>';
        }
    }
    
     public function getProjectEvolutionForPeriod($groupId,$startDate, $endDate) {
        $res = $this->_dum->returnProjectEvolutionForPeriod($groupId,$startDate, $endDate);
        if ($res) {
            echo '<table border="1">';
            echo '<thead>';
            echo '<tr>';
            echo "<th>Project Id</th>";
            echo "<th>Start size</th>";
            echo "<th>End size</th>";
            echo "<th>Size Evolution</th>";
            echo "<th>Rate Evolution (%)</th>";
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';
            foreach ($res as $row){
                echo '<tr>';
                echo '<td>'.$groupId.'</td>';
                $this->_displayEvolutionData($row);
                echo '</tr>';
            }
            echo '</tbody>';
            echo '</table>';
        }
    }
    
    public function getServiceEvolutionForPeriod($startDate , $endDate) {
        $res = $this->_dum->returnServiceEvolutionForPeriod($startDate , $endDate);
        if ($res) {
            echo '<table border="1">';
            echo '<thead>';
            echo '<tr>';
            echo "<th>Service</th>";
            echo "<th>Start size</th>";
            echo "<th>End size</th>";
            echo "<th>Size Evolution</th>";
            echo "<th>Rate Evolution (%)</th>";
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';
            foreach ($res as $row){
                echo '<tr>';
                echo '<td>'.$row['service'].'</td>';
                $this->_displayEvolutionData($row);
                echo '</tr>';
            }
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
            foreach ($res as $row){
                echo '<tr>';
                echo '<td>'.$userId.'</td>';
                $this->_displayEvolutionData($row);
                echo '</tr>';
            }
            echo '</tbody>';
            echo '</table>';
        }
    }
    
    
    public function getTopUsers($startDate, $endDate, $order, $url) {
        $res = $this->_dum->getTopUsers($startDate, $endDate, $order);
        if ($res) {
            $titles = array('Rank', 'Id', 'Name', 'Start size', 'End size', 'Evolution Size ', 'Evolution Rate (%)');
            $links  = array('', '', '', $url.'&order=start_size', $url.'&order=end_size', $url.'&order=evolution', $url.'&order=evolution_rate');
            echo html_build_list_table_top($titles, $links);
            $url = str_replace('func=show_top_users', 'func=show_one_user', $url);
            $i = 1;
            foreach ($res as $row) {
                echo '<tr>';
                echo '<td>'.$i++.'</td>';
                echo '<td><a href="'.$url.'&user_id='.$row['user_id'].'">'.$row['user_id'].'</a></td>';
                echo '<td>'.$row['user_name'].'</td>';
                $this->_displayEvolutionData($row);
                echo '</tr>';
            }
            echo '</table>';
        }
    }

    public function getReadable($result, $key) {
        if (isset($result['service'][$key])) {
            return '<td>'.$this->sizeReadable($result['service'][$key]).'</td>';
        }
        return '';
    }

}

?>
