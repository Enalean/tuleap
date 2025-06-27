/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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
import { createApp } from "vue";
import TimetrackingManagementWidget from "./components/TimetrackingManagementWidget.vue";
import { createGettext } from "vue3-gettext";
import { getPOFileFromLocaleWithoutExtension, initVueGettext } from "@tuleap/vue3-gettext-init";
import { RETRIEVE_QUERY, USER_LOCALE_KEY, WIDGET_ID } from "./injection-symbols";
import { QueryRetriever } from "./query/QueryRetriever";
import { getAttributeOrThrow } from "@tuleap/dom";
import { formatDatetimeToYearMonthDay } from "@tuleap/plugin-timetracking-time-formatters";
import {
    getPeriodAccordingToSelectedPreset,
    getPredefinedTimePeriodWithString,
} from "@tuleap/plugin-timetracking-predefined-time-periods";

document.addEventListener("DOMContentLoaded", async () => {
    const mount_point = document.getElementById("timetracking-management-widget");
    if (!(mount_point instanceof HTMLElement)) {
        return;
    }

    if (!document.body.dataset.userId) {
        return;
    }

    const query = JSON.parse(getAttributeOrThrow(mount_point, "data-widget-config"));

    const period = getPeriodAccordingToSelectedPreset(query.predefined_time).unwrapOr(null);

    const start_date = formatDatetimeToYearMonthDay(period?.start ?? new Date(query.start_date));
    const end_date = formatDatetimeToYearMonthDay(period?.end ?? new Date(query.end_date));

    const query_retriever = QueryRetriever();
    query_retriever.setQuery(
        start_date,
        end_date,
        getPredefinedTimePeriodWithString(query.predefined_time),
        query.users,
    );

    createApp(TimetrackingManagementWidget)
        .use(
            await initVueGettext(createGettext, (locale: string) => {
                return import(`../po/${getPOFileFromLocaleWithoutExtension(locale)}.po`);
            }),
        )
        .provide(USER_LOCALE_KEY, getAttributeOrThrow(document.body, "data-user-locale"))
        .provide(RETRIEVE_QUERY, query_retriever)
        .provide(WIDGET_ID, query.id)
        .mount(mount_point);
});
