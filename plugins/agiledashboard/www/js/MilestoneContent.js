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

tuleap.agiledashboard.MilestoneContent = Class.create({
    initialize: function (container) {
        (function ($) {
            var milestone_content_rows = $(container).find('.milestone-content-rows');
            milestone_content_rows.sortable({
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
                cursor: 'move'
            });

            milestone_content_rows.find('tr, td').disableSelection();

            (function() { //why this anonymous function?
                milestone_content_rows.children().each(function() {
                    $(this).children().each(function() {
                        $(this).width($(this).width());
                    });
                });
            })();
        })(jQuery);
    }
});
