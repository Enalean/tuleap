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

/**
* Chart_GanttBar
* 
* Facade for jpgraph GanttBar
* 
* @see jpgraph documentation for usage
*/
class Chart_GanttBar {
    
    protected $_jpgraph_instance;
    
    /**
    * Constructor
    */
    public function __construct($aPos,$aLabel,$aStart,$aEnd,$aCaption="",$aHeightFactor=0.6) {
        $this->_jpgraph_instance = new GanttBar($aPos,$aLabel,$aStart,$aEnd,$aCaption,$aHeightFactor);
        $color      = $GLOBALS['HTML']->getGanttBarColor();
        $color_dark = $color .':0.65';
        $this->_jpgraph_instance->progress->SetPattern(BAND_SOLID, $color_dark);
        $this->_jpgraph_instance->setColor($color_dark);
        $this->_jpgraph_instance->setPattern(GANTT_SOLID, $color);
        $this->_jpgraph_instance->title->setColor($GLOBALS['HTML']->getChartMainColor());
        $this->_jpgraph_instance->title->setFont($this->getFont(), FS_NORMAL, 8);
        $this->_jpgraph_instance->caption->setColor($GLOBALS['HTML']->getChartMainColor());
        $this->_jpgraph_instance->caption->setFont($this->getFont(), FS_NORMAL, 7);
                        
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
        $result = call_user_func_array(array($this->_jpgraph_instance, $method), $args);
        return $result;
    }
    
    public function SetCSIM($link, $alt) {
        $this->_jpgraph_instance->SetCSIMTarget($link); 
        $this->_jpgraph_instance->SetCSIMAlt($alt);
        $this->_jpgraph_instance->title->SetCSIMTarget(array($link, $link)); 
        $this->_jpgraph_instance->title->SetCSIMAlt(array($alt));
    }
}
?>
