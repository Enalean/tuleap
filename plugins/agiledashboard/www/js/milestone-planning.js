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
            var self = this,
                $backlog = $(".agiledashboard-planning-backlog");

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

            // let the backlog items be draggable
            $( "tr", $backlog ).draggable({
                cancel: "a.ui-icon", // clicking an icon won't initiate dragging
                revert: "invalid", // when not dropped, the item will revert back to its initial position
                containment: "document",
                helper: "clone",
                cursor: "move"
            });
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
                    self.setSubmilestonesEditLinks(data_container);
                    self.updateSubmilestoneCapacity(data_container);
                    self.makeSubmilestonesSortable(data_container);
                    self.makeSubmilestonesDroppable(data_container);
                },
                error : function(data) {
                    console.log('error', data);
                }
            });
         },

        setSubmilestoneDataLoaded : function(data_container) {
            data_container.attr("data-loaded", "true");
        },

        setSubmilestonesEditLinks : function(data_container) {
            var urls = $('tr.submilestone-element td > a', data_container);

            urls.each( function(index, url) {
                var $url    = $(url);
                var new_url = $url.attr('href') + '&' + 'planning[planning][' + 6 + ']=' + 24;

                $url.attr('href', new_url);
            });

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
            var params = {
                rowContainer    : data_container.find(".submilestone-element-rows"),
                rowIdentifier   : 'data-artifact-id'
            };
            new tuleap.agiledashboard.TableRowSorter(params);
        },

        makeSubmilestonesDroppable : function(data_container) {
            var $planning= $(".agiledashboard-planning-submilestones tbody"),
                self = this;

            // let the planning be droppable, accepting the gallery items
            $planning.droppable({
                accept: ".agiledashboard-planning-backlog tbody > tr",
                activeClass: "ui-state-highlight",
                drop: function( event, ui ) {
                    var target = event.target;

                    prependSubmilestoneElement( target, ui.draggable);
                    synchroniseSubmilestoneWithBackend(target, ui.draggable);
                }
            });

            function prependSubmilestoneElement(target, $element) {
                $element.addClass("submilestone-element")
                $(target).prepend($element);
                $element.draggable( "disable" );
            }

            function synchroniseSubmilestoneWithBackend(target, $element) {
                //do it here
            }
        }
    });
})(jQuery);
