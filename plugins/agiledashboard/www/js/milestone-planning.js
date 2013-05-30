/*
 * Copyright Enalean (c) 2013. All rights reserved.
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
    tuleap.agiledashboard.NewPlanning = Class.create({
        dragging : false,

        initialize: function () {
            var self = this;

            $('.agiledashboard-planning-submilestone-header').click(function (event) {
                var $submilestone_content_row = $(this).next();
                if ($submilestone_content_row.is(':visible')) {
                    $submilestone_content_row.hide();
                } else {
                    var data_container = $submilestone_content_row.find(".submilestone-data");

                    if (! isSubmilestoneDataLoaded(data_container)) {
                        self.fetchSubmilestoneData(data_container);
                    }
                    $submilestone_content_row.show();
                }
                $(this).find('i').toggleClass('icon-chevron-down').toggleClass('icon-chevron-right');
                event.preventDefault();
            });
            $('.agiledashboard-planning-submilestone-header').next().hide();

            function isSubmilestoneDataLoaded(data_container) {
                return data_container.attr("data-loaded") === "true";
            }

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
                    self.setSubmilestonesEditLinks(data_container);
                    self.updateSubmilestoneCapacities();
                    self.makeSubmilestonesSortable();
                }
            });
         },

        setSubmilestoneDataLoaded : function(data_container) {
            data_container.attr("data-loaded", "true");
        },

        setSubmilestonesEditLinks : function(data_container) {
            var urls                  = $('tr.submilestone-element td > a', data_container),
                milestone_id          = this.getMilestoneId(),
                milestone_planning_id = this.getMilestonePlanningId();

            urls.each( function() {
                var new_url = $(this).attr('href') + '&' + 'planning[planning][' + milestone_planning_id + ']=' + milestone_id;

                $(this).attr('href', new_url);
            });
        },

        getMilestoneId : function() {
            return $('div.agiledashboard-planning-backlog').attr('data-milestone-id');
        },

        getMilestonePlanningId : function() {
            return $('div.agiledashboard-planning-backlog').attr('data-milestone-planning-id');
        },

        updateSubmilestoneCapacities : function() {
            var $submilestones = $(".submilestone-data");

            $submilestones.each(function() {
                var remaining_effort = 0,
                    $all_efforts = $(this).find(".submilestone-element-remaining-effort");

                $all_efforts.each(function(){
                    var element_effort = parseFloat($(this).html());

                    if (! isNaN(element_effort)) {
                        remaining_effort += parseFloat(element_effort);
                    }
                });

                $(this).find(".submilestone-effort").html(remaining_effort);
            });
        },

        displayPlaceHolderIfEmpty : function(target) {
            var $tables = $('table.submilestone-element-rows'),
                self = this;

            $tables.each(function() {

                if (shouldDisplayPlaceholder(this)) {
                    var $placeholder = $('tr.empty-table-placeholder', this);

                    $placeholder.show('slow');
                } else {
                    $('tr.empty-table-placeholder', this).fadeOut('slow');
                }

                function shouldDisplayPlaceholder(table) {
                    console.log($('tbody tr', table).length);
                    var SIZE_OF_TABLE_WHEN_THERE_IS_ONLY_OUR_EMPTY_PLACEHOLDER = 1;

                    return $('tbody tr', table).length === SIZE_OF_TABLE_WHEN_THERE_IS_ONLY_OUR_EMPTY_PLACEHOLDER || isDraggingLastRow(table);
                }

                function isDraggingLastRow(table) {
                    /* When we are dragging the last row in a table, there is
                     * actually 4 remaining rows:
                     *
                     * - the initial row
                     * - the helper (jQuery, clone of the initial row)
                     * - placeholder (jQuery)
                     * - empty placeholder (ours, "This element is empty, bla bla bla")
                     */
                    var SIZE_OF_TABLE_WHILE_DRAGGING_THE_LAST_ROW = 4;

                    if (typeof(target) !== 'undefined') {
                        var target_submilestone_id = $(target).parents('[data-submilestone-id]').first().attr('data-submilestone-id');
                        var table_submilestone_id  = $(table).parents('[data-submilestone-id]').first().attr('data-submilestone-id');
                        var submilestone_are_equal = target_submilestone_id === table_submilestone_id;

                        return self.dragging === true
                            && $('tr', target).length === SIZE_OF_TABLE_WHILE_DRAGGING_THE_LAST_ROW
                            && submilestone_are_equal;
                    }
                }
            });
        },

        makeSubmilestonesSortable : function() {
            var self = this,
                from_submilestone_id,
                user_can_plan = $("[data-can-plan]").attr("data-can-plan"),
                $submilestone_element_rows = $(".submilestone-element-rows");

            if (user_can_plan !== "true") {
                return;
            }

            var $submilestone_element_rows = $(".submilestone-element-rows tbody");

            $submilestone_element_rows.sortable({
                connectWith: ".submilestone-element-rows tbody",
                dropOnEmpty: true,
                scroll: true,
                cancel: ".empty-table-placeholder",
                tolerance : "pointer",
                scrollSensitivity: 50,
                items : ".submilestone-element",
                helper: function (e, tr) {
                    var $originals = tr.children();
                    var $helper = tr.clone();
                    $helper.children().each(function (index) {
                        // Set helper cell sizes to match the original size
                        $(this).width($originals.eq(index).width());
                    });

                    return $helper;
                },
                start : function (event, ui) {
                    self.dragging = true;
                    self.displayPlaceHolderIfEmpty(event.target);
                    from_submilestone_id = $(event.target).parents(".submilestone-data").first().attr('data-submilestone-id');
                },
                stop: function (event, ui) {
                    var item               = ui.item,
                        rowIdentifier      = "data-artifact-id",
                        item_id            = $(item).attr(rowIdentifier),
                        next_id            = $(item).nextAll(".submilestone-element").first().attr(rowIdentifier),
                        prev_id            = $(item).prevAll(".submilestone-element").first().attr(rowIdentifier),
                        to_submilestone_id = getToSubmilestoneId();

                        self.dragging = false;
                        updateElement();
                        self.updateSubmilestoneCapacities();
                        self.displayPlaceHolderIfEmpty();

                        function getToSubmilestoneId() {
                            return $(ui.item).parents(".submilestone-data").first().attr('data-submilestone-id');
                        }

                        function updateElement() {
                            if(to_submilestone_id == from_submilestone_id) {
                                 sort();
                            } else if (typeof(to_submilestone_id) === "undefined" && typeof(from_submilestone_id) === "undefined") {
                                sort();
                            } else if (typeof(to_submilestone_id) === "undefined") {
                                updateArtifactlink("unassociate-artifact-to", from_submilestone_id, sort)
                            } else if (typeof(from_submilestone_id) === "undefined") {
                                updateArtifactlink("associate-artifact-to", to_submilestone_id, sort)
                            } else {
                                updateArtifactlink("unassociate-artifact-to", from_submilestone_id, sort)
                                updateArtifactlink("associate-artifact-to", to_submilestone_id)
                            }
                        }

                        function sort() {
                            if (next_id) {
                                sortHigher(item_id, next_id);
                            } else if (prev_id) {
                                sortLesser(item_id, prev_id);
                            }
                        }

                        function updateArtifactlink(func, submilestone_id, callback) {
                             $.ajax({
                                url  : codendi.tracker.base_url,
                                data : {
                                    "func"              : func,
                                    "aid"               : submilestone_id,
                                    "linked-artifact-id": item_id
                                },
                                method : "get",
                                success : function() {
                                    if (typeof(callback) === "function") {
                                        callback();
                                    }
                                }
                            });
                        }

                        function sortHigher(source_id, target_id) {
                            updateOrder('higher-priority-than', source_id, target_id);
                        }

                        function sortLesser(source_id, target_id) {
                            updateOrder('lesser-priority-than', source_id, target_id);
                        }

                        function updateOrder(func, source_id, target_id) {
                            $.ajax({
                                url  : codendi.tracker.base_url,
                                data : {
                                    "func"             : func,
                                    "aid"              : source_id,
                                    "target-id"        : target_id
                                },
                                method : "get"
                            });
                        }
                }
            }).disableSelection();

            self.displayPlaceHolderIfEmpty();

            (establishWidthOfCellsToBeConstitentWhileDragging = function() {
                $submilestone_element_rows.children().each(function() {
                    $(this).children().each(function() {
                        $(this).width($(this).width());
                    });
                });
            })();
        }
    });
})(jQuery);
