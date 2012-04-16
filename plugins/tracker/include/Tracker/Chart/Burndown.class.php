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

require_once 'Data/IProvideDataForBurndownChart.class.php';

/**
 * I'm responsible of 
 * - displaying a Burndown chart
 * - prepare data for display
 */
class Tracker_Chart_Burndown {

    const SECONDS_IN_A_DAY = 86400;

    /**
     * @var Tracker_Chart_Data_IProvideDataForBurndownChart
     */
    private $burndown_data;
    private $start_date;
    private $duration = 10;
    private $title = 'Burndown';
    private $description = '';
    private $width = 640;
    private $height = 480;

    private $graph_data_ideal_burndown   = array();
    private $graph_data_human_dates      = array();
    private $graph_data_remaining_effort = array();
    
    public function __construct(Tracker_Chart_Data_IProvideDataForBurndownChart $burndown_data) {
        $this->burndown_data = $burndown_data;
        $this->start_date = $_SERVER['REQUEST_TIME'] - $this->duration * 24 * 3600;
    }

    public function setStartDate($start_date) {
        $this->start_date = round($start_date / self::SECONDS_IN_A_DAY);
    }
    
    public function setStartDateInDays($start_date) {
        $this->start_date = $start_date;
    }

    public function setDuration($duration) {
        $this->duration = $duration;
    }

    public function setTitle($title) {
        $this->title = $title;
    }

    public function setDescription($description) {
        $this->description = $description;
    }

    public function setWidth($width) {
        $this->width = $width;
    }

    public function setHeight($height) {
        $this->height = $height;
    }

    public function getGraphDataHumanDates() {
        return $this->graph_data_human_dates;
    }

    public function getGraphDataRemainingEffort() {
        return $this->graph_data_remaining_effort;
    }

    public function getGraphDataIdealBurndown() {
        return $this->graph_data_ideal_burndown;
    }

    public function getComputedData() {
        $dbdata       = $this->burndown_data->getRemainingEffort();
        $artifact_ids = $this->burndown_data->getArtifactIds();
        $minday       = $this->burndown_data->getMinDay();
        $maxday       = $this->burndown_data->getMaxDay();
        $data         = array_fill($this->start_date, $maxday - $this->start_date, array());

        // We assume here that SQL returns effort value order by changeset_id ASC
        // so we only keep the last value (possible to change effort several times a day)

        foreach ($artifact_ids as $aid) {
            for ($day = $minday; $day <= $maxday; $day++) {
                $dbdata_of_the_day = isset($dbdata[$day][$aid]) ? $dbdata[$day][$aid] : 0;
                if ($day <= $this->start_date) {
                    $current_day = $this->start_date;   
                } else {
                    $current_day = $day;
                    if ($dbdata_of_the_day == 0 && isset($data[$day - 1][$aid])) {
                        $dbdata_of_the_day = $data[$day - 1][$aid];
                    }
                }
                $data[$current_day][$aid] = $dbdata_of_the_day;
            }
        }
        return $data;
    }
    
    /**
     * Used with function array_reduce to have the max remaining effort
     * 
     * @param int   $result result of previous call or initial value (null by default)
     * @param array $item   the current item of the array we're reducing
     * @return int  the higher sum of array already iterated items
     */
    protected function reduceToMaxSum($result, $item) {
        $sum = is_array($item) ? array_sum($item) : 0;
        return max($result, $sum);
    }
    
    public function prepareDataForGraph(array $remaining_effort) {
        // order this->data by date asc
        ksort($remaining_effort);

        // Ideal burndown line:  a * x + b
        // where b is the sum of effort for the first day
        //       x is the number of days (starting from 0 to duration
        //       a is the slope of the line equals -b/duration (burn down goes down)
        // 
        // Final formula: slope * day_num + first_day_effort
        // 
        // Build data for initial estimation
        list($start_of_sprint, $efforts) = each($remaining_effort);
        $max_effort_found = false;
        $max_effort       = array_reduce($remaining_effort, array($this, 'reduceToMaxSum'));
        $slope            = - $max_effort / $this->duration;
        $previous_effort  = is_array($efforts) ? array_sum($efforts) : 0; // init with sum of effort for the first day
        
        // for each day
        for ($day_num = 0; $day_num <= $this->duration; ++$day_num) {
            $effort = null;
            $current_day = $start_of_sprint + $day_num;
            
            $this->graph_data_ideal_burndown[] = $slope * $day_num + $max_effort;
            $this->graph_data_human_dates[]    = date('M-d', $current_day * self::SECONDS_IN_A_DAY);
            
            if (isset($remaining_effort[$current_day])) {
                $effort = array_sum($remaining_effort[$current_day]);
                if ($max_effort_found == false) {
                    $max_effort_found = $effort == $max_effort;
                    $effort = $max_effort;
                }
            } elseif ($day_num < count($remaining_effort)) {
                $effort = $previous_effort;
            }
            $this->graph_data_remaining_effort[] = $effort;
            $previous_effort = $effort;
        }
    }

    /**
     * @return Chart
     */
    public function buildGraph() {
        $this->prepareDataForGraph($this->getComputedData());
 
        $graph = new Chart($this->width, $this->height);
        $graph->SetScale("datlin");

        $graph->title->Set($this->title);
        $graph->subtitle->Set($this->description);

        $colors = $graph->getThemedColors();

        $graph->xaxis->SetTickLabels($this->graph_data_human_dates);
        
        $remaining_effort = new LinePlot($this->graph_data_remaining_effort);
        $remaining_effort->SetColor($colors[1] . ':0.7');
        $remaining_effort->SetWeight(2);
        $remaining_effort->SetLegend('Remaining effort');
        $remaining_effort->mark->SetType(MARK_FILLEDCIRCLE);
        $remaining_effort->mark->SetColor($colors[1] . ':0.7');
        $remaining_effort->mark->SetFillColor($colors[1]);
        $remaining_effort->mark->SetSize(3);
        $graph->Add($remaining_effort);

        $ideal_burndown = new LinePlot($this->graph_data_ideal_burndown);
        $ideal_burndown->SetColor($colors[0] . ':1.25');
        $ideal_burndown->SetLegend('Ideal Burndown');
        $graph->Add($ideal_burndown);

        return $graph;
    }
    
    public function display() {
        $this->buildGraph()->stroke();
    }

}

?>
