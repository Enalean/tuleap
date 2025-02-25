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
import CrossTrackerWidget from "./CrossTrackerWidget.vue";
import {
    DATE_FORMATTER,
    DATE_TIME_FORMATTER,
    DOCUMENTATION_BASE_URL,
    EMITTER,
    GET_COLUMN_NAME,
    GET_SUGGESTED_QUERIES,
    IS_MULTIPLE_QUERY_SUPPORTED,
    IS_USER_ADMIN,
    WIDGET_ID,
    RETRIEVE_ARTIFACTS_TABLE,
    DASHBOARD_TYPE,
} from "./injection-symbols";
import { ArtifactsTableRetriever } from "./api/ArtifactsTableRetriever";
import { ArtifactsTableBuilder } from "./api/ArtifactsTableBuilder";
import VueDOMPurifyHTML from "vue-dompurify-html";
import { ColumnNameGetter } from "./domain/ColumnNameGetter";
import type { Events } from "./helpers/emitter-provider";
import mitt from "mitt";
import { getAttributeOrThrow, selectOrThrow } from "@tuleap/dom";
import { SuggestedQueries } from "./domain/SuggestedQueriesGetter";

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

    const column_name_getter = ColumnNameGetter({ $gettext: gettext_plugin.$gettext });

    for (const widget_element of widget_cross_tracker_elements) {
        if (!widget_element || !(widget_element instanceof HTMLElement)) {
            return;
        }

        const documentation_url = getAttributeOrThrow(
            widget_element,
            "data-documentation-base-url",
        );
        const widget_id_string = getAttributeOrThrow(widget_element, "data-widget-id");
        const widget_id = Number.parseInt(widget_id_string, 10);
        const is_widget_admin = Boolean(
            getAttributeOrThrow(widget_element, "data-is-widget-admin"),
        );
        const is_multiple_query_supported = Boolean(
            getAttributeOrThrow(widget_element, "data-is-multiple-query-supported"),
        );

        const dashboard_type = getAttributeOrThrow(widget_element, "data-dashboard-type");

        const vue_mount_point = selectOrThrow(widget_element, ".vue-mount-point");

        createApp(CrossTrackerWidget)
            .use(gettext_plugin)
            .use(VueDOMPurifyHTML)
            .provide(DATE_FORMATTER, date_formatter)
            .provide(DATE_TIME_FORMATTER, date_time_formatter)
            .provide(
                RETRIEVE_ARTIFACTS_TABLE,
                ArtifactsTableRetriever(widget_id, ArtifactsTableBuilder()),
            )
            .provide(WIDGET_ID, widget_id)
            .provide(IS_USER_ADMIN, is_widget_admin)
            .provide(DOCUMENTATION_BASE_URL, documentation_url)
            .provide(GET_COLUMN_NAME, column_name_getter)
            .provide(EMITTER, mitt<Events>())
            .provide(IS_MULTIPLE_QUERY_SUPPORTED, is_multiple_query_supported)
            .provide(GET_SUGGESTED_QUERIES, SuggestedQueries({ $gettext: gettext_plugin.$gettext }))
            .provide(DASHBOARD_TYPE, dashboard_type)
            .mount(vue_mount_point);
    }
});
