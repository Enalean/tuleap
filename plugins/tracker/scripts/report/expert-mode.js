/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

import codendi from "codendi";
import { post } from "@tuleap/tlp-fetch";
import {
    buildModeDefinition,
    TQL_autocomplete_keywords,
    insertAllowedFieldInCodeMirror,
    initializeTQLMode,
    codeMirrorify,
} from "@tuleap/plugin-tracker-tql-codemirror";

export { init };

let query_rich_editor = null;

function init() {
    initializeTrackerReportQuery();
    initializeTrackerReportAllowedFields();
    initializeCodeMirror();
}

function initializeTrackerReportQuery() {
    const tracker_report_expert_query_button = document.getElementById(
        "tracker-report-expert-query-button",
    );
    const tracker_report_normal_query_button = document.getElementById(
        "tracker-report-normal-query-button",
    );
    const tracker_report_expert_query = document.getElementById("tracker-report-expert-query");
    const tracker_report_normal_query = document.getElementById("tracker-report-normal-query");

    if (
        !tracker_report_expert_query_button ||
        !tracker_report_normal_query_button ||
        !tracker_report_expert_query ||
        !tracker_report_normal_query
    ) {
        return;
    }

    tracker_report_expert_query_button.addEventListener("click", () => {
        tracker_report_normal_query.classList.add("tracker-report-query-undisplayed");
        tracker_report_expert_query.classList.remove("tracker-report-query-undisplayed");

        codeMirrorifyQueryArea();

        sendRequestNewMode("store-expert-mode");
    });

    tracker_report_normal_query_button.addEventListener("click", () => {
        tracker_report_normal_query.classList.remove("tracker-report-query-undisplayed");
        tracker_report_expert_query.classList.add("tracker-report-query-undisplayed");

        sendRequestNewMode("store-normal-mode");
    });
}

function initializeTrackerReportAllowedFields() {
    const tracker_report_expert_allowed_fields = document.getElementById("allowed-fields");
    if (!tracker_report_expert_allowed_fields) {
        return;
    }

    tracker_report_expert_allowed_fields.addEventListener("click", (event) =>
        insertAllowedFieldInCodeMirror(event, query_rich_editor),
    );
}

async function sendRequestNewMode(mode) {
    let url = location.href + "&func=" + mode;

    if (location.search === "") {
        url = location.href + "?func=" + mode;
    }

    await post(url);
    codendi.tracker.report.setHasChanged();
}

function initializeCodeMirror() {
    const tracker_report_expert_query = document.getElementById("tracker-report-expert-query");
    if (!tracker_report_expert_query) {
        return;
    }

    const TQL_simple_mode_definition = buildModeDefinition({ additional_keywords: ["@comments"] });
    initializeTQLMode(TQL_simple_mode_definition);
    if (!tracker_report_expert_query.classList.contains("tracker-report-query-undisplayed")) {
        codeMirrorifyQueryArea();
    }
}

function codeMirrorifyQueryArea() {
    const tracker_query = document.getElementById("tracker-report-expert-query-textarea");
    const allowed_fields = JSON.parse(tracker_query.dataset.allowedFields);

    if (query_rich_editor !== null) {
        query_rich_editor.refresh();
    } else {
        const autocomplete_keywords = TQL_autocomplete_keywords.concat(allowed_fields);

        query_rich_editor = codeMirrorify(tracker_query, autocomplete_keywords, submitFormCallback);
    }
}

function submitFormCallback() {
    document.querySelector('button[name="tracker_expert_query_submit"]').click();
}
