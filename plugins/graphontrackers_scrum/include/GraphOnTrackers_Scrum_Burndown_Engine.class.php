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
class GraphOnTrackers_Scrum_Burndown_Engine extends GraphOnTrackers_Engine {
    
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
        
        // Build data for initial estimation
        list($i, $b) = each($this->data);
        $start_of_sprint = $i;
        $day = 24 * 60 * 60;
        $a = - $b / $this->duration;
        
        $data_initial_estimation = array();
        $dates = array();
        $end_of_weeks = array();
        for($x = 0 ; $x <= $this->duration ; ++$x) {
            $data_initial_estimation[] = $a * $x  + $b;
            $d = $start_of_sprint + $x * $day;
            $dates[] = date('M-d', $d);
            $end_of_weeks[] = date('N', $d) == 7 ? 1 : 0;
        }
        
        $this->graph->xaxis->SetTickLabels($dates);
        
        foreach($end_of_weeks as $i => $w) {
            if ($w) {
                $vline = new PlotLine(VERTICAL, $i, "gray9", 1);
                $this->graph->Add($vline);
            }
        }
        
        $colors = $this->graph->getThemedColors();
        
        $current = new LinePlot(array_values($this->data));
        $current->SetColor($colors[1]);
        $current->SetLegend('Current estimation');
        $current->mark->SetType(MARK_FILLEDCIRCLE);
        $current->mark->SetColor($colors[1].':0.7');
        $current->mark->SetFillColor($colors[1]);
        $current->mark->SetSize(2);
        $this->graph->Add($current);
        
        //Add "initial" after current so it is on top
        $initial = new LinePlot($data_initial_estimation);
        $initial->SetColor($colors[0]);
        $initial->SetLegend('Initial estimation');
        $this->graph->Add($initial);
        
        return $this->graph;
    }
}
?>
