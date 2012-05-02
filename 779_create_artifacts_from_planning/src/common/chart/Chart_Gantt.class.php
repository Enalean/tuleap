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
*/
class Chart_Gantt extends Chart{

    /**
    * Constructor
    * 
    * @param int    $aWidth      Default is 0
    * @param int    $aHeight     Default is 0
    * @param string $aCachedName Default is ""
    * @param int    $aTimeOut    Default is 0
    * @param bool   $aInline     Default is true
    * 
    * @return void
    */
    public function __construct($aWidth = 0, $aHeight = 0, $aCachedName = "", $aTimeOut = 0, $aInline = true) {
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
    
    /**
     * Get the name of the jpgraph class to instantiate
     *
     * @return string
     */
    protected function getGraphClass() {
        return 'GanttGraph';
    }
    
    /**
     * Return the color used to draw a gantt bar when the task is late
     *
     * @return string
     * @see Layout->getGanttLateBarColor
     */
    public function getLateBarColor() {
        return $GLOBALS['HTML']->getGanttLateBarColor();
    }
    
    /**
     * Return the color used to draw a gantt bar when there is an error (mainly in dates)
     *
     * @return string
     * @see Layout->getGanttErrorBarColor
     */
    public function getErrorBarColor() {
        return $GLOBALS['HTML']->getGanttErrorBarColor();
    }
    
    /**
     * Return the color used to draw a gantt bar when the task is green
     *
     * @return string
     * @see Layout->getGanttGreenBarColor
     */
    public function getGreenBarColor() {
        return $GLOBALS['HTML']->getGanttGreenBarColor();
    }
    
    /**
     * Return the color used to draw the "today" vertical line
     *
     * @return string
     * @see Layout->getGanttTodayLineColor
     */
    public function getTodayLineColor() {
        return $GLOBALS['HTML']->getGanttTodayLineColor();
    }
    
}
?>
