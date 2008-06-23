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
require_once('colorsFactory.class.php');
require_once('GraphOnTrackers_Engine.class.php');

class GraphOnTrackers_Engine_Gantt extends GraphOnTrackers_Engine {
    
    var $graph;
    var $title;
    var $description;
    var $scale;
    var $start;
    var $due;
    var $finish;
    var $progress;
    var $right;
    var $asOfDate;
    var $hint;
    var $links;
    var $summary;
    var $data;
    var $jp_graph_path;
    
    function setTitle($title) {
        $this->title = $title;
    }

    function setDescription($description) {
        $this->description = $description;
    }
        
    function setScale($scale) {
        $this->scale = $scale;
    }
    
    function setStart($start) {
        $this->start = $start;
    }
    
    function setDue($due) {
        $this->due = $due;
    }
    
    function setFinish($finish) {
        $this->finish = $finish;
    }
    
    function setProgress($progress) {
        $this->progress = $progress;
    }
    
    
    function setRight($right) {
        $this->right = $right;
    }
    
    
    function setAsOfDate($asOfDate) {
        $this->asOfDate = $asOfDate;
    }
    
    function setHint($hint) {
        $this->hint = $hint;
    }
    
    function setSummary($summary) {
        $this->summary = $summary;
    }
    
    
    function setLinks($links) {
        $this->links = $links;
    }
    
    function setData($data) {
        $this->data = $data;
    }
      
    
    function formatScale(&$scale_dim) {
        switch ($this->scale){
            case 'day':
                $scale_dim = 1;
                return GANTT_HYEAR | GANTT_HMONTH | GANTT_HWEEK | GANTT_HDAY;
                break;
            case 'week':
                $scale_dim = 3;
                return GANTT_HYEAR | GANTT_HMONTH | GANTT_HWEEK;
                break; 
            case 'month':
                $scale_dim = 7;
                return GANTT_HYEAR | GANTT_HMONTH;
                break;
            case 'year':
                $scale_dim = 14;
                return GANTT_HYEAR;
                break;
            default:
                $scale_dim = 14;
                return GANTT_HYEAR;
                break;
        }        
    }
    
    /**
    * Builds gantt graph
    */
    function buildGraph() {
        require_once('common/chart/Chart_Gantt.class.php');
        $cf = new colorsFactory(); 
              
        $this->graph = new Chart_Gantt($this->width,$this->height,"auto");
        
        if (is_null($this->description)) {
            $this->description = "";
        }
        $this->graph->subtitle->Set($this->description);
        
        if ($this->description) {
            $this->graph->SetMargin(20,20,20 + $this->graph->title->getTextHeight($this->graph->img) + $this->graph->subtitle->getTextHeight($this->graph->img),30);
        }
        
        // title setup
        $this->graph->title->Set($this->title);
                        
        // asOfDate setup 
        if ($this->asOfDate == 0) {
            $dateRep  = date("Y-m-d",strtotime('now'));
            $dateDisp = date("m-d-Y",strtotime('now'));
            $vline = new GanttVLine($dateRep,"Today:".$dateDisp, $cf->getColor_name(10), 1, 'solid');
        } else {
            $dateRep  = date("Y-m-d",$this->asOfDate);
            $dateDisp = date("m-d-Y",$this->asOfDate);
            $vline = new GanttVLine($dateRep,$dateDisp, $cf->getColor_name(10), 1, 'solid');
        }
        $vline->SetDayOffset(0.5);
        $vline->title->SetFont($this->graph->getFont(), FS_NORMAL, 7); 
        $vline->title->setColor($this->graph->getMainColor());
        $this->graph->Add($vline);
        
        
        
        
        //scale setup
        $this->graph->ShowHeaders($this->formatScale(&$scale_dim));
  
        //formating scale

        $this->graph->scale->month->grid->SetColor($this->graph->getMainColor());
        $this->graph->scale->month->grid->Show(true);
        $this->graph->scale->month->SetBackgroundColor($cf->getColor_name(11));
        $this->graph->scale->month->SetFont($this->graph->getFont(), FS_NORMAL, 8);
        
        $this->graph->scale->year->grid->SetColor($this->graph->getMainColor());
        $this->graph->scale->year->grid->Show(true);
        $this->graph->scale->year->SetBackgroundColor($cf->getColor_name(11));
        $this->graph->scale->year->SetFont($this->graph->getFont(), FS_NORMAL, 8);
   
        //add info to gantt graph
        $this->graph->scale->actinfo->SetBackgroundColor($cf->getColor_name(11));
        $this->graph->scale->actinfo->SetFont($this->graph->getFont(), FS_NORMAL, 8);
        
        $this->graph->scale->actinfo->vgrid->SetColor($cf->getColor_name(11));
        $this->graph->scale->actinfo->SetColTitles(array("Summary"),array(20));
        

        $format = "Y-m-d";
        $today  = strtotime('now'); 
        for($i=0;$i<count($this->data);$i++){
            
            // start=0 and due=0 and finish=0
            if (($this->data[$i]['start'] == 0) && ($this->data[$i]['due'] == 0) && ($this->data[$i]['finish'] == 0)) {

                $begin_date = date($format, $today);
                $end_date   = date($format, $today);
                $this->addBar($i, $this->data[$i], false, array(
                    'start'   => date($format, $today),
                    'end'     => date($format, $today),
                    'caption' => "",
                    'height'  => 0.2));
                
            }
            
            // tasks with (start=0 due=0 finished !=0)
            if (($this->data[$i]['start'] == 0) && ($this->data[$i]['due'] == 0) && ($this->data[$i]['finish'] != 0)) {
                
                if (($this->data[$i]['finish'] < $today) && ($this->data[$i]['progress']<1)) {
                    
                    $this->addMilestone($i, $this->data[$i], array('date' => 'finish', 'caption' => ''));
                 
                    $bar = $this->addBar($i, $this->data[$i], false, array(
                            'label'  => "", 
                            'start'  => date($format, $this->data[$i]['finish'] - (60*60*24*5*$scale_dim)), 
                            'end'    => date($format,$today),
                            'height' => 0.2));
                    
                } else {
                    $this->addMilestone($i, $this->data[$i], array('date' => 'finish'));
                 
                    $bar = $this->addBar($i, $this->data[$i], false, array(
                            'label'   => "", 
                            'start'   => date($format, $this->data[$i]['finish'] - (60*60*24*5*$scale_dim)), 
                            'end'     => date($format,$this->data[$i]['finish']-(60*60*24*$scale_dim)),
                            'caption' => "",
                            'height'  => 0.2));
                    $bar->SetColor($cf->getColor_name(12).":0.7");
                    $bar->SetPattern(GANTT_SOLID,$cf->getColor_name(12));
                }
                
            }
            

            
            // task with (start=0 due!=0 finish!=0)
   
            if (($this->data[$i]['start'] == 0) && ($this->data[$i]['due'] != 0) && ($this->data[$i]['finish'] != 0))  {
                
                if ($this->data[$i]['due'] == $this->data[$i]['finish']) {
                    
                    if (($this->data[$i]['finish'] < $today) && ($this->data[$i]['progress']<1)) {
                        
                        $this->addMilestone($i, $this->data[$i]);
                    
                        $begin_date = date($format, $this->data[$i]['finish'] - (60*60*24*5*$scale_dim)); 
                        $bar = $this->addBar($i, $this->data[$i], false, array(
                            'label'   => "", 
                            'start'   => $begin_date, 
                            'end'     => date($format,$today),
                            'caption' => "",
                            'height'  => 0.2));
                        $bar->SetColor($cf->getColor_name(13).":0.7");
                        $bar->SetPattern(GANTT_SOLID,$cf->getColor_name(13),94);
                        
                    } else {
                        
                        $this->addMilestone($i, $this->data[$i]);
                    
                        $bar = $this->addBar($i, $this->data[$i], false, array(
                            'label'   => "", 
                            'start'   => date($format, $this->data[$i]['finish'] - (60*60*24*5*$scale_dim)), 
                            'end'     => date($format,$this->data[$i]['finish']-(60*60*24*$scale_dim)),
                            'caption' => "",
                            'height'  => 0.2));
                        $bar->SetColor($cf->getColor_name(13).":0.7");;
                        $bar->SetPattern(GANTT_SOLID,$cf->getColor_name(13),94);
                        
                    }
                    
                } else if ($this->data[$i]['due'] < $this->data[$i]['finish']) { 

                    if (($this->data[$i]['finish'] < $today) && ($this->data[$i]['progress']<1)) {
                        
                        $a2 = $this->addBar($i, $this->data[$i], false, array(
                            'start'   => date($format,$this->data[$i]['due']+(60*60*24)),
                            'end'     => date($format,$this->data[$i]['finish']-(60*60*24)),
                            'caption' => ""));
                        $a2->SetColor($cf->getColor_name(12).":0.7");;
                        $a2->SetPattern(GANTT_SOLID,$cf->getColor_name(12),98);
                    
                        $this->addMilestone($i, $this->data[$i], array('caption' => ''));
                    
                        $this->addMilestone($i, $this->data[$i], array('date' => 'finish', 'caption' => ''));
                        
                        $bar = $this->addBar($i, $this->data[$i], false, array(
                            'label'  => "", 
                            'start'  => date($format,$this->data[$i]['finish']+(60*60*24)), 
                            'end'    => 'right',
                            'height' => 0.2));
                        $bar->SetColor($cf->getColor_name(13).":0.7");;
                        $bar->SetPattern(GANTT_SOLID,$cf->getColor_name(13),94);
                        
                        
                    } else {
                        
                        $a2 = $this->addBar($i, $this->data[$i], false, array(
                            'start'   => date($format,$this->data[$i]['due']+(60*60*24)),
                            'end'     => date($format,$this->data[$i]['finish']-(60*60*24)),
                            'caption' => ""));
                        $a2->SetColor($cf->getColor_name(12).":0.7");;
                        $a2->SetPattern(GANTT_SOLID,$cf->getColor_name(12),98);
                    
                        $this->addMilestone($i, $this->data[$i], array('caption' => ''));
                    
                        $this->addMilestone($i, $this->data[$i], array('date' => 'finish'));
                        
                    }
                    
                } else if ($this->data[$i]['due'] > $this->data[$i]['finish']) {
                    
                    $bar = $this->addBar($i, $this->data[$i], false, array(
                        'label'  => "", 
                        'start'  => date($format,$this->data[$i]['finish']+(60*60*24)), 
                        'end'    => 'due',
                        'height' => 0.2));
                    $bar->SetColor($cf->getColor_name(13).":0.7");;
                    $bar->SetPattern(GANTT_SOLID,$cf->getColor_name(13),94);
                    
                    $this->addMilestone($i, $this->data[$i], array('date' => 'finish', 'caption' => ''));
                                        
                }
                 
            }

            // task with (start!=0 due!=0 finish!=0)
            
            if (($this->data[$i]['start'] != 0) && ($this->data[$i]['due'] != 0) && ($this->data[$i]['finish'] != 0))  {
                
                if (($this->data[$i]['start'] == $this->data[$i]['due']) && ($this->data[$i]['due'] == $this->data[$i]['finish'])) {
                    
                    $a1 = $this->addBar($i, $this->data[$i], true, array( 'end' => 'due'));
                    
                } else if (($this->data[$i]['start'] == $this->data[$i]['due']) && ($this->data[$i]['due'] < $this->data[$i]['finish'])) {
                    
                    $bar = $this->addBar($i, $this->data[$i], false, array(
                        'start'   => 'due',
                        'height'  => 0.2));
                    $bar->SetColor($cf->getColor_name(12).":0.7");;
                    $bar->SetPattern(GANTT_SOLID,$cf->getColor_name(12),94);
                     
                    $this->addMilestone($i, $this->data[$i], array('date' => 'start', 'caption' => ''));
                                                            
                } else if (($this->data[$i]['start'] == $this->data[$i]['due']) && ($this->data[$i]['due'] > $this->data[$i]['finish'])) {
                    
                    $bar = $this->addBar($i, $this->data[$i], false, array(
                        'start'  => 'finish',
                        'end'    => 'due',
                        'height' => 0.2));
                    $bar->SetPattern(GANTT_SOLID,$cf->getColor_name(14),94);

                    $this->addMilestone($i, $this->data[$i], array('date' => 'finish', 'caption' => ''));
                                        
                } else if (($this->data[$i]['start'] < $this->data[$i]['due']) && ($this->data[$i]['due'] == $this->data[$i]['finish'])) {
                    
                    $a1 = $this->addBar($i, $this->data[$i], true, array('end' => 'due'));
                                                            
                } else if (($this->data[$i]['start'] > $this->data[$i]['due']) && ($this->data[$i]['due'] == $this->data[$i]['finish'])) {
                    
                    if (($this->data[$i]['finish'] < $today) && ($this->data[$i]['progress']<1)) {
                        
                        $a2 = $this->addBar($i, $this->data[$i], false, array(
                            'label' => "",
                            'start' => 'due',
                            'end'   => date($format,$today)));
                        $a2->SetColor($cf->getColor_name(12).":0.7");;
                        $a2->SetPattern(GANTT_SOLID,$cf->getColor_name(12),98);
                    
                        $this->addMilestone($i, $this->data[$i], array('label' => "", 'caption' => ''));

                        $this->addMilestone($i, $this->data[$i], array('date' => 'start', 'caption' => ''));
                        
                    } else { 
                    
                        $a2 = $this->addBar($i, $this->data[$i], false, array(
                            'start' => 'due',
                            'end'   => 'finish'));
                        $a2->SetColor($cf->getColor_name(12).":0.7");;
                        $a2->SetPattern(GANTT_SOLID,$cf->getColor_name(12),98);
                    
                        $this->addMilestone($i, $this->data[$i], array('caption' => ''));

                        $this->addMilestone($i, $this->data[$i], array('date' => 'start', 'caption' => ""));
                        
                    }
                    
                } else if (($this->data[$i]['start'] < $this->data[$i]['due']) && ($this->data[$i]['due'] < $this->data[$i]['finish'])) {
                    
                    if (($this->data[$i]['finish'] < $today) && ($this->data[$i]['progress']<1)) {
                        
                        $a1 = $this->addBar($i, $this->data[$i], true, array(
                            'end'     => 'due',
                            'caption' => ""));

                        $a2 = $this->addBar($i, $this->data[$i], false, array(
                            'start' => date($format,$this->data[$i]['due']+(60*60*24)),
                            'end'     => date($format,$today)));
                        $a2->SetColor($cf->getColor_name(12).":0.7");;
                        $a2->SetPattern(GANTT_SOLID,$cf->getColor_name(12),98);
                        
                    } else {
                        
                        $a1 = $this->addBar($i, $this->data[$i], true, array(
                            'end'     => 'due',
                            'caption' => ""));

                        $a2 = $this->addBar($i, $this->data[$i], false, array(
                            'start' => date($format,$this->data[$i]['due']+(60*60*24)),
                            'end'     => 'finish'));
                        $a2->SetColor($cf->getColor_name(12).":0.7");;
                        $a2->SetPattern(GANTT_SOLID,$cf->getColor_name(12),98);
                        
                    }
                                        
                } else if (($this->data[$i]['start'] < $this->data[$i]['due']) && ($this->data[$i]['due'] > $this->data[$i]['finish'])) {
                    
                    if ($this->data[$i]['start'] == $this->data[$i]['finish']) {
                        
                        $this->addMilestone($i, $this->data[$i], array('date' => 'start', 'caption' => ""));
                        
                        $this->addBar($i, $this->data[$i], false, array(
                                'label' => "",
                                'start' => 'finish',
                                'end'   => 'due',
                                'height' => 0.2));
                        
                    } else if ($this->data[$i]['start'] > $this->data[$i]['finish']) {
                        
                        $this->addMilestone($i, $this->data[$i], array('date' => 'finish', 'caption' => ''));
                        
                        $this->addBar($i, $this->data[$i], false, array(
                                'start' => date($format,$this->data[$i]['finish']+(60*60*24)),
                                'end'   => 'due',
                                'height' => 0.2));
                        
                        $this->addMilestone($i, $this->data[$i], array('date' => 'start', 'caption' => ""));
                                                
                    } else if ($this->data[$i]['start'] < $this->data[$i]['finish']) {
                        
                        if (($this->data[$i]['finish'] < $today) && ($this->data[$i]['progress']<1)) {

                            $bar = $this->addBar($i, $this->data[$i], true, array(
                                'start' => date($format,$this->data[$i]['finish']+(60*60*24)),
                                'end'   => 'due',
                                'height' => 0.2));
                            $bar->SetPattern(GANTT_VLINE,$cf->getColor_name(14),92);
                            $bar->SetFillColor($cf->getColor_name(15));
                            
                            $this->addBar($i, $this->data[$i], true, array('end' => date($format,$today), 'caption' => ""));
                            
                        } else {
                        
                            $this->addBar($i, $this->data[$i], true, array('caption' => ""));

                            $bar = $this->addBar($i, $this->data[$i], false, array(
                                'start' => date($format,$this->data[$i]['finish']+(60*60*24)),
                                'end' => 'due',
                                'height' => 0.2));
                            $bar->SetPattern(GANTT_VLINE,$cf->getColor_name(14),92);
                            $bar->SetFillColor($cf->getColor_name(15));
                        }
                        
                    }
                    
                } else if (($this->data[$i]['start'] > $this->data[$i]['due']) && ($this->data[$i]['due'] < $this->data[$i]['finish'])) {
                    
                    if ($this->data[$i]['start'] == $this->data[$i]['finish']) {
                        $this->addMilestone($i, $this->data[$i], array('date' => 'finish', 'caption' => ''));
                    } else if ($this->data[$i]['start'] > $this->data[$i]['finish']) {
                        if (($this->data[$i]['finish'] < $today) && ($this->data[$i]['progress']<1)) {
                            $bar = $this->addBar($i, $this->data[$i], false, array(
                                'label'   => "",
                                'start'   => date($format,$this->data[$i]['due']+(60*60*24)),
                                'end'     => date($format,$today),
                                'caption' => "",
                                'height'  => 0.2));
                            $bar->SetColor($cf->getColor_name(12).":0.7");;
                            $bar->SetPattern(GANTT_SOLID,$cf->getColor_name(12),94);
                            
                            $this->addMilestone($i, $this->data[$i], array('caption' => ''));
                            
                            $this->addMilestone($i, $this->data[$i], array('date' => 'finish', 'caption' => ''));
                            
                            $this->addMilestone($i, $this->data[$i], array('date' => 'start'));
                                                        
                        } else {
                            $bar = $this->addBar($i, $this->data[$i], false, array(
                                'label' => "",
                                'start' => date($format,$this->data[$i]['due']+(60*60*24)),
                                'end' => date($format,$this->data[$i]['finish']-(60*60*24)),
                                'caption' => "",
                                'height' => 0.2));
                            $bar->SetColor($cf->getColor_name(12).":0.7");;
                            $bar->SetPattern(GANTT_SOLID,$cf->getColor_name(12),94);
                            
                            $bar1 = $this->addBar($i, $this->data[$i], false, array(
                                'label' => "",
                                'start' => date($format,$this->data[$i]['finish']+(60*60*24)),
                                'end' => date($format,$this->data[$i]['start']-(60*60*24)),
                                'caption' => "",
                                'height' => 0.2));
                            $bar1->SetColor($cf->getColor_name(12).":0.7");;
                            $bar1->SetPattern(GANTT_SOLID,$cf->getColor_name(12),94);
                            
                            $this->addMilestone($i, $this->data[$i], array('caption' => ''));
                            
                            $this->addMilestone($i, $this->data[$i], array('date' => 'finish', 'caption' => ''));
                            
                            $this->addMilestone($i, $this->data[$i], array('date' => 'start'));
                            
                        }
                        
                    } else if ($this->data[$i]['start'] < $this->data[$i]['finish']) {
                        $bar = $this->addBar($i, $this->data[$i], false, array(
                            'start' => 'due',
                            'end' => 'start',
                            'caption' => "",
                            'height' => 0.2));
                        $bar->SetColor($cf->getColor_name(12).":0.7");;
                        $bar->SetPattern(GANTT_SOLID,$cf->getColor_name(12),94);
                        
                        $this->addMilestone($i, $this->data[$i], array('caption' => ''));
                        
                        $this->addMilestone($i, $this->data[$i], array('date' => 'start', 'caption' => ''));
                        
                    }
                
                } else if (($this->data[$i]['start'] > $this->data[$i]['due']) && ($this->data[$i]['due'] > $this->data[$i]['finish'])) {
                    $this->addMilestone($i, $this->data[$i], array('date' => 'start', 'caption' => ''));
                        
                    $this->addBar($i, $this->data[$i], true, array(
                        'start' => date($format,$this->data[$i]['finish']+(60*60*24)),
                        'end' => date($format,$this->data[$i]['due']-(60*60*24)),
                        'caption' => ""));
                        
                    $this->addMilestone($i, $this->data[$i], array('caption' => ''));
                       
                    $bar = $this->addBar($i, $this->data[$i], false, array(
                        'start' => date($format,$this->data[$i]['due']+(60*60*24)),
                        'end' => date($format,$this->data[$i]['start']-(60*60*24))));
                    $bar->SetColor($cf->getColor_name(12).":0.7");;
                    $bar->SetPattern(GANTT_SOLID,$cf->getColor_name(12),98);
                    
                    $this->addMilestone($i, $this->data[$i], array('date' => 'finish', 'caption' => ''));

                }
                
            }
            
            // task with (start!=0 due!=0 finish=0)
            if (($this->data[$i]['start'] != 0) && ($this->data[$i]['due'] != 0) && ($this->data[$i]['finish'] == 0))  {
                if ($this->data[$i]['start'] == $this->data[$i]['due'] ) {
                    $this->addBar($i, $this->data[$i], true, array('end' => date($format,$this->data[$i]['due'])));
                } else if ($this->data[$i]['start'] > $this->data[$i]['due'] ) {

                    $this->addBar($i, $this->data[$i], false, array(
                        'label'   => "",
                        'start'   => date($format,$this->data[$i]['due']+(60*60*24)), 
                        'end'     => date($format,$this->data[$i]['start']-(60*60*24)),
                        'caption' => "",
                        'height'  => 0.2));
                    
                    $this->addMilestone($i, $this->data[$i], array('date' => 'start'));

                    $this->addMilestone($i, $this->data[$i], array('caption' => ''));
                    
                } else if ($this->data[$i]['start'] < $this->data[$i]['due'] ) {
                    $this->addBar($i, $this->data[$i], true, array('end' => date($format,$this->data[$i]['due'])));
                }
                
            }
            
            // task with (start!=0 due=0 finish=0)
            if (($this->data[$i]['start'] != 0) && ($this->data[$i]['due'] == 0) && ($this->data[$i]['finish'] == 0))  {
                $end_date   = date($format,$this->data[$i]['start']+(60*60*24*5*$scale_dim));
                $start_date = date($format,$this->data[$i]['start']+(60*60*24));
                $this->addBar($i, $this->data[$i], false, array(
                    'label' => "", 
                    'start' => $start_date, 
                    'end' => $end_date,
                    'height' => 0.2));
                    
                $this->addMilestone($i, $this->data[$i], array('date' => 'start', 'caption' => ''));
            }
            
            // task with (start=0 due=!0 finish=0)
            if (($this->data[$i]['start'] == 0) && ($this->data[$i]['due'] != 0) && ($this->data[$i]['finish'] == 0))  {
                $this->addMilestone($i, $this->data[$i]);
            }
            
            // task with (start!=0 due=0 finish!=0)
            if (($this->data[$i]['start'] != 0) && ($this->data[$i]['due'] == 0) && ($this->data[$i]['finish'] != 0))  {
                if ($this->data[$i]['start'] > $this->data[$i]['finish']) {
                    $aStart = 'finish';
                    $aEnd   = 'start';
                } else {
                    $aStart = 'start';
                    $aEnd   = 'finish';
                }
                $this->addBar($i, $this->data[$i], true, array('start' => $aStart, 'end' => $aEnd));
            }
        }
    }
    
    protected function addBar($pos, $data, $progress, $params = array()) {
        $format = "Y-m-d";
        //start date
        if (isset($params['start'])) {
            if (in_array($params['start'], array('start', 'due', 'finish'))) {
                $aStart = date($format, $data[$params['start']]);
            } else {
                $aStart = $params['start'];
            }
        } else {
            $aStart = date($format, $data['start']);
        }
        //end date
        if (isset($params['end'])) {
            if (in_array($params['end'], array('start', 'due', 'finish'))) {
                $aEnd = date($format, $data[$params['end']]);
            } else {
                $aEnd = $params['end'];
            }
        } else {
            $aEnd = date($format, $data['finish']);
        }
        
        $aLabel        = isset($params['label']) ? $params['label'] : array(html_entity_decode($data['summary']));
        $aCaption      = isset($params['caption']) ? $params['caption'] : $data['right'];
        $aHeightFactor = isset($params['height']) ? $params['height'] : 0.6; //default jpgraph value
        
        $bar = new Chart_GanttBar($pos, $aLabel, $aStart, $aEnd, $aCaption, $aHeightFactor);
        if ($progress) {
            $bar->progress->Set($data['progress']);
        }
        $bar->SetCSIM($data['links'], $data['hint']);
        $this->graph->Add($bar);
        return $bar;
    }
    
    protected function addMilestone($pos, $data, $params = array()) {
        $format = "Y-m-d";
        $aLabel   = isset($params['label']) ? $params['label'] : array(html_entity_decode($data['summary']));
        if (isset($params['date'])) {
            if (in_array($params['date'], array('start', 'due', 'finish'))) {
                $aDate = date($format, $data[$params['date']]);
            } else {
                $aDate = $params['date'];
            }
        } else {
            $aDate = date($format, $data['due']);
        }
        $aCaption = isset($params['caption']) ? $params['caption'] : $data['right'];
        
        $milestone = new Chart_GanttMilestone($pos, $aLabel, $aDate, $aCaption);
        
        $milestone->SetCSIM($data['links'], $data['hint']);
        $this->graph->Add($milestone);
        return $milestone;
    }
}

?>
