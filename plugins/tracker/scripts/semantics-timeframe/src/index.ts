/*
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
import VueDOMPurifyHTML from "vue-dompurify-html";
import App from "./components/App.vue";
import { getPOFileFromLocaleWithoutExtension, initVueGettext } from "@tuleap/vue3-gettext-init";
import { createGettext } from "vue3-gettext";

document.addEventListener("DOMContentLoaded", async () => {
    const mount_point = document.getElementById("semantic-timeframe-admin-mount-point");
    if (!mount_point) {
        return;
    }

    const locale = document.body.dataset.userLocale;
    if (locale === undefined) {
        throw new Error("Unable to load user locale");
    }

    const gettext = await initVueGettext(createGettext, (locale: string) => {
        return import(`../po/${getPOFileFromLocaleWithoutExtension(locale)}.po`);
    });

    const app = createApp(App, {
        usable_date_fields:
            typeof mount_point.dataset.usableDateFields !== "undefined"
                ? JSON.parse(mount_point.dataset.usableDateFields)
                : [],
        usable_numeric_fields:
            typeof mount_point.dataset.usableNumericFields !== "undefined"
                ? JSON.parse(mount_point.dataset.usableNumericFields)
                : [],
        suitable_trackers:
            typeof mount_point.dataset.suitableTrackers !== "undefined"
                ? JSON.parse(mount_point.dataset.suitableTrackers)
                : [],
        implied_from_tracker_id:
            Number.parseInt(mount_point.dataset.impliedFromTrackerId || "0", 10) || "",
        start_date_field_id: Number.parseInt(mount_point.dataset.startDateFieldId || "0", 10) || "",
        end_date_field_id: Number.parseInt(mount_point.dataset.endDateFieldId || "0", 10) || "",
        duration_field_id: Number.parseInt(mount_point.dataset.durationFieldId || "0", 10) || "",
        target_url: String(mount_point.dataset.targetUrl),
        csrf_token: String(mount_point.dataset.csrfToken),
        has_other_trackers_implying_their_timeframes: Boolean(
            mount_point.dataset.hasOtherTrackersImplyingTheirTimeframes,
        ),
        has_tracker_charts: Boolean(mount_point.dataset.hasTrackerCharts),
        has_artifact_link_field: Boolean(mount_point.dataset.hasArtifactLinkField),
        current_tracker_id: Number.parseInt(mount_point.dataset.currentTrackerId || "0", 10) || 0,
        semantic_presentation: mount_point.dataset.semanticPresentation,
        should_send_event_in_notification: Boolean(
            mount_point.dataset.shouldSendEventInNotification,
        ),
    });

    app.use(gettext);
    app.use(VueDOMPurifyHTML);

    app.mount(mount_point);
});
