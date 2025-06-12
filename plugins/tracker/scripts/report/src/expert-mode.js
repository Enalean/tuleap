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
    buildTQLEditor,
    buildParserDefinition,
    insertAllowedFieldInCodeMirror,
    TQL_autocomplete_keywords,
} from "@tuleap/plugin-tracker-tql-codemirror";

let query_rich_editor = null;

export function init() {
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

        sendRequestNewMode(
            "store-expert-mode",
            getFormChallengeAssociatedWithButton(tracker_report_expert_query_button),
        );
    });

    tracker_report_normal_query_button.addEventListener("click", () => {
        tracker_report_normal_query.classList.remove("tracker-report-query-undisplayed");
        tracker_report_expert_query.classList.add("tracker-report-query-undisplayed");

        sendRequestNewMode(
            "store-normal-mode",
            getFormChallengeAssociatedWithButton(tracker_report_normal_query_button),
        );
    });
}

function getFormChallengeAssociatedWithButton(button) {
    if (!(button instanceof HTMLButtonElement)) {
        return "";
    }

    const form_data = new FormData(button.form);
    return form_data.get("challenge");
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

async function sendRequestNewMode(mode, challenge) {
    const form_data = new FormData();
    form_data.set("challenge", challenge);
    form_data.set("func", mode);

    await post(location.href, {
        body: form_data,
    });
    codendi.tracker.report.setHasChanged();
}

function initializeCodeMirror() {
    const tracker_report_expert_query = document.getElementById("tracker-report-expert-query");
    if (!tracker_report_expert_query) {
        return;
    }

    if (!tracker_report_expert_query.classList.contains("tracker-report-query-undisplayed")) {
        codeMirrorifyQueryArea();
    }
}

function codeMirrorifyQueryArea() {
    if (query_rich_editor !== null) {
        return;
    }

    const tracker_query = document.getElementById("tracker-report-expert-query-textarea");
    const allowed_fields = JSON.parse(tracker_query.dataset.allowedFields);
    const autocomplete_keywords = TQL_autocomplete_keywords.concat(allowed_fields);

    query_rich_editor = buildTQLEditor(
        {
            autocomplete: autocomplete_keywords,
            parser_definition: buildParserDefinition(["@comments"]),
        },
        tracker_query.placeholder,
        tracker_query.value,
        submitFormCallback,
        null,
    );
    tracker_query.insertAdjacentElement("afterend", query_rich_editor.dom);
    tracker_query.form?.addEventListener("submit", function () {
        tracker_query.value = query_rich_editor.state.doc.toString();
    });
}

function submitFormCallback() {
    document.querySelector('button[name="tracker_expert_query_submit"]').click();
}
