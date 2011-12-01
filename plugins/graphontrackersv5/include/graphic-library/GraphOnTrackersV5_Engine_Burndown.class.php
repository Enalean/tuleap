<?php
/*
 * Copyright (c) Xerox, 2008. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2008. Xerox Codendi Team.
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
class GraphOnTrackersV5_Engine_Burndown extends GraphOnTrackersV5_Engine {
    
    public $duration;
    
    function validData(){
        if ((is_array($this->duration)) && ($this->duration > 0)){
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
        $this->graph = new Chart($this->width,$this->height);
        $this->graph->SetScale("datlin");
        
        // title setup
        $this->graph->title->Set($this->title);
        
        //description setup
        if (is_null($this->description)) {
            $this->description = "";
        }
        $this->graph->subtitle->Set($this->description);
        
        // order this->data by date asc
        ksort($this->data);
        
        // Initial estimation line: a * x + b
        // where b is the sum of effort for the first day
        //       x is the number of days (starting from 0 to duration
        //       a is the slope of the line equals -b/duration (burn down goes down)
        
        
        // Build data for initial estimation
        list($first_day, $b) = each($this->data);
        $b = array_sum($b);
        $day = 24 * 60 * 60;
        $start_of_sprint = $first_day;
        $a = - $b / $this->duration;
        $data_initial_estimation = array();
        $dates = array();
        //$end_of_weeks = array();
        $data = array();
        $previous = $b; // init with sum of effort for the first day
        // for each day
        for ($x = 0 ; $x <= $this->duration ; ++$x) {
            $data_initial_estimation[] = $a * $x  + $b;
            $timestamp_current_day = ($start_of_sprint + $x) * $day;
            $human_dates[] = date('M-d', $timestamp_current_day);
            if (isset($this->data[$start_of_sprint + $x])) {
                $nb = array_sum($this->data[$start_of_sprint + $x]);
            } else {
                if ($x - 1 < count($this->data) - 1) {
                    $nb = $previous;
                } else {
                    $nb = null;
                }
            }
            $data[] = $nb;
            $previous = $nb;
            //$end_of_weeks[] = date('N', $timestamp_current_day) == 7 ? 1 : 0;
        }
        $this->graph->xaxis->SetTickLabels($human_dates);
        /*
        foreach($end_of_weeks as $i => $w) {
            if ($w) {
                $vline = new PlotLine(VERTICAL, $i, "gray9", 1);
                $this->graph->Add($vline);
            }
        }
        */
        foreach($this->data as $d) {
            
        }
        $colors = $this->graph->getThemedColors();
        
        $current = new LinePlot($data);
        $current->SetColor($colors[1].':0.7');
        $current->SetWeight(2);
        $current->SetLegend('Remaining effort');
        $current->mark->SetType(MARK_FILLEDCIRCLE);
        $current->mark->SetColor($colors[1].':0.7');
        $current->mark->SetFillColor($colors[1]);
        $current->mark->SetSize(3);
        $this->graph->Add($current);
       
        //Add "initial" after current so it is on top
        $initial = new LinePlot($data_initial_estimation);
        $initial->SetColor($colors[0].':1.25');
        //$initial->SetStyle('dashed');
        $initial->SetLegend('Ideal Burndown');
        $this->graph->Add($initial);
        
        return $this->graph;
    }
}
?>
