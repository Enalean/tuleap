<?php
/* 
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Mahmoud MAALEJ, 2006. STMicroelectronics.
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
require_once('GraphOnTrackersV5_Engine.class.php');

class GraphOnTrackersV5_Engine_Bar extends GraphOnTrackersV5_Engine {

    var $title;
    var $description;
    var $field_base;
    var $field_group;
    var $height;
    var $width;
    var $legend;
    var $xaxis;
    
    /**
     * Builds bar chart object
     */
    function buildGraph() {
        require_once('common/chart/Chart.class.php');
        if ($this->width == 0) {
            if (!is_null($this->xaxis)) {
                $this->width = (count($this->data)*count($this->data[0])*25)+(2*150);
            } else {
                $this->width = (count($this->data)*100)+(2*150);
            }
        }

        $right_margin = 50;
        
        $this->graph = new Chart($this->width,$this->height);
        $this->graph->SetScale("textlint");
        $this->graph->title->Set($this->title);
        if (is_null($this->description)) {
            $this->description = "";
        }
        $this->graph->subtitle->Set($this->description);
        
        // x axis formating
        $this->graph->xaxis->SetTickSide(SIDE_DOWN);
        
        $this->graph->xaxis->title->setMargin(60,20,20,20);
        
        if (!is_null($this->xaxis)) {
            sort($this->xaxis);
            $this->graph->xaxis->SetTickLabels(array_values($this->xaxis));
        } else {
            $this->graph->xaxis->SetTickLabels(array_values($this->legend));
        }
        
        $colors = $this->getColors();
        
        if (is_null($this->xaxis)) {
            if ((is_array($this->data)) && (array_sum($this->data)>0)) {
                $this->graph->add($this->getBarPlot($this->data, $colors));
            }
        } else {
            $this->keys = array();
            foreach($this->data as $group => $data) {
                foreach($data as $key => $nb) {
                    $this->keys[$key] = 1;
                }
            }
            $this->keys = array_keys($this->keys);
            sort($this->keys);
            foreach($this->data as $group => $data) {
                foreach($this->keys as $key) {
                    if (!isset($data[$key])) {
                        $this->data[$group][$key] = 0;
                    }
                }
                uksort($this->data[$group], array($this, 'sort'));
            }
            $l = 0; 
            foreach($this->data as $base => $group) {
                $b[$l] = $this->getBarPlot(array_values($group), $colors[$base]);
                $b[$l]->SetLegend($this->legend[$base]);
                $l++;
            }
            $gbplot = new GroupBarPlot($b);
            $this->graph->add($gbplot);
            $right_margin = 150;
        }
        $this->graph->SetMargin(50,$right_margin,$this->graph->getTopMargin() + 40,100);
        return $this->graph;
    }
    function sort($a, $b) {
        return array_search($a, $this->keys) - array_search($b, $this->keys);
    }
     
    
    function getBarPlot($data, $color) {
        $b = new BarPlot($data);
        //parameters hard coded for the moment
        $b->SetAbsWidth(10);
        $b->value->Show(true);
        $b->value->SetColor($this->graph->getMainColor());
        $b->value->SetFormat("%d");
        $b->value->HideZero();
        $b->value->SetMargin(4);
        $b->value->SetFont($this->graph->getFont(), FS_NORMAL, 7);
        
        $b->SetWidth(0.4);
        if(is_array($color)) {
            $b->SetColor('#FFFFFF:0.7');
        }
        else {
           $b->SetColor($color.':0.7');  
        }
        $b->SetFillColor($color);
        // end hard coded parameter
        return $b;
    }
}
?>
