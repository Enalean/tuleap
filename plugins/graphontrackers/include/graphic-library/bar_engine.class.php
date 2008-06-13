<?php
/*
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Mahmoud MAALEJ, 2006. STMicroelectronics.
 *
 * This file is a part of CodeX.
 *
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class bar_engine {

    var $graph;
    var $title;
    var $description;
    var $field_base;
    var $field_group;
    var $height;
    var $width;
    var $data;
    var $legend;
    var $xaxis;
    var $cf;
    var $jp_graph_path;
    
    /**
	* class constructor
	*
	* 	@return null
    */
    
    function bar_engine() {
    	require_once('colorsFactory.class.php');
        $this->jp_graph_path = $GLOBALS['jpgraph_dir'];
        $this->cf = new colorsFactory();       
    }
    
    function Valid_datas(){
    	if((is_array($this->data)) && (array_sum($this->data)>0)){
    		return true;
    	}else{
    		
			echo " <p class='feedback_info'>".$GLOBALS['Language']->getText('plugin_graphontrackers_engine','no_datas',array($this->title))."</p>";				
    		return false;
    	}
    }
    
    /**
	* function to build bar chart object (JpGraph object)
	*   
	* 	@return Bar graph object (JpGraph object)
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
        $caption = new Text($this->description,30,40);
        $caption->setFont($this->graph->getFont(), FS_NORMAL, 9);
        $caption->setColor($this->graph->getMainColor());
        $this->graph->AddText($caption); 
        
        
        
        // x axis formating
        $this->graph->xaxis->SetTickSide(SIDE_DOWN);
        
        $this->graph->xaxis->title->setMargin(60,20,20,20);
        $this->graph->xaxis->title->setColor($this->cf->getColor_name(15));
        
        if (!is_null($this->xaxis)) {
            $this->graph->xaxis->SetTickLabels($this->xaxis);
        } else {
            $this->graph->xaxis->SetTickLabels($this->legend);
        }

        if (is_null($this->xaxis)) {
            if ((is_array($this->data)) && (array_sum($this->data)>0)) {
                $b = new BarPlot($this->data);
                //parameters hard coded for the moment
                $b->SetAbsWidth(10);
                $b->value->HideZero();
                $b->value->Show(true);
                $b->value->SetColor($this->graph->getMainColor());
                $b->value->SetFormat("%d");
                $b->value->HideZero();
                $b->value->SetMargin(2);
                
                $b->SetWidth(0.4);
                $b->SetColor($this->cf->getColor_name(0).':0.7');
                $b->SetFillColor($this->cf->getColor_name(0));
                // end hard coded parameter
                $this->graph->add($b);
            }
                
        } else {
            $l = 0; 
            for ($i=0;$i<count($this->data);$i++) {
                if ((is_array($this->data[$i])) && (array_sum($this->data[$i])>0)) {
                    $b[$l] = new BarPlot($this->data[$i]);
                    //parameters hard coded for the moment
                    $b[$l]->SetAbsWidth(10); 
                    $b[$l]->value->Show(true);
                    $b[$l]->value->SetColor($this->graph->getMainColor());
                    $b[$l]->value->SetFormat("%d");
                    $b[$l]->value->HideZero();
                    $b[$l]->value->SetMargin(2);   
                    $b[$l]->SetLegend($this->legend[$i]);
                    $b[$l]->SetWidth(0.4);
                    $b[$l]->SetColor($this->cf->getColor_name($l).':0.7');
                    $b[$l]->SetFillColor($this->cf->getColor_name($l));
                    $l++;
                    // end hard coded parameter
                }
                
            }
            if (count($this->data)>0) {
                $gbplot = new GroupBarPlot($b);
                $this->graph->add($gbplot);
                $right_margin = 150;
            }
        }
        $this->graph->img->SetMargin(50,$right_margin,100,100);
        return $this->graph;
    }
    
    /**
	* function to inverse matrix
	*   
	* 	@return matrix: inversed matrix
    */

    function invMatrix($matrix) {
        for($i=0;$i<count($matrix);$i++) {
            for($j=0;$j<count($matrix[$i]);$j++) {
               $inversed_matrix[$j][$i] = $matrix[$i][$j];
            }
        }
        return $inversed_matrix;
    }
}
?>
