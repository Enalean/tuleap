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
        $this->_jpgraph_instance->SetMarginColor("white");
        $this->_jpgraph_instance->SetFrame(true, $this->getMainColor(), 1);
        if ($aWidth && $aHeight) {
            $this->_jpgraph_instance->img->SetAntiAliasing();
        }
        
        $this->_jpgraph_instance->legend->SetShadow(false);
        $this->_jpgraph_instance->legend->SetColor($this->getMainColor());
        $this->_jpgraph_instance->legend->SetFillColor('#fefefe');
        $this->_jpgraph_instance->legend->SetFont(FF_DEJAVU,FS_NORMAL,8);
        $this->_jpgraph_instance->legend->SetVColMargin(5);
        
        $this->_jpgraph_instance->title->SetFont(FF_DEJAVU,FS_BOLD,12);
        $this->_jpgraph_instance->title->SetColor($this->getMainColor());
        $this->_jpgraph_instance->title->SetMargin(15);
        
        $this->_jpgraph_instance->subtitle->SetFont($this->getFont(), FS_NORMAL,8);
        $this->_jpgraph_instance->subtitle->SetColor($this->getMainColor());
        
        $this->_themed_colors = array(
            'lightsalmon',
            'palegreen',
            'palegoldenrod',
            'lightyellow',
            'paleturquoise',
            'steelblue1',
            'thistle',
            'palevioletred1',
            'wheat1',
            'gold',
            'olivedrab1',
            'lightcyan',
            'lightcyan3',
            'lightgoldenrod1',
            'rosybrown',
            'mistyrose',
            'silver',
            'aquamarine',
        );
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
        return "#444444";
    }
    
    public function getThemedColors() {
        return $this->_themed_colors;
    }
}
?>
