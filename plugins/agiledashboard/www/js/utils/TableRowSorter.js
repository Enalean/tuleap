/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

var tuleap = tuleap || { };
tuleap.agiledashboard = tuleap.agiledashboard || { };

tuleap.agiledashboard.TableRowSorter = Class.create({
    rowIdentifier : "",
    jQuery : jQuery,

    initialize: function (params) {
        var container = params.rowContainer,
            self      = this,
            $         = this.jQuery;

        this.rowIdentifier = params.rowIdentifier;

        $(container).sortable({
            revert: true,
            axis: 'y',
            forcePlaceholderSize: true,
            containment: "parent",
            helper: function (e, tr) {
                var $originals = tr.children();
                var $helper = tr.clone();
                $helper.children().each(function (index) {
                    // Set helper cell sizes to match the original size
                    $(this).width($originals.eq(index).width());
                });

                return $helper;
            },
            stop: function (event, ui) {
                self.sort(ui.item);
            },
            cursor: 'move'
        });

        container.find('tr, td').disableSelection();
        this.establishWidthOfCellsToBeConstitentWhileDragging(container);  
    },
    
    establishWidthOfCellsToBeConstitentWhileDragging : function(rows) {
        var $ = this.jQuery;

        rows.children().each(function() {
            $(this).children().each(function() {
                $(this).width($(this).width());
            });
        });
    },

    sort : function(item) {
        var $ = this.jQuery,
            item_id = $(item).attr(this.rowIdentifier),
            next_id = $(item).next().attr(this.rowIdentifier),
            prev_id = $(item).prev().attr(this.rowIdentifier);

        if (next_id) {
            this.sortHigher(item_id, next_id);
        } else if (prev_id) {
            this.sortLesser(item_id, prev_id);
        }
    },

    sortHigher : function(source_id, target_id) {
        this.requestSort('higher-priority-than', source_id, target_id);
    },

     sortLesser : function(source_id, target_id) {
        this.requestSort('lesser-priority-than', source_id, target_id);
    },

     requestSort : function(action, source_id, target_id) {
        this.jQuery.ajax({
            url  : codendi.tracker.base_url,
            data : {
                "func"      : action,
                "aid"       : source_id,
                "target-id" : target_id
            },
            method : "get"
        });
    }

});
