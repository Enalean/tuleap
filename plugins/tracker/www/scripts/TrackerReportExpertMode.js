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
        initializeTrackerReportAllowedFields();
        initializeCodeMirror();

        var query_rich_editor;

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

                codeMirrorifyQueryArea();

                sendRequestNewMode('store-expert-mode');
            });

            tracker_report_normal_query_button.addEventListener('click', function() {
                tracker_report_normal_query.classList.remove('tracker-report-query-undisplayed');
                tracker_report_expert_query.classList.add('tracker-report-query-undisplayed');

                sendRequestNewMode('store-normal-mode');
            });
        }

        function initializeTrackerReportAllowedFields() {
            var tracker_report_expert_allowed_fields = document.getElementsByClassName('tracker-report-expert-allowed-field');

            if (! tracker_report_expert_allowed_fields) {
                return;
            }

            [].forEach.call(tracker_report_expert_allowed_fields, function (field) {
                field.addEventListener('click', function (event) {
                    if (query_rich_editor instanceof CodeMirror) {
                        var text_query = query_rich_editor.getValue();
                        query_rich_editor.setValue(text_query + ' ' + event.target.value);
                        event.target.selected = false;
                    }
                });
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

        function initializeCodeMirror() {
            CodeMirror.defineSimpleMode("tql", {
                start: [
                    {
                        regex: /"(?:[^\\]|\\.)*?(?:"|$)/, // double quotes
                        token: "string"
                    },
                    {
                        regex: /'(?:[^\\]|\\.)*?(?:'|$)/, // single quotes
                        token: "string"
                    },
                    {
                        regex: /(?:and|or)\b/i,
                        token: "keyword"
                    },
                    {
                        regex: /[=]+/,
                        token: "operator"
                    },
                    {
                        regex: /[(]/,
                        indent: true
                    },
                    {
                        regex: /[)]/,
                        dedent: true
                    },
                    {
                        regex: /[a-zA-Z0-9_]+/,
                        token: "variable"
                    }
                ]
            });

            var tracker_report_expert_query = document.getElementById('tracker-report-expert-query');
            if (! tracker_report_expert_query.classList.contains('tracker-report-query-undisplayed')) {
                codeMirrorifyQueryArea();
            }
        }

        function codeMirrorifyQueryArea() {
            if (query_rich_editor instanceof CodeMirror) {
                query_rich_editor.refresh();
            } else {
                var tracker_query = document.getElementById('tracker-report-expert-query-textarea');
                query_rich_editor = CodeMirror.fromTextArea(
                    tracker_query,
                    {
                        lineNumbers: true,
                        mode: "tql"
                    }
                );
            }
        }
    });
})(window.jQuery);
