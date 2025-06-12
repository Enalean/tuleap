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
    IS_USER_ADMIN,
    WIDGET_ID,
    RETRIEVE_ARTIFACTS_TABLE,
    DASHBOARD_TYPE,
    NEW_QUERY_CREATOR,
    WIDGET_TITLE_UPDATER,
    QUERY_UPDATER,
    WIDGET_CONTAINER,
    CAN_DISPLAY_ARTIFACT_LINK,
    RETRIEVE_ARTIFACT_LINKS,
} from "./injection-symbols";
import { ArtifactsTableRetriever } from "./api/ArtifactsTableRetriever";
import { ArtifactsTableBuilder } from "./api/ArtifactsTableBuilder";
import VueDOMPurifyHTML from "vue-dompurify-html";
import { ColumnNameGetter } from "./domain/ColumnNameGetter";
import type { Events } from "./helpers/widget-events";
import mitt from "mitt";
import { getAttributeOrThrow, selectOrThrow } from "@tuleap/dom";
import { SuggestedQueries } from "./domain/SuggestedQueriesGetter";
import { NewQueryCreator } from "./api/NewQueryCreator";
import type { WidgetData } from "./type";
import { WidgetTitleUpdater } from "./WidgetTitleUpdater";
import { QueryUpdater } from "./api/QueryUpdater";
import { ArtifactLinksRetriever } from "./api/ArtifactLinksRetriever";

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

        const widget_json_data = getAttributeOrThrow(widget_element, "data-widget-json-data");
        const widget_data: WidgetData = JSON.parse(widget_json_data);
        const emitter = mitt<Events>();

        const title_element = selectOrThrow(
            document,
            `[data-widget-title="${widget_data.title_attribute}"]`,
        );
        const widget_title_updater = WidgetTitleUpdater(
            emitter,
            title_element,
            widget_data.default_title,
        );

        const vue_mount_point = selectOrThrow(widget_element, ".vue-mount-point");
        const artifacts_table_builder = ArtifactsTableBuilder();

        createApp(CrossTrackerWidget)
            .use(gettext_plugin)
            .use(VueDOMPurifyHTML)
            .provide(DATE_FORMATTER, date_formatter)
            .provide(DATE_TIME_FORMATTER, date_time_formatter)
            .provide(
                RETRIEVE_ARTIFACTS_TABLE,
                ArtifactsTableRetriever(widget_data.widget_id, artifacts_table_builder),
            )
            .provide(RETRIEVE_ARTIFACT_LINKS, ArtifactLinksRetriever(artifacts_table_builder))
            .provide(WIDGET_ID, widget_data.widget_id)
            .provide(IS_USER_ADMIN, widget_data.is_widget_admin)
            .provide(DOCUMENTATION_BASE_URL, widget_data.documentation_base_url)
            .provide(GET_COLUMN_NAME, column_name_getter)
            .provide(EMITTER, emitter)
            .provide(GET_SUGGESTED_QUERIES, SuggestedQueries({ $gettext: gettext_plugin.$gettext }))
            .provide(DASHBOARD_TYPE, widget_data.dashboard_type)
            .provide(NEW_QUERY_CREATOR, NewQueryCreator())
            .provide(QUERY_UPDATER, QueryUpdater())
            .provide(WIDGET_TITLE_UPDATER, widget_title_updater)
            .provide(WIDGET_CONTAINER, widget_element)
            .provide(CAN_DISPLAY_ARTIFACT_LINK, widget_data.can_display_artifact_link)
            .mount(vue_mount_point);
    }
});
