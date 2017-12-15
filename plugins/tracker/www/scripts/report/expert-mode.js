/**
 * Copyright (c) 2017, Enalean. All rights reserved
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

import codendi    from 'codendi';
import CodeMirror from 'codemirror';
import { get }    from 'tlp-fetch';
import {
    TQL_simple_mode_definition,
    TQL_autocomplete_keywords
} from './TQL-CodeMirror/configuration.js';
import {
    insertAllowedFieldInCodeMirror
} from './TQL-CodeMirror/allowed-field-inserter.js';
import {
    initializeTQLMode,
    codeMirrorify
} from './TQL-CodeMirror/builder.js';

export { init };

let query_rich_editor;

function init() {
    initializeTrackerReportQuery();
    initializeTrackerReportAllowedFields();
    initializeCodeMirror();
}

function initializeTrackerReportQuery() {
    const tracker_report_expert_query_button = document.getElementById('tracker-report-expert-query-button');
    const tracker_report_normal_query_button = document.getElementById('tracker-report-normal-query-button');
    const tracker_report_expert_query        = document.getElementById('tracker-report-expert-query');
    const tracker_report_normal_query        = document.getElementById('tracker-report-normal-query');

    if (! tracker_report_expert_query_button
        || ! tracker_report_normal_query_button
        || ! tracker_report_expert_query
        || ! tracker_report_normal_query
    ) {
        return;
    }

    tracker_report_expert_query_button.addEventListener('click', () => {
        tracker_report_normal_query.classList.add('tracker-report-query-undisplayed');
        tracker_report_expert_query.classList.remove('tracker-report-query-undisplayed');

        codeMirrorifyQueryArea();

        sendRequestNewMode('store-expert-mode');
    });

    tracker_report_normal_query_button.addEventListener('click', () => {
        tracker_report_normal_query.classList.remove('tracker-report-query-undisplayed');
        tracker_report_expert_query.classList.add('tracker-report-query-undisplayed');

        sendRequestNewMode('store-normal-mode');
    });
}

function initializeTrackerReportAllowedFields() {
    const tracker_report_expert_allowed_fields = document.getElementsByClassName('tracker-report-expert-allowed-field');
    if (! tracker_report_expert_allowed_fields) {
        return;
    }

    const allowedFieldClickedCallback = event => insertAllowedFieldInCodeMirror(event, query_rich_editor);

    for (const field of tracker_report_expert_allowed_fields) {
        field.addEventListener('click', allowedFieldClickedCallback);
    }
}

async function sendRequestNewMode(mode) {
    await get(location.href, {
        params: {
            func: mode
        }
    });
    codendi.tracker.report.setHasChanged();
}

function initializeCodeMirror() {
    const tracker_report_expert_query = document.getElementById('tracker-report-expert-query');
    if (! tracker_report_expert_query) {
        return;
    }

    initializeTQLMode(TQL_simple_mode_definition);
    if (! tracker_report_expert_query.classList.contains('tracker-report-query-undisplayed')) {
        codeMirrorifyQueryArea();
    }
}

function codeMirrorifyQueryArea() {
    const tracker_query  = document.getElementById('tracker-report-expert-query-textarea');
    const allowed_fields = JSON.parse(tracker_query.dataset.allowedFields);

    if (query_rich_editor instanceof CodeMirror) {
        query_rich_editor.refresh();
    } else {
        const autocomplete_keywords = TQL_autocomplete_keywords.concat(allowed_fields);

        query_rich_editor = codeMirrorify({
            textarea_element: tracker_query,
            autocomplete_keywords,
            submitFormCallback
        });
    }
}

function submitFormCallback() {
    document.querySelector('button[name="tracker_expert_query_submit"]').click();
}
