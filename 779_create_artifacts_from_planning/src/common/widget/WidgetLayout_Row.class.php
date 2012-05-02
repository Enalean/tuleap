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

class WidgetLayout_Row {
    var $id;
    var $rank;
    var $columns;
    var $layout;
    
    function WidgetLayout_Row($id, $rank) {
        $this->id      = $id;
        $this->rank    = $rank;
        $this->columns = array();
    }
    function setLayout(&$layout) {
        $this->layout =& $layout;
    }
    function add(&$c) {
        $this->columns[] =& $c;
        $c->setRow($this);
    }
    function display($readonly, $owner_id, $owner_type) {
        echo '<table width="100%" border="0" cellpadding="0" cellspacing="0">';
        echo '<tr style="vertical-align:top;">';
        $last = count($this->columns) - 1;
        $i = 0;
        foreach($this->columns as $key => $nop) {
            $this->columns[$key]->display($readonly, $owner_id, $owner_type, $is_last = ($i++ == $last));
        }
        echo '</tr>';
        echo '</table>';
    }
    function getColumnIds() {
        $ret = array();
        foreach($this->columns as $key => $nop) {
            $ret[] = $this->columns[$key]->getColumnId();
        }
        return $ret;
    }
}
?>
