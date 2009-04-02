<?php
/*
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
require_once('Chart.class.php');
require_once('Chart_GanttBar.class.php');
require_once('Chart_GanttMilestone.class.php');
require_once($GLOBALS['jpgraph_dir'].'/jpgraph_gantt.php');

/**
* PieChart
* 
* Facade for jpgraph GanttGraph
* 
* @see jpgraph documentation for usage
* 
* Copyright (c) Xerox Corporation, CodeX Team, 2008. All rights reserved
*
* @author  N. Terray
*/
class Chart_Gantt extends Chart{

    /**
    * Constructor
    */
    public function __construct($aWidth=0,$aHeight=0,$aCachedName="",$aTimeOut=0,$aInline=true) {
        parent::__construct($aWidth, $aHeight, $aCachedName, $aTimeOut, $aInline);
        
        $header_color = $GLOBALS['HTML']->getGanttHeaderColor();
        
        $this->scale->year->grid->SetColor($this->getMainColor());
        $this->scale->year->grid->Show(true);
        $this->scale->year->SetBackgroundColor($header_color);
        $this->scale->year->SetFont($this->getFont(), FS_NORMAL, 8);
        
        $this->scale->month->grid->SetColor($this->getMainColor());
        $this->scale->month->grid->Show(true);
        $this->scale->month->SetBackgroundColor($header_color);
        $this->scale->month->SetFont($this->getFont(), FS_NORMAL, 8);
        
        $this->scale->week->grid->SetColor($this->getMainColor());
        $this->scale->week->SetFont($this->getFont(), FS_NORMAL, 8);
        
        $this->scale->day->grid->SetColor($this->getMainColor());
        $this->scale->day->SetFont($this->getFont(), FS_NORMAL, 6);
        
        $this->scale->actinfo->SetBackgroundColor($header_color);
        $this->scale->actinfo->SetFont($this->getFont(), FS_NORMAL, 8);
        
        $this->scale->actinfo->vgrid->SetColor($header_color);
    }
    
    protected function _getGraphClass() {
        return 'GanttGraph';
    }
    
    public function getLateBarColor() {
        return $GLOBALS['HTML']->getGanttLateBarColor();
    }
    
    public function getErrorBarColor() {
        return $GLOBALS['HTML']->getGanttErrorBarColor();
    }
    
    public function getGreenBarColor() {
        return $GLOBALS['HTML']->getGanttGreenBarColor();
    }
    
    public function getTodayLineColor() {
        return $GLOBALS['HTML']->getGanttTodayLineColor();
    }
    
}
?>
