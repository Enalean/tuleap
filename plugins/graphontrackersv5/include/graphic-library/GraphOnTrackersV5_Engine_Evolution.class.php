<?php
/*
 * Copyright (c) Enalean, 2013. All Rights Reserved.
 *
 * Originally written by Yoann Celton, 2013. Jtekt Europe SAS.
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
require_once('GraphOnTrackersV5_Engine.class.php');

class GraphOnTrackersV5_Engine_Evolution extends GraphOnTrackersV5_Engine {
    
    public $unit;
    public $nb_step;
    public $start_date;
    
    function validData(){
        
        if ($this->nb_step && $this->nb_step > 0){
            return true;
        }else{
            echo " <p class='feedback_info'>".$GLOBALS['Language']->getText('plugin_graphontrackersv5_engine','no_datas',array($this->title))."</p>";
            return false;
        }
    }
    
    /**
     * @return Chart
     */
    public function buildGraph() {
        require_once('common/chart/Chart.class.php');
        echo "<pre>"; var_dump($this->data); echo "</pre>";
        if ($this->width == 0) {
            $this->width = (count($this->data)*100)+(2*150);
        }
        
        $right_margin = 50;
        
        $this->graph = new Chart($this->width,$this->height);
        $this->graph->SetScale("textlint");
        $this->graph->title->Set($this->title);
        
        
        /*
        $burndown = new Tracker_Chart_Burndown($this->data);
        $burndown->setTitle($this->title);
        $burndown->setDescription($this->description);
        $burndown->setWidth($this->width);
        $burndown->setHeight($this->height);
        $burndown->setStartDate($this->start_date);
        $burndown->setDuration($this->duration);
        
        $this->graph = $burndown->buildGraph();
        //*/
        return $this->graph;
    }
}
?>
