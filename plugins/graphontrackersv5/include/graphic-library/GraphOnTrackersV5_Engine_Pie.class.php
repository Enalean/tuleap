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
require_once('common/chart/Chart_Pie.class.php');
require_once('GraphOnTrackersV5_Engine.class.php');
require_once('common/layout/ColorHelper.class.php');

class GraphOnTrackersV5_Engine_Pie extends GraphOnTrackersV5_Engine {

    var $title;
    var $field_base;
    var $height;
    var $width;
    var $size_pie;
    var $legend;
    
    
    function validData(){
        if ((is_array($this->data)) && (array_sum($this->data) > 0)){
            return true;
        }else{
            echo " <p class='feedback_info'>".$GLOBALS['Language']->getText('plugin_graphontrackersv5_engine','no_datas',array($this->title))."</p>";                
            return false;
        }
    }
    
    private function convertColors() {
        if ($this->graph) {
            $availableColors = $this->graph->getThemedColors();
            $length = count($this->colors);
            for ($i = 0 ; $i < $length ; $i++ ) {
                //We fill the blanks
                if ($this->colors[$i][0] == NULL || $this->colors[$i][1] == NULL || $this->colors[$i][2] == NULL )
                    $this->colors[$i] = $availableColors[$i];
                else {
                    //We convert RGB array to hex
                    $this->colors[$i]= ColorHelper::RBGToHexa($this->colors[$i][0], $this->colors[$i][1], $this->colors[$i][2]);
                }
            }
        }
    }
    
    /**
     * Builds pie graph
     */
    function buildGraph() {
        $this->graph = new Chart_Pie($this->width,$this->height);

        // title setup
        $this->graph->title->Set($this->title);
        
        if (is_null($this->description)) {
            $this->description = "";
        }
        $this->graph->subtitle->Set($this->description);
        
        $this->convertColors();
        
        if ((is_array($this->data)) && (array_sum($this->data)>0)) {
            $p = new PiePlot($this->data);
            
            $p->setSliceColors($this->colors);
            
            $p->SetCenter(0.4,0.6);
            $p->SetLegends($this->legend);
                      
                
            $p->value->HideZero();
            $p->value->SetFont($this->graph->getFont(), FS_NORMAL, 8);
            $p->value->SetColor($this->graph->getMainColor());
            $p->value->SetMargin(0);
            
            $this->graph->Add($p);
        }          
        return $this->graph;
    }
}
?>
