/* 
 * Copyright Enalean (c) 2011, 2012, 2013. All rights reserved.
 * 
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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
   
(function ($) {
    tuleap.agiledashboard.Planning = Class.create({
        initialize: function (container) {
            var self = this;

            $("#accordion > div").accordion({
                header: "h4",
                collapsible: true,
                animate: false,
                active: false,
                heightStyle: "content",
                beforeActivate: function (event, ui) {
                    var data_container = $(this).find(".submilestone-data");

                    if (data_container.attr("data-loaded") == "false") {
                        self.fetchSubmilestoneData(data_container);
                    }
                }
            });

            this.makeSubmilestonesSortable();
        },

        fetchSubmilestoneData : function(data_container) {
            var self = this;

            $.ajax({
                url : "/plugins/agiledashboard/?action=submilestonedata",
                dataType : "html",
                data : {
                    planning_id: data_container.attr('data-planning-id'),
                    aid : data_container.attr('data-submilestone-id')
                },
                method : "get",
                success : function(data) {
                    self.setSubmilestoneDataLoaded(data_container);
                    data_container.find('tbody').append(data);
                    self.updateSubmilestoneCapacity(data_container);
                    self.makeSubmilestonesSortable(data_container);
                },
                error : function(data) {
                    console.log('error', data);
                }
            });
         },

        setSubmilestoneDataLoaded : function(data_container) {
            data_container.attr("data-loaded", "true");
        },

        updateSubmilestoneCapacity : function(data_container) {
            var capacity = 0,
                capacities = data_container.find(".submilestone-element-capacity");

            capacities.each(function(){
                var element_capacity = parseFloat($(this).html());
                if (! isNaN(element_capacity)) {
                    capacity += parseFloat(element_capacity);
                }
            });

            data_container.find(".submilestone-capacity").html(capacity);
        },

        makeSubmilestonesSortable : function(data_container) {
            if (data_container.find(".submilestone-element-rows").hasClass('ui-sortable')) {
                data_container.find(".submilestone-element-rows").sortable("destroy");
            }

            $( ".submilestone-element-rows" ).sortable({
                connectWith: ".submilestone-element-rows",
                dropOnEmpty: true,
                tolerance : "pointer",
                scrollSensitivity: 50,

                start : function() {
                    $(".submilestone-drop-helper").show()
                },
                stop: function (event, ui) {
                    $(".submilestone-drop-helper").hide();
                    
                    sort(ui.item, "data-artifact-id");

                    function sort(item, rowIdentifier) {
                        var item_id = $(item).attr(rowIdentifier),
                            next_id = $(item).nextAll(".submilestone-element").first().attr(rowIdentifier),
                            prev_id = $(item).prevAll(".submilestone-element").first().attr(rowIdentifier);

                        if (next_id) {
                            sortHigher(item_id, next_id);
                        } else if (prev_id) {
                            sortLesser(item_id, prev_id);
                        }
                    }

                    function sortHigher(source_id, target_id) {
                        requestSort('higher-priority-than', source_id, target_id);
                    }

                    function sortLesser(source_id, target_id) {
                        requestSort('lesser-priority-than', source_id, target_id);
                    }

                    function requestSort(action, source_id, target_id) {
                        $.ajax({
                            url  : codendi.tracker.base_url,
                            data : {
                                "func"      : action,
                                "aid"       : source_id,
                                "target-id" : target_id
                            },
                            method : "get"
                        });
                    }
                }
            }).disableSelection();
        }


    });
})(jQuery);