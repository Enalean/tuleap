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

tuleap.agiledashboard.Planning = Class.create({
    initialize: function (container) {
        var self = this;
        (function($) {
            $(function() {
                $("#accordion > div").accordion({
                    header: "h4",
                    collapsible: true,
                    active: false,
                    beforeActivate: function (event, ui) {
                        var data_container = $(this).find(".submilestone-data");

                        if (data_container.attr("data-loaded") == "false") {
                            fetchSubmilestoneData(data_container);
                        }
                    }
                });

                function fetchSubmilestoneData(data_container) {
                    jQuery.ajax({
                        url : "/plugins/agiledashboard/?action=submilestonedata",
                        dataType : "html",
                        data : {
                            submilestone_id : data_container.attr('data-submilestone-id')
                        },
                        method : "get",
                        success : function(data) {
                            setSubmilestoneDataLoaded(data_container);
                            data_container.find('tbody').append(data);
                            self.updateSubmilestoneCapacity(data_container);
                            self.makeSubmilestonesSortable(data_container);
                        },
                        error : function(data) {
                            console.log('error', data);
                        }
                    })
                };

                function setSubmilestoneDataLoaded(data_container) {
                    data_container.attr("data-loaded", "true")
                };
            })

            
        })(jQuery);
    },
    
    updateSubmilestoneCapacity : function(data_container) {
        (function($) {
            var capacity = 0,
                capacities = data_container.find(".submilestone-element-capacity");

            capacities.each(function(){
                var element_capacity = parseFloat($(this).html());
                if (! isNaN(element_capacity)) {
                    capacity += parseFloat(element_capacity);
                }
            })
            data_container.find(".submilestone-capacity").html(capacity);
        })(jQuery);
    },
    
    makeSubmilestonesSortable : function(data_container) {
        (function($) {
            data_container.find(".submilestone-element").sortable({
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
//                    sort(ui.item);
                },
                cursor: 'move'
            });
        })(jQuery);
    }
    
    
});



