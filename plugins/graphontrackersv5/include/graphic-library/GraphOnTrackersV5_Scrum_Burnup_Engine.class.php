<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

class GraphOnTrackersV5_Scrum_Burnup_Engine extends GraphOnTrackersV5_Engine {
    
    public $duration;
    
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
        //
        ksort($this->data);
        $this->duration = 10;
        // Build data for initial estimation
        list($i, $b) = each($this->data);
        $b = array_sum($b);
        $day = 24 * 60 * 60;
        $start_of_sprint = $i;
        $a = - $b / $this->duration;
        
        $data_current_estimation = array();
        $dates = array();
        //$end_of_weeks = array();
        $data = array();
        $previous = 0;
        $previous_est = 0;
        for($x = 0 ; $x <= $this->duration ; ++$x) {
            $a * $x  + $b;
            $d = ($start_of_sprint + $x) * $day;
            $dates[] = date('M-d', $d);
            if (isset($this->data[$start_of_sprint + $x])) {
                $nb = array_sum($this->data[$start_of_sprint + $x]);
                $est = $nb + array_sum($this->remaining[$start_of_sprint + $x]);
            } else {
                $est = $previous_est;
                if ($x - 1 < count($this->data) - 1) {
                    $nb = $previous;
                } else {
                    $nb = null;
                }
            }
            $data[] = $nb;
            $data_current_estimation[] = $est;
            $previous = $nb;
            $previous_est = $est;
            //$end_of_weeks[] = date('N', $d) == 7 ? 1 : 0;
        }
        $this->graph->xaxis->SetTickLabels($dates);
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
        $current->SetLegend('Done');
        $current->mark->SetType(MARK_FILLEDCIRCLE);
        $current->mark->SetColor($colors[1].':0.7');
        $current->mark->SetFillColor($colors[1]);
        $current->mark->SetSize(3);
        $this->graph->Add($current);
        
        //Add "initial" after current so it is on top
        $initial = new LinePlot($data_current_estimation);
        $initial->SetColor($colors[0].':1.25');
        //$initial->SetStyle('dashed');
        $initial->SetLegend('Current estimation');
        $this->graph->Add($initial);
        
        
        return $this->graph;
    }
}
?>
