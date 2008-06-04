<?php 
/*
 * Copyright (c) Xerox, 2008. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2008. Xerox Codex Team.
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

/**
* Chart_GanttBar
* 
* Facade for jpgraph GanttBar
* 
* @see jpgraph documentation for usage
* 
* Copyright (c) Xerox Corporation, CodeX Team, 2008. All rights reserved
*
* @author  N. Terray
*/
class Chart_GanttBar {
    
    protected $_jpgraph_instance;
    
    /**
    * Constructor
    */
    public function __construct($aPos,$aLabel,$aStart,$aEnd,$aCaption="",$aHeightFactor=0.6) {
        $this->_jpgraph_instance = new GanttBar($aPos,$aLabel,$aStart,$aEnd,$aCaption,$aHeightFactor);
        $color      = "steelblue1";
        $color_dark = $color .':0.65';
        $this->_jpgraph_instance->progress->SetPattern(BAND_SOLID, $color_dark);
        $this->_jpgraph_instance->setColor($color_dark);
        $this->_jpgraph_instance->setPattern(GANTT_SOLID, $color);
        $this->_jpgraph_instance->title->setColor("#444444");
        $this->_jpgraph_instance->title->setFont(FF_DEJAVU, FS_NORMAL, 8);
        $this->_jpgraph_instance->caption->setColor("#444444");
        $this->_jpgraph_instance->caption->setFont(FF_DEJAVU, FS_NORMAL, 7);
                        
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
        $this->_jpgraph_instance->title->SetCSIMTarget($link); 
        $this->_jpgraph_instance->title->SetCSIMAlt($alt);
    }
}
?>
