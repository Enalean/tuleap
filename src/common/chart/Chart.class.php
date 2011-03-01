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

require_once($GLOBALS['jpgraph_dir'].'/jpgraph.php');
require_once($GLOBALS['jpgraph_dir'].'/jpgraph_gantt.php');
require_once($GLOBALS['jpgraph_dir'].'/jpgraph_line.php');
require_once($GLOBALS['jpgraph_dir'].'/jpgraph_bar.php');
require_once($GLOBALS['jpgraph_dir'].'/jpgraph_pie.php');
require_once($GLOBALS['jpgraph_dir'].'/jpgraph_date.php'); 

/**
* Chart
* 
* Facade for jpgraph Graph
* 
* @see jpgraph documentation for usage
*/
class Chart {
    
    protected $jpgraph_instance;
    
    /**
    * Constructor
    * 
    * @param int    $aWidth      Default is 600
    * @param int    $aHeight     Default is 400
    * @param string $aCachedName Default is ""
    * @param int    $aTimeOut    Default is 0
    * @param bool   $aInline     Default is true
    * 
    * @return void
    */
    public function __construct($aWidth = 600, $aHeight = 400, $aCachedName = "", $graphType = "Graph", $aTimeOut = 0, $aInline = true) {
        $classname = $this->getGraphClass($graphType);
        $this->jpgraph_instance = new $classname($aWidth,$aHeight,$aCachedName,$aTimeOut,$aInline);
        $this->jpgraph_instance->SetMarginColor($GLOBALS['HTML']->getChartBackgroundColor());
        $this->jpgraph_instance->SetFrame(true, $this->getMainColor(), 1);
        if ($aWidth && $aHeight) {
            $this->jpgraph_instance->img->SetAntiAliasing();
        }
        $this->jpgraph_instance->SetUserFont(
            'dejavu-lgc/DejaVuLGCSans.ttf',  
            'dejavu-lgc/DejaVuLGCSans-Bold.ttf', 
            'dejavu-lgc/DejaVuLGCSans-Oblique.ttf', 
            'dejavu-lgc/DejaVuLGCSans-BoldOblique.ttf'
        );
        
        $this->jpgraph_instance->legend->SetShadow(false);
        $this->jpgraph_instance->legend->SetColor($this->getMainColor());
        $this->jpgraph_instance->legend->SetFillColor($GLOBALS['HTML']->getChartBackgroundColor());
        $this->jpgraph_instance->legend->SetFont($this->getFont(), FS_NORMAL, 8);
        $this->jpgraph_instance->legend->SetVColMargin(5);
        
        $this->jpgraph_instance->title->SetFont($this->getFont(), FS_BOLD, 12);
        $this->jpgraph_instance->title->SetColor($this->getMainColor());
        $this->jpgraph_instance->title->SetMargin(15);
        
        $this->jpgraph_instance->subtitle->SetFont($this->getFont(), FS_NORMAL, 9);
        $this->jpgraph_instance->subtitle->SetColor($this->getMainColor());
        $this->jpgraph_instance->subtitle->SetAlign('left', 'top', 'left');
        $this->jpgraph_instance->subtitle->SetMargin(20);
    }
    
    /**
     * Get the name of the jpgraph class to instantiate
     *
     * @return string
     */
    protected function getGraphClass($graphType) {
        return $graphType;
    }
    
    /**
     * Use magic method to retrieve property of a jpgraph instance
     * /!\ Do not call it directly
     *
     * @param string $name The name of the property
     *
     * @return mixed
     */
    public function __get($name) {
        return $this->jpgraph_instance->$name;
    }
    
    /**
     * Use magic method to set property of a jpgraph instance
     * /!\ Do not call it directly
     *
     * @param string $name  The name of the property
     * @param mixed  $value The new value
     *
     * @return mixed the $value
     */
    public function __set($name, $value) {
        return $this->jpgraph_instance->$name = $value;
    }
    
    /**
     * Use magic method to know if a property of a jpgraph instance exists
     * /!\ Do not call it directly
     *
     * @param string $name The name of the property
     *
     * @return boolean
     */
    public function __isset($name) {
        return isset($this->jpgraph_instance->$name);
    }
    
    /**
     * Use magic method to unset a property of a jpgraph instance
     * /!\ Do not call it directly
     *
     * @param string $name The name of the property
     *
     * @return boolean
     */
    public function __unset($name) {
        unset($this->jpgraph_instance->$name);
    }
    
    /**
     * Use magic method to call a method of a jpgraph instance
     * /!\ Do not call it directly
     *
     * @param string $method The name of the method
     * @param array  $args   The parameters of the method
     *
     * @return mixed
     */
    public function __call($method, $args) {
        try{
            $result = call_user_func_array(array($this->jpgraph_instance, $method), $args);
        }
        catch (Exception $exc) {
            echo '<p class="feedback_error">';
            echo $GLOBALS['Language']->getText('plugin_graphontrackers_error', 'jp_graph', array($this->title->t));
            echo '</p>';
            return false;
        }
        if (!strnatcasecmp($method, 'SetScale')) {
            $this->jpgraph_instance->xaxis->SetColor($this->getMainColor(), $this->getMainColor());
            $this->jpgraph_instance->xaxis->SetFont($this->getFont(), FS_NORMAL, 8);
            $this->jpgraph_instance->xaxis->SetLabelAngle(45);
            $this->jpgraph_instance->xaxis->title->SetFont($this->getFont(), FS_BOLD, 8);
            
            $this->jpgraph_instance->yaxis->SetColor($this->getMainColor(), $this->getMainColor());
            $this->jpgraph_instance->yaxis->SetFont($this->getFont(), FS_NORMAL, 8);
            $this->jpgraph_instance->xaxis->title->SetFont($this->getFont(), FS_BOLD, 8);
            $this->jpgraph_instance->yaxis->title->SetFont($this->getFont(), FS_BOLD, 8);
        }
        return $result;
    }
    
    /**
     * Return the font used by the chart
     *
     * @return int
     */
    public function getFont() {
        return FF_USERFONT;
    }
    
    /**
     * Return the main color used by the chart (axis, text, ...)
     *
     * @return string
     * @see Layout->getChartMainColor
     */
    public function getMainColor() {
        return $GLOBALS['HTML']->getChartMainColor();
    }
    
    /**
     * Return the colors used by the chart to draw data (part of pies, bars, ...)
     *
     * @return string
     * @see Layout->getChartColors
     */
    public function getThemedColors() {
        return $GLOBALS['HTML']->getChartColors();
    }
    
    /**
     * Compute the height of the top margin
     *
     * @return int
     */
    public function getTopMargin() {
        return 20 + $this->jpgraph_instance->title->getTextHeight($this->jpgraph_instance->img) + $this->jpgraph_instance->subtitle->getTextHeight($this->jpgraph_instance->img);
    }
}
?>
