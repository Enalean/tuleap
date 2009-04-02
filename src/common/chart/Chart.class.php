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

require_once($GLOBALS['jpgraph_dir'].'/jpgraph.php');
require_once($GLOBALS['jpgraph_dir'].'/jpgraph_gantt.php');
require_once($GLOBALS['jpgraph_dir'].'/jpgraph_line.php');
require_once($GLOBALS['jpgraph_dir'].'/jpgraph_bar.php');
require_once($GLOBALS['jpgraph_dir'].'/jpgraph_date.php'); 

/**
* Chart
* 
* Facade for jpgraph Graph
* 
* @see jpgraph documentation for usage
* 
* Copyright (c) Xerox Corporation, CodeX Team, 2008. All rights reserved
*
* @author  N. Terray
*/
class Chart {
    
    protected $_jpgraph_instance;
    protected $_themed_colors;
    
    /**
    * Constructor
    */
    public function __construct($aWidth=600,$aHeight=400,$aCachedName="",$aTimeOut=0,$aInline=true) {
        $classname = $this->_getGraphClass();
        $this->_jpgraph_instance = new $classname($aWidth,$aHeight,$aCachedName,$aTimeOut,$aInline);
        $this->_jpgraph_instance->SetMarginColor($GLOBALS['HTML']->getChartBackgroundColor());
        $this->_jpgraph_instance->SetFrame(true, $this->getMainColor(), 1);
        if ($aWidth && $aHeight) {
            $this->_jpgraph_instance->img->SetAntiAliasing();
        }
        
        $this->_jpgraph_instance->legend->SetShadow(false);
        $this->_jpgraph_instance->legend->SetColor($this->getMainColor());
        $this->_jpgraph_instance->legend->SetFillColor($GLOBALS['HTML']->getChartBackgroundColor());
        $this->_jpgraph_instance->legend->SetFont(FF_DEJAVU,FS_NORMAL,8);
        $this->_jpgraph_instance->legend->SetVColMargin(5);
        
        $this->_jpgraph_instance->title->SetFont(FF_DEJAVU,FS_BOLD,12);
        $this->_jpgraph_instance->title->SetColor($this->getMainColor());
        $this->_jpgraph_instance->title->SetMargin(15);
        
        $this->_jpgraph_instance->subtitle->SetFont($this->getFont(), FS_NORMAL,9);
        $this->_jpgraph_instance->subtitle->SetColor($this->getMainColor());
        $this->_jpgraph_instance->subtitle->SetAlign('left', 'top', 'left');
        $this->_jpgraph_instance->subtitle->SetMargin(20);
    }
        
    protected function _getGraphClass() {
        return 'Graph';
    }

    public function __get($name) {
        return $this->_jpgraph_instance->$name;
    }
    
    public function __set($name, $value) {
        return $this->_jpgraph_instance->$name = $value;
    }
    
    public function __isset($name) {
        return isset($this->_jpgraph_instance->$name);
    }
    
    public function __unset($name) {
        unset($this->_jpgraph_instance->$name);
    }
    
    public function __call($method, $args) {
        try{
            $result = call_user_func_array(array($this->_jpgraph_instance, $method), $args);
        }
        catch (Exception $exc) {
            echo '<p class="feedback_error">';
            echo $GLOBALS['Language']->getText('plugin_graphontrackers_error','jp_graph',array($this->title->t));
            echo '</p>';
            return false;
        }
        if (!strnatcasecmp($method, 'SetScale')) {
            $this->_jpgraph_instance->xaxis->SetColor($this->getMainColor(), $this->getMainColor());
            $this->_jpgraph_instance->xaxis->SetFont(FF_DEJAVU,FS_NORMAL,8);
            $this->_jpgraph_instance->xaxis->SetLabelAngle(45);
            $this->_jpgraph_instance->xaxis->title->SetFont(FF_DEJAVU,FS_BOLD,8);
            
            $this->_jpgraph_instance->yaxis->SetColor($this->getMainColor(), $this->getMainColor());
            $this->_jpgraph_instance->yaxis->SetFont(FF_DEJAVU,FS_NORMAL,8);
            $this->_jpgraph_instance->xaxis->title->SetFont(FF_DEJAVU,FS_BOLD,8);
            $this->_jpgraph_instance->yaxis->title->SetFont(FF_DEJAVU,FS_BOLD,8);
        }
        return $result;
    }
    
    public function getFont() {
        return FF_DEJAVU;
    }
    
    public function getMainColor() {
        return $GLOBALS['HTML']->getChartMainColor();
    }
    
    public function getThemedColors() {
        return $GLOBALS['HTML']->getChartColors();
    }
    
    public function getTopMargin() {
        return 20 + $this->_jpgraph_instance->title->getTextHeight($this->_jpgraph_instance->img) + $this->_jpgraph_instance->subtitle->getTextHeight($this->_jpgraph_instance->img);
    }
}
?>
