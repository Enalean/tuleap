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

class GraphOnTrackersV5_Engine_CumulativeFlow extends GraphOnTrackersV5_Engine {

    public $scale;
    public $stop_date;
    public $start_date;
    public $color_set;
    public $keys;
    public $nbOpt;

    const WIDTH_PER_POINT = 100;
    const MARGIN = 200;
    function validData(){

        if ($this->start_date && $this->start_date > 0 && $this->hasData()){
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

        if ($this->width == 0) {
            $this->width = (count($this->data)* GraphOnTrackersV5_Engine_CumulativeFlow::WIDTH_PER_POINT)+(GraphOnTrackersV5_Engine_CumulativeFlow::MARGIN);
        }

        foreach ($this->data as $date => $label)
            $dates[] = date('M-d', $date);

        $this->graph = new Chart($this->width,$this->height);
        $colors = $this->getColors();
        $this->graph->SetScale("datlin");
        $this->graph->title->Set($this->title);
        $this->graph->xaxis->SetTickLabels($dates);

        if (is_null($this->description)) {
            $this->description = "";
        }
        $this->graph->subtitle->Set($this->description);
        $this->keys = array_keys($this->data[$this->start_date]);
        $this->nbOpt = count($this->keys);
        $this->stackDataCount();
        $this->graph->ygrid->SetFill(true,'#F3FFFF@0.5','#FFFFFF@0.5');

        for($i = $this->nbOpt-1; $i >= 0; $i--) {
            $lineData = array();
            foreach ($this->data as $data => $row) {
                $lineData[] = $row[$this->keys[$i]];
            }
            $line = new LinePlot($lineData);
            $line->SetFillColor($colors[$this->keys[$i]]);
            $line->SetColor('#000');
            $line->SetLegend($this->keys[$i]);
            $this->graph->Add($line);
        }
        return $this->graph;
    }

    function hasData() {
        $sumData = 0;
        foreach ($this->data as $row) {
            $sumData += array_sum($row);
        }
        return (count(reset($this->data)) > 0) && $sumData > 0;
    }

    /**
     *
     * Stack the counts to see each line on top of each other.
     */
    private function stackDataCount() {
        foreach ($this->data as $date => $row) {
            for($i = 1; $i < $this->nbOpt ;$i++) {
                if($this->data[$date][$this->keys[$i]]!==null){
                    $this->data[$date][$this->keys[$i]] += $this->data[$date][$this->keys[$i-1]];
                }
            }
        }
    }
}
?>
