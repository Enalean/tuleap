<?php
/**
* WidgetLayout_Row
* 
* TODO: description
* 
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* @author  N. Terray
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
    function display() {
        echo '<table width="100%" border="0">';
        echo '<tr style="vertical-align:top;">';
        foreach($this->columns as $key => $nop) {
            $this->columns[$key]->display();
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
