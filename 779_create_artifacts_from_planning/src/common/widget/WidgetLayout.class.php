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

require_once('WidgetLayout_Row.class.php');
require_once('WidgetLayout_Row_Column.class.php');

/**
* WidgetLayout
*/
class WidgetLayout {
    var $id;
    var $name;
    var $description;
    var $scope;
    function WidgetLayout($id, $name, $description, $scope) {
        $this->id          = $id;
        $this->name        = $name;
        $this->description = $description;
        $this->scope       = $scope;
        $this->rows        = array();
    }
    function add(&$r) {
        $this->rows[] =& $r;
        $r->setLayout($this);
    }
    function display($readonly, $owner_id, $owner_type) {
        foreach($this->rows as $key => $nop) {
            $this->rows[$key]->display($readonly, $owner_id, $owner_type);
        }
        if (!$readonly) {
            $cells = "['". implode("', '", $this->getColumnIds()) ."']";
           echo <<<EOS
            <script type="text/javascript">
            var cells = $cells;
            Event.observe(window, 'load', function() {
                  cells.each(function (cell_id) {
                    Sortable.create(cell_id, {
                        dropOnEmpty: true,
                        constraint:  false,
                        tag:         'div',
                        handle:      'widget_titlebar_handle',
                        containment: cells,
                        format:      /^widget_(.*)$/,
                        onUpdate: function() {
                            new Ajax.Request('/widgets/updatelayout.php?owner=$owner_type'+$owner_id+'&layout_id='+$this->id+'&'+Sortable.serialize(cell_id));
                        }
                    });
                });
            });
            Event.observe(window, 'unload', function() {
                cells.each(function (cell_id) {
                    Sortable.destroy(cell_id);
                });
            });
            </script>
EOS;
        }
    }
    function getColumnIds() {
        $ret = array();
        foreach($this->rows as $key => $nop) {
            $ret = array_merge($ret, $this->rows[$key]->getColumnIds());
        }
        return $ret;
    }
}
?>
