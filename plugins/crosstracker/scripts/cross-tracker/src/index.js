/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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

import Vue from "vue";
import GettextPlugin from "vue-gettext";
import french_translations from "../po/fr.po";

import { createStore } from "./store/index.js";
import { init as initUser } from "./user-service.js";
import ReadingCrossTrackerReport from "./reading-mode/reading-cross-tracker-report.js";
import WritingCrossTrackerReport from "./writing-mode/writing-cross-tracker-report.js";
import BackendCrossTrackerReport from "./backend-cross-tracker-report.js";
import CrossTrackerWidget from "./CrossTrackerWidget.vue";

document.addEventListener("DOMContentLoaded", () => {
    Vue.use(GettextPlugin, {
        translations: {
            fr: french_translations.messages,
        },
        silent: true,
    });
    const locale = document.body.dataset.userLocale;
    Vue.config.language = locale;

    const widget_cross_tracker_elements = document.getElementsByClassName(
        "dashboard-widget-content-cross-tracker"
    );
    const Widget = Vue.extend(CrossTrackerWidget);

    for (const widget_element of widget_cross_tracker_elements) {
        const report_id = widget_element.dataset.reportId;
        const localized_php_date_format = widget_element.dataset.dateFormat;
        const is_widget_admin = widget_element.dataset.isWidgetAdmin === "true";

        initUser(localized_php_date_format, locale);

        const backend_cross_tracker_report = new BackendCrossTrackerReport();
        const reading_cross_tracker_report = new ReadingCrossTrackerReport();
        const writing_cross_tracker_report = new WritingCrossTrackerReport();

        const store = createStore();
        store.commit("initWithDataset", { report_id, is_widget_admin });

        const vue_mount_point = widget_element.querySelector(".vue-mount-point");
        new Widget({
            store,
            propsData: {
                backendCrossTrackerReport: backend_cross_tracker_report,
                readingCrossTrackerReport: reading_cross_tracker_report,
                writingCrossTrackerReport: writing_cross_tracker_report,
            },
        }).$mount(vue_mount_point);
    }
});
