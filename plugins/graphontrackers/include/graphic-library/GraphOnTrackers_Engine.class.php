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
 * Graphic engine which builds a graph
 */
abstract class GraphOnTrackers_Engine {
    
    public $graph;
    public $data;
    
    /**
     * @return boolean true if the data are valid to buid the chart
     */
    public function validData() {
        if (count($this->data) > 0) {
            return true;
        }else{
            echo ' <p class="feedback_info">';
            echo $GLOBALS['Language']->getText('plugin_graphontrackers_engine','no_datas',array($this->title));
            echo '</p>';
            return false;
        }
    }
    
    /**
     * Build graph based on data, title, description given to the engine
     */
    abstract public function buildGraph();
}
?>
