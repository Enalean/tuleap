<?php
/**
 * Copyright (c) STMicroelectronics, 2009. All Rights Reserved.
 *
 * Originally written by Nouha Terzi, 2009
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

require_once 'common/chart/Chart.class.php';
require_once 'Statistics_DiskUsageOutput.class.php';

class Statistics_DiskUsageGraph extends Statistics_DiskUsageOutput {
    /**
     * Return a human readable string for service
     *
     * @param String $service
     *
     * @return String
     */
    public function getServiceColor($service) {
        switch($service) {
            case Statistics_DiskUsageManager::SVN:
                return 'darkgreen';
            case Statistics_DiskUsageManager::CVS:
                return 'darkseagreen';
            case Statistics_DiskUsageManager::FRS:
                return 'cornflowerblue';
            case Statistics_DiskUsageManager::FTP:
                return 'royalblue';
            case Statistics_DiskUsageManager::WIKI:
                return 'darkslategray';
            case Statistics_DiskUsageManager::MAILMAN:
                return 'darkkhaki';
            case Statistics_DiskUsageManager::PLUGIN_WEBDAV:
                return 'gainsboro';
            case Statistics_DiskUsageManager::GRP_HOME:
                return 'lavender';
            case Statistics_DiskUsageManager::USR_HOME:
                return 'darkturquoise';
            case Statistics_DiskUsageManager::MYSQL:
                return 'sandybrown';
            case Statistics_DiskUsageManager::CODENDI_LOGS:
                return 'forestgreen';
            case Statistics_DiskUsageManager::BACKUP:
                return 'saddlebrown';
            case Statistics_DiskUsageManager::BACKUP_OLD:
                return 'peru';
            default:
                // If plugins don't want to color themselves they are white
                $color = 'white';
                $params = array('service' => $service, 'color' => &$color);
                $em = EventManager::instance();
                $em->processEvent('plugin_statistics_color', $params);
                return $color;
        }
    }

    /**
     * 
     * @param Array $services
     * @param unknown_type $groupBy
     * @param unknown_type $startDate
     * @param unknown_type $endDate
     * @param Boolean $absolute Is y-axis relative to data set or absolute (starting from 0)
     */
    function displayServiceGraph($services, $groupBy, $startDate, $endDate, $absolute=true){
        $graph = new Chart(650,450,"auto");
        $graph->img->SetMargin(70,50,20,20);
        $graph->SetScale("textint");
        $graph->title->Set("Services growth over the time");

        $graph->yaxis->title->Set("Size");
        $graph->yaxis->SetTitleMargin(60);
        $graph->yaxis->setLabelFormatCallback(array($this, 'sizeReadable'));
        if ($absolute) {
            $graph->yaxis->scale->SetAutoMin(0);
        }

        $servicesList = $this->_dum->getProjectServices();
        
        $data = $this->_dum->getWeeklyEvolutionServiceData($services, $groupBy, $startDate, $endDate);
        $i = 0;
        $dates = array();
        foreach ($data as $service => $values) {
            $ydata = array();
            foreach ($values as $date => $size) {
                $dates[] = $date;
                $ydata[] = $size;
            }
            $lineplot = new LinePlot($ydata);

            $color = $this->getServiceColor($service);
            $lineplot->SetColor($color);
            $lineplot->SetFillColor($color.':1.5');
            $lineplot->SetLegend($servicesList[$service]);

            //$lineplot->value->show();
            $lineplot->value->SetFont($graph->getFont(), FS_NORMAL, 8);
            $lineplot->value->setFormatCallback(array($this, 'sizeReadable'));
            $graph->Add($lineplot);
            $i++;
        }

        $graph->xaxis->title->Set("Weeks");
        $graph->xaxis->SetTitleMargin(15);
        $graph->xaxis->SetTickLabels($dates);
        
        
        $graph->Stroke();
    }
    
     /**
     * 
     * @param int $userId
     * @param unknown_type $groupBy
     * @param unknown_type $startDate
     * @param unknown_type $endDate
     * @param Boolean $absolute Is y-axis relative to data set or absolute (starting from 0)
     */
    function displayUserGraph($userId, $groupBy, $startDate, $endDate, $absolute=true){
        $graph = new Chart(650,450,"auto");
        $graph->img->SetMargin(70,50,20,20);
        $graph->SetScale("textlin");
        $graph->title->Set("User growth over the time");

        $graph->yaxis->title->Set("Size");
        $graph->yaxis->SetTitleMargin(60);
        $graph->yaxis->setLabelFormatCallback(array($this, 'sizeReadable'));
        if ($absolute) {
            $graph->yaxis->scale->SetAutoMin(0);
        }

        $data = $this->_dum->getWeeklyEvolutionUserData($userId, $groupBy, $startDate, $endDate);
        $dates = array();
        $ydata = array();
        foreach ($data as $xdate => $values) {
             $dates[] = $xdate;
            $ydata[] = (float)$values;
        }
               
        $lineplot = new BarPlot($ydata);
        $lineplot->SetColor('blue');
      
        $lineplot->value->SetFont($graph->getFont(), FS_NORMAL, 8);
        $lineplot->value->setFormatCallback(array($this, 'sizeReadable'));
        $graph->Add($lineplot);

        $graph->xaxis->title->Set("Weeks");
        $graph->xaxis->SetTitleMargin(15);
        $graph->xaxis->SetTickLabels($dates);
        
        
        $graph->Stroke();
    }
   
     /**
     * 
     * @param Integer $groupId
     * @param Array   $services
     * @param String  $groupBy
     * @param Date    $startDate
     * @param Date    $endDate
     * @param Boolean $absolute Is y-axis relative to data set or absolute (starting from 0)
     */
    function displayProjectGraph($groupId, $services, $groupBy, $startDate, $endDate, $absolute=true){
       $graph = new Chart(650,450,"auto");
        $graph->img->SetMargin(70,50,20,20);
        $graph->SetScale("textint");
        $graph->title->Set("Project by service growth over the time");

        $graph->yaxis->title->Set("Size");
        $graph->yaxis->SetTitleMargin(60);
        $graph->yaxis->setLabelFormatCallback(array($this, 'sizeReadable'));
        if ($absolute) {
            $graph->yaxis->scale->SetAutoMin(0);
        }

        $servicesList = $this->_dum->getProjectServices();

        $data = $this->_dum->getWeeklyEvolutionProjectData($services, $groupId, $groupBy, $startDate, $endDate);
        $i = 0;
        $dates = array();
        foreach ($data as $service => $values) {
            $ydata = array();
            foreach ($values as $date => $size) {
                $dates[] = $date;
                $ydata[] = $size;
            }
            $lineplot = new LinePlot($ydata);

            $color = $this->getServiceColor($service);
            $lineplot->SetColor($color);
            $lineplot->SetFillColor($color.':1.5');
            $lineplot->SetLegend($servicesList[$service]);

            //$lineplot->value->show();
            $lineplot->value->SetFont($graph->getFont(), FS_NORMAL, 8);
            $lineplot->value->setFormatCallback(array($this, 'sizeReadable'));
            $graph->Add($lineplot);
            $i++;
        }

        $graph->xaxis->title->Set("Weeks");
        $graph->xaxis->SetTitleMargin(15);
        $graph->xaxis->SetTickLabels($dates);
        
        
        $graph->Stroke();
    }
 
   /**
     *
     * @param Integer $groupId
     * @param Array   $services    //not nedd to services here
     * @param String  $groupBy
     * @param Date    $startDate
     * @param Date    $endDate
     * @param Boolean $absolute Is y-axis relative to data set or absolute (starting from 0)
     */
    function displayProjectTotalSizeGraph($groupId, $groupBy, $startDate, $endDate, $absolute=true){
        $graph = new Chart(420,340,"auto");
        $graph->img->SetMargin(70,50,30,70);
        $graph->SetScale("textlin");
        $graph->title->Set("Total project size growth over the time");

        $graph->yaxis->title->Set("Size");
        $graph->yaxis->SetTitleMargin(60);
        $graph->yaxis->setLabelFormatCallback(array($this, 'sizeReadable'));
        if ($absolute) {
            $graph->yaxis->scale->SetAutoMin(0);
        }

        $data = $this->_dum->getWeeklyEvolutionProjectTotalSize($groupId,$groupBy, $startDate, $endDate);
        $dates = array();
        $ydata = array();
        foreach ($data as $xdate => $values) {
            $dates[] = $xdate;
            $ydata[] = (float)$values;
        }

        $lineplot = new LinePlot($ydata);

        $color ='#6BA132';
        $lineplot->SetColor($color);
        $lineplot->SetFillColor($color.':1.5');

        $lineplot->value->SetFont($graph->getFont(), FS_NORMAL, 8);
        $lineplot->value->setFormatCallback(array($this, 'sizeReadable'));
        $graph->Add($lineplot);

        $graph->xaxis->title->Set("Weeks");
        $graph->xaxis->SetTitleMargin(35);
        $graph->xaxis->SetTickLabels($dates);

        $graph->Stroke();
    }

}

?>