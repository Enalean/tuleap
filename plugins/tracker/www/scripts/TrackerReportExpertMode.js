/**
 * Copyright (c) 2016-2017, Enalean. All rights reserved
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
        initializeTrackerReportQuery();
        initializeTrackerReportAllowedFields();
        initializeCodeMirror();

        var query_rich_editor;

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
                        var cursor_start = query_rich_editor.getCursor('from'),
                            cursor_end   = query_rich_editor.getCursor('to'),
                            value        = event.target.value;

                        if (isTextNotSelected(cursor_start, cursor_end)) {
                            value = value + ' ';
                            if (shouldASpaceBePrefixed(query_rich_editor, cursor_start)) {
                                value = ' ' + value;
                            }
                            query_rich_editor.doc.setSelection(cursor_start);
                        }

                        query_rich_editor.doc.replaceSelection(value);
                        query_rich_editor.focus();
                        event.target.selected = false;
                    }
                });
            });
        }

        function isTextNotSelected(cursor_start, cursor_end) {
            return cursor_start.line === cursor_end.line &&
                cursor_start.ch === cursor_end.ch;
        }

        function shouldASpaceBePrefixed(query_rich_editor, cursor_start) {
            var line_start = query_rich_editor.getLine(cursor_start.line);

            return cursor_start.ch !== 0 && line_start.charAt(cursor_start.ch - 1) !== ' ';
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
            var tracker_report_expert_query = document.getElementById('tracker-report-expert-query');
            if (! tracker_report_expert_query) {
                return;
            }

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
                        regex: /\d+[dwmy]/i, // Time period
                        token: "variable-3"
                    },
                    {
                        regex: /\d+(?:\.\d+)?/i, // Float & integers
                        token: "number"
                    },
                    {
                        regex: /(?:and|or)\b/i,
                        token: "keyword"
                    },
                    {
                        regex: /(?:now|between)\b/i,
                        token: "variable-2"
                    },
                    {
                        regex: /[=<>!+-]+/,
                        token: "operator"
                    },
                    {
                        regex: /[(]/,
                        token: "operator",
                        indent: true
                    },
                    {
                        regex: /[)]/,
                        token: "operator",
                        dedent: true
                    },
                    {
                        regex: /[a-zA-Z0-9_]+/,
                        token: "variable"
                    }
                ]
            });
            if (! tracker_report_expert_query.classList.contains('tracker-report-query-undisplayed')) {
                codeMirrorifyQueryArea();
            }
        }

        function codeMirrorifyQueryArea() {
            if (query_rich_editor instanceof CodeMirror) {
                query_rich_editor.refresh();
            } else {
                var tracker_query      = document.getElementById('tracker-report-expert-query-textarea'),
                    allowed_fields     = JSON.parse(tracker_query.dataset.allowedFields),
                    autocomplete_words = ['AND', 'OR', 'BETWEEN(', 'NOW()'].concat(allowed_fields);

                CodeMirror.commands.autocomplete = autocomplete;

                query_rich_editor = CodeMirror.fromTextArea(
                    tracker_query,
                    {
                        extraKeys   : { "Ctrl-Space": "autocomplete", "Ctrl-Enter": submitForm },
                        lineNumbers : false,
                        lineWrapping: true,
                        mode        : "tql",
                        readOnly    : tracker_query.readOnly ? 'nocursor' : false
                    }
                );
            }

            function submitForm() {
                document.querySelector('button[name="tracker_expert_query_submit"]').click();
            }

            function autocomplete(editor) {
                editor.showHint({
                    words: autocomplete_words,
                    hint: getHint
                });
            }

            function getHint(editor, options) {
                var cursor = editor.getCursor(),
                    token  = editor.getTokenAt(cursor);

                if (token['type'] === null || token['type'] === 'variable') {
                    return getFieldNamesHint(editor, options, cursor, token);
                }
            }

            function getFieldNamesHint(editor, options, cursor, token) {
                var start = getStartOfToken(editor),
                    end   = cursor.ch,
                    from  = CodeMirror.Pos(cursor.line, start),
                    to    = CodeMirror.Pos(cursor.line, end),
                    text  = new RegExp(token.string.trim(), 'i');

                return {
                    list: options.words.filter(function (field_name) {
                        return text.test(field_name);
                    }),
                    from: from,
                    to: to
                };
            }

            function getStartOfToken(editor) {
                var cursor = editor.getCursor(),
                    line   = editor.getLine(cursor.line),
                    start  = cursor.ch,
                    a_word = /\w+/;

                while (start && a_word.test(line.charAt(start - 1))) {
                    --start;
                }

                return start;
            }
        }
    });
})(window.jQuery);
