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

var tuleap = tuleap || {};
tuleap.trackers = tuleap.trackers || {};
tuleap.trackers.report = tuleap.trackers.report || {};
tuleap.trackers.report.table = tuleap.trackers.report.table || {};

!(function ($) {
    function fixAggregatesHeight(container) {
        var container_selector = ".tracker_report_table_aggregates > td > table > " + container,
            tr_selector = container_selector + " > tr",
            $elements = $(container_selector),
            all_heights,
            max_height;

        $(tr_selector).height(0);

        all_heights = $.map($elements, function (el) {
            return $(el).height();
        });
        max_height = Math.max.apply(Math, all_heights);

        $(tr_selector).height(max_height);
    }

    tuleap.trackers.report.table.fixAggregatesHeights = function () {
        fixAggregatesHeight("thead");
        fixAggregatesHeight("tbody");
    };

    var resize_timeout;

    $(document).ready(tuleap.trackers.report.table.fixAggregatesHeights);
    $(window).resize(function () {
        clearTimeout(resize_timeout);
        resize_timeout = setTimeout(tuleap.trackers.report.table.fixAggregatesHeights, 10);
    });
})(window.jQuery);
