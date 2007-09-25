<?php
/**
* WidgetLayout_Row_Column
* 
* TODO: description
* 
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* @author  N. Terray
*/
class WidgetLayout_Row_Column {
    var $id;
    var $width;
    var $contents;
    var $row;
    function WidgetLayout_Row_Column($id, $width) {
        $this->id       = $id;
        $this->width    = $width;
        $this->contents = array();
    }
    function setRow(&$row) {
        $this->row =& $row;
    }
    function add(&$c, $is_minimized, $display_preferences) {
        $this->contents[] = array('content' => &$c, 'is_minimized' => $is_minimized, 'display_preferences' => $display_preferences);
    }
    function display($readonly) {
        echo '<td style="width:'. $this->width .'%" id="'. $this->getColumnId() .'">';
        foreach ($this->contents as $key => $nop) {
            $this->contents[$key]['content']->display($this->row->layout->id, $this->id, $readonly, $this->contents[$key]['is_minimized'], $this->contents[$key]['display_preferences']);
        }
        echo '</td>';
    }
    function getColumnId() {
        return 'widgetlayout_col_'. $this->id;
    }
}
?>
