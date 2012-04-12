<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

/**
 * I'm responsible of 
 * - displaying a Burndown chart
 * - prepare data for display
 */
class Tracker_Chart_Burndown {
    /**
     * @var Tracker_Chart_Burndown_Data 
     */
    private $burndown_data;
    
    private $start_date;
    private $duration   = 10;
    
    private $title       = 'Burndown';
    private $description = '';
    private $width       = 640;
    private $height      = 480;
    
    public function __construct(Tracker_Chart_Burndown_Data $burndown_data) {
        $this->burndown_data = $burndown_data;
        $this->start_date = $_REQUEST['SERVER_TIME'] - $this->duration * 24 * 3600;
    }
    
    private function getComputedData($dbdata, $artifact_ids, $start_date, $minday, $maxday) {
        /*$dbdata = array();
        $minday = 0;
        $maxday = 0;
        while ($d = db_fetch_array($res)) {
            if (!isset($dbdata[$d['day']])) {
                $dbdata[$d['day']] = array();
            }
            $dbdata[$d['day']][$d['id']] = $d['value'];
            if ($d['day'] > $maxday)
                $maxday = $d['day'];
            if ($d['day'] < $minday)
                $minday = $d['day'];
        }*/
        $data   = array();
        for ($day = $start_date; $day <= $maxday; $day++) {
            if (!isset($data[$start_date])) {
                $data[$start_date] = array();
            }
        }
        // We assume here that SQL returns effort value order by changeset_id ASC
        // so we only keep the last value (possible to change effort several times a day)

        foreach ($artifact_ids as $aid) {
            for ($day = $minday; $day <= $maxday; $day++) {
                if ($day < $start_date) {
                    if (isset($dbdata[$day][$aid])) {
                        $data[$start_date][$aid] = $dbdata[$day][$aid];
                    }
                } else if ($day == $start_date) {
                    if (isset($dbdata[$day][$aid])) {
                        $data[$day][$aid] = $dbdata[$day][$aid];
                    } else {
                        $data[$day][$aid] = 0;
                    }
                } else {
                    if (isset($dbdata[$day][$aid])) {
                        $data[$day][$aid] = $dbdata[$day][$aid];
                    } else {
                        // No update this day: get value from previous day
                        $data[$day][$aid] = $data[$day - 1][$aid];
                    }
                }
            }
        }
        return $data;
    }
    
    /**
     * @return Chart
     */
    public function buildGraph($remaining_effort, $duration) {
        $this->title  = "Burndown";
        $this->width  = 640;
        $this->height = 480;
        $this->description = "";
        
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
        ksort($remaining_effort);
        
        // Initial estimation line: a * x + b
        // where b is the sum of effort for the first day
        //       x is the number of days (starting from 0 to duration
        //       a is the slope of the line equals -b/duration (burn down goes down)
        
        
        // Build data for initial estimation
        list($first_day, $b) = each($remaining_effort);
        $b = array_sum($b);
        $day = 24 * 60 * 60;
        $start_of_sprint = $first_day;
        $a = - $b / $duration;
        $data_initial_estimation = array();
        $dates = array();
        //$end_of_weeks = array();
        $data = array();
        $previous = $b; // init with sum of effort for the first day
        // for each day
        for ($x = 0 ; $x <= $duration ; ++$x) {
            $data_initial_estimation[] = $a * $x  + $b;
            $timestamp_current_day = ($start_of_sprint + $x) * $day;
            $human_dates[] = date('M-d', $timestamp_current_day);
            if (isset($remaining_effort[$start_of_sprint + $x])) {
                $nb = array_sum($remaining_effort[$start_of_sprint + $x]);
            } else {
                if ($x - 1 < count($remaining_effort) - 1) {
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
        foreach($remaining_effort as $d) {
            
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
