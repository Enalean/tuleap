/**
 * Copyright (c) 2016, Enalean. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

(function($) {
    $(document).ready(function() {
        initializeTooltip();
        initializeTrackerReportQuery();

        function initializeTooltip() {
            $('#tracker-report-expert-query-tooltip').tooltip({ placement: 'right'});
        }

        function initializeTrackerReportQuery() {
            var tracker_report_expert_query_button = document.getElementById('tracker-report-expert-query-button'),
                tracker_report_normal_query_button = document.getElementById('tracker-report-normal-query-button'),
                tracker_report_expert_query        = document.getElementById('tracker-report-expert-query'),
                tracker_report_normal_query        = document.getElementById('tracker-report-normal-query');

            if (! tracker_report_expert_query_button
                || ! tracker_report_normal_query_button
                || ! tracker_report_expert_query
                || ! tracker_report_normal_query
            ) {
                return;
            }

            tracker_report_expert_query_button.addEventListener('click', function() {
                tracker_report_normal_query.classList.add('tracker-report-query-undisplayed');
                tracker_report_expert_query.classList.remove('tracker-report-query-undisplayed');

                sendRequestNewMode('store-expert-mode');
            });

            tracker_report_normal_query_button.addEventListener('click', function() {
                tracker_report_normal_query.classList.remove('tracker-report-query-undisplayed');
                tracker_report_expert_query.classList.add('tracker-report-query-undisplayed');

                sendRequestNewMode('store-normal-mode');
            });
        }

        function sendRequestNewMode(mode) {
            $.ajax({
                url: location.href,
                data: {
                    func: mode
                },
                success: function(){
                    codendi.tracker.report.setHasChanged();
                }
            });
        }
    });
})(window.jQuery);
