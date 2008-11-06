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

codendi.ReorderColumns = Class.create({
    initialize: function(table) {
        this.has_just_been_dragged = {};
        //Take the first row, and register the cells
        Element.childElements(table.rows[0]).map(this.register.bind(this));
    },
    register: function (cell) {
        this.registerDraggables(cell);
        this.registerDroppables(cell);
    },
    registerDraggables: function (cell) {
        this.has_just_been_dragged[cell.identify()] = false;
        cell.observe('click', (function(evt) {
            if (this.has_just_been_dragged[cell.identify()]) {
                this.has_just_been_dragged[cell.identify()] = false;
                Event.stop(evt);
            }
        }).bind(this));
        new Draggable(cell.down(), {
            revert: true,
            onStart: function () {
                Element.setStyle(cell, { cursor:"move" });
            },
            onEnd: (function () {
                this.has_just_been_dragged[cell.identify()] = true;
                Element.setStyle(cell, { cursor:"auto" });
            }).bind(this)
        });
    },
    registerDroppables: function (cell) {
        Droppables.add(cell, {
            hoverclass: 'drop-over',
            onDrop: (function(dragged, dropped, evt) {
                dragged.undoPositioned();
                var from = dragged.up('th').cellIndex;
                var to   = dropped.cellIndex;
                
                //don't change column order if it is not necessary
                if (from != to) {
                    var form = dropped.up('form');
                    var input = new Element('input', { 
                            type: 'hidden', 
                            name: 'reordercolumns['+dragged.id+']', 
                            value: (from < to ? (dropped.next() ? dropped.next().down().id : '-1') : dropped.down().id)
                    });
                    form.appendChild(input);
                    
                    this.reorder(dropped.up('table'), from, to);
    
                    //save the new column order
                    form.request();
                    
                    //remove the input for other requests
                    Element.remove(input);
                }
            }).bind(this)
        });
    },
    reorder: function(table, from, to) {
        var i = table.rows.length;
        while (i--) {
            var row  = table.rows[i];
            var cell = row.removeChild(row.cells[from]);
            if (to < row.cells.length) {
                row.insertBefore(cell, row.cells[to]);
            } else {
                row.appendChild(cell);
            }
        }
    }
});

document.observe('dom:loaded', function() {
    $$('table.reorderable').each(function (table) {
            new codendi.ReorderColumns(table);
    });
});
