/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2008. All rights reserved
*
* Originally written by Nicolas Terray, 2008
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
* along with Codendi; if not, write to the Free Software
* Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*
* 
*/

var codendi = codendi || { };

codendi.reorder_columns = { };

codendi.ReorderColumns = Class.create({
    initialize: function (table) {
        this.has_just_been_dragged = {};
        //Take the first row, and register the cells
        $(table).down('thead').down('tr').select('.tracker_report_table_column').map(this.register.bind(this));
    },
    register: function (cell) {
        if (cell.id.match(/_\d+$/)) {
            this.registerDraggables(cell);
            this.registerDroppables(cell);
        }
    },
    registerDraggables: function (cell) {
        this.has_just_been_dragged[cell.identify()] = false;
        cell.observe('click', (function (evt) {
            if (this.has_just_been_dragged[cell.identify()]) {
                this.has_just_been_dragged[cell.identify()] = false;
                Event.stop(evt);
            }
        }).bind(this));
        var d = new Draggable(cell.down('.tracker_report_table_column_title'), {
            revert: true,
            onStart: function () {
                Element.addClassName(cell, "reordercolumns_ondrag");
            },
            onEnd: (function () {
                this.has_just_been_dragged[cell.identify()] = true;
                Element.removeClassName(cell, "reordercolumns_ondrag");
            }).bind(this)
        });
    },
    registerDroppables: function (cell) {
        Droppables.add(cell, {
            hoverclass: 'drop-over',
            onDrop: (function (dragged, dropped, evt) {
                dragged.undoPositioned();
                var from = dragged.up('th').cellIndex;
                var to   = dropped.cellIndex;
                
                //don't change column order if it is not necessary
                if (from !== to) {
                    
                    var parameters = {
                            func:     'renderer',
                            renderer: $('tracker_report_table_addcolumn_form').renderer.value
                        }, 
                        param_name = 'renderer_table[reorder-column][' + dragged.up('th').id.match(/_(\d+)$/)[1] + ']';
                    if (from < to) {
                        parameters[param_name] = '-2';
                        if (dropped.next()) {
                            parameters[param_name] = dropped.id.match(/_(\d+)$/)[1];
                        }
                    } else {
                        parameters[param_name] = '-1';
                        if (dropped.previous() && dropped.previous().id) {
                            parameters[param_name] = dropped.previous().id.match(/_(\d+)$/)[1];
                        }
                    }
                    
                    //save the new column order
                    var form = $('tracker_report_table_addcolumn_form');
                    var req = new Ajax.Request(codendi.tracker.base_url + '?report=' + form.report.value + '&renderer=' + form.renderer.value, {
                        parameters: parameters,
                        onSuccess: function (transport) {
                            this.reorder(dropped.up('table'), from, to);
                            codendi.tracker.report.setHasChanged();
                        }.bind(this)
                    });
                }
            }).bind(this)
        });
    },
    reorder: function (table, from, to) {
        var i = table.rows.length;
        while (i--) {
            var row  = table.rows[i];
            if (row.cells[from] && row.cells[to]) {
                var cell = row.removeChild(row.cells[from]);
                if (to < row.cells.length) {
                    row.insertBefore(cell, row.cells[to]);
                } else {
                    row.appendChild(cell);
                }
            }
        }
    }
});

document.observe('dom:loaded', function () {
    $$('table.reorderable').each(function (table) {
        codendi.reorder_columns[table.identify()] = new codendi.ReorderColumns(table);
    });
});
