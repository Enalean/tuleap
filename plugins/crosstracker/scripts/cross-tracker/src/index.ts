/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

import "../themes/cross-tracker.scss";
import { createApp } from "vue";
import { getPOFileFromLocaleWithoutExtension, initVueGettext } from "@tuleap/vue3-gettext-init";
import { createGettext } from "vue3-gettext";
import { getLocaleOrThrow, getTimezoneOrThrow, IntlFormatter } from "@tuleap/date-helper";
import ReadingCrossTrackerReport from "./reading-mode/reading-cross-tracker-report";
import WritingCrossTrackerReport from "./writing-mode/writing-cross-tracker-report";
import BackendCrossTrackerReport from "./backend-cross-tracker-report";
import CrossTrackerWidget from "./CrossTrackerWidget.vue";
import type { RetrieveProjects } from "./domain/RetrieveProjects";
import { getSortedProjectsIAmMemberOf } from "./api/rest-querier";
import { ProjectsCache } from "./writing-mode/ProjectsCache";
import {
    DATE_FORMATTER,
    DATE_TIME_FORMATTER,
    DOCUMENTATION_BASE_URL,
    IS_USER_ADMIN,
    REPORT_ID,
    RETRIEVE_ARTIFACTS_TABLE,
    RETRIEVE_PROJECTS,
} from "./injection-symbols";
import { ArtifactsTableRetriever } from "./api/ArtifactsTableRetriever";
import { ArtifactsTableBuilder } from "./api/ArtifactsTableBuilder";
import VueDOMPurifyHTML from "vue-dompurify-html";

document.addEventListener("DOMContentLoaded", async () => {
    const locale = getLocaleOrThrow(document);
    const gettext_plugin = await initVueGettext(
        createGettext,
        (locale) => import(`../po/${getPOFileFromLocaleWithoutExtension(locale)}.po`),
    );

    const timezone = getTimezoneOrThrow(document);
    const date_formatter = IntlFormatter(locale, timezone, "date");
    const date_time_formatter = IntlFormatter(locale, timezone, "date-with-time");

    const widget_cross_tracker_elements = document.getElementsByClassName(
        "dashboard-widget-content-cross-tracker",
    );

    const projects_retriever: RetrieveProjects = { getSortedProjectsIAmMemberOf };
    const projects_cache = ProjectsCache(projects_retriever);

    for (const widget_element of widget_cross_tracker_elements) {
        if (!widget_element || !(widget_element instanceof HTMLElement)) {
            return;
        }

        const report_id_string = widget_element.dataset.reportId;
        if (!report_id_string) {
            throw new Error("Can not find report id");
        }

        const documentation_url = widget_element.dataset.documentationBaseUrl;

        const report_id = Number.parseInt(report_id_string, 10);
        const is_widget_admin = widget_element.dataset.isWidgetAdmin === "true";

        const backend_report = new BackendCrossTrackerReport();
        const reading_report = new ReadingCrossTrackerReport();
        const writing_report = new WritingCrossTrackerReport();

        const vue_mount_point = widget_element.querySelector(".vue-mount-point");
        if (!vue_mount_point || !(vue_mount_point instanceof HTMLElement)) {
            throw new Error("vue-mount-point DOM element is not found");
        }

        createApp(CrossTrackerWidget, {
            backend_cross_tracker_report: backend_report,
            reading_cross_tracker_report: reading_report,
            writing_cross_tracker_report: writing_report,
        })
            .use(gettext_plugin)
            .use(VueDOMPurifyHTML)
            .provide(RETRIEVE_PROJECTS, projects_cache)
            .provide(DATE_FORMATTER, date_formatter)
            .provide(DATE_TIME_FORMATTER, date_time_formatter)
            .provide(
                RETRIEVE_ARTIFACTS_TABLE,
                ArtifactsTableRetriever(ArtifactsTableBuilder(), report_id),
            )
            .provide(REPORT_ID, report_id)
            .provide(IS_USER_ADMIN, is_widget_admin)
            .provide(DOCUMENTATION_BASE_URL, documentation_url)
            .mount(vue_mount_point);
    }
});
