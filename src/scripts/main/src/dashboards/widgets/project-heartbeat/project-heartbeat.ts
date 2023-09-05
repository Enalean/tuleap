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

import { get } from "@tuleap/tlp-fetch";
import mustache from "mustache";
import { sanitize } from "dompurify";
import moment from "moment";
import { loadTooltips } from "@tuleap/tooltip";
import { formatFromPhpToMoment } from "@tuleap/date-helper";

export default init;

interface Group {
    label: string;
    entries: Entry[];
}

interface Entry {
    updated_at: string;
}

function init(): void {
    const heartbeat_widgets = document.querySelectorAll(
        ".dashboard-widget-content-projectheartbeat-content",
    );

    [].forEach.call(heartbeat_widgets, async (widget_content: HTMLElement) => {
        try {
            const response = await get(
                "/api/v1/projects/" + widget_content.dataset.projectId + "/heartbeats",
            );
            const json = await response.json();
            if (json.entries.length > 0) {
                displayActivities(widget_content, json.entries);
            } else {
                displayEmptyState(widget_content, json);
            }
        } catch (error) {
            displayError(widget_content);
        }
    });
}

function displayActivities(widget_content: HTMLElement, entries: Entry[]): void {
    hideEverything(widget_content);
    const activities = widget_content.querySelector(
        ".dashboard-widget-content-projectheartbeat-activities",
    );
    if (!(activities instanceof Element)) {
        throw new Error("No activies defined in projectheartbeat");
    }
    const template = widget_content.querySelector(
        ".dashboard-widget-content-projectheartbeat-placeholder",
    )?.textContent;

    if (!template) {
        throw new Error("No template defined in projectheartbeat");
    }

    const rendered_activities = mustache.render(
        template,
        getGroupedEntries(widget_content, entries),
    );
    insertRenderedActivitiesInDOM(rendered_activities, activities);

    loadTooltips();
    activities.classList.add("shown");
}

function getGroupedEntries(widget_content: HTMLElement, entries: Entry[]): { groups: Group[] } {
    moment.locale(widget_content.dataset.locale);

    const today_entries: Entry[] = [],
        yesterday_entries: Entry[] = [],
        recently_entries: Entry[] = [];

    const today = moment(),
        yesterday = moment().subtract(1, "day");

    const date_format_data = widget_content.dataset.dateFormat;
    if (!date_format_data) {
        throw new Error("No dateFormat dataset");
    }

    const date_time_format_data = widget_content.dataset.dateTimeFormat;
    if (!date_time_format_data) {
        throw new Error("No dateTimeFormat dataset");
    }

    const today_label = widget_content.dataset.today;

    if (!today_label) {
        throw new Error("No today dataset");
    }

    const yesterday_label = widget_content.dataset.yesterday;

    if (!yesterday_label) {
        throw new Error("No yesterday dataset");
    }

    const recently_label = widget_content.dataset.recently;

    if (!recently_label) {
        throw new Error("No recently dataset");
    }

    const datetime_format = formatFromPhpToMoment(date_time_format_data);
    const date_format = formatFromPhpToMoment(date_format_data);

    entries.forEach((entry) => {
        const updated_at = moment(entry.updated_at);

        if (updated_at.isSame(today, "day")) {
            entry.updated_at = updated_at.fromNow();
            today_entries.push(entry);
        } else if (updated_at.isSame(yesterday, "day")) {
            entry.updated_at = updated_at.format(datetime_format);
            yesterday_entries.push(entry);
        } else {
            entry.updated_at = updated_at.format(date_format);
            recently_entries.push(entry);
        }
    });

    return {
        groups: [
            {
                label: today_label,
                entries: today_entries,
            },
            {
                label: yesterday_label,
                entries: yesterday_entries,
            },
            {
                label: recently_label,
                entries: recently_entries,
            },
        ],
    };
}

function insertRenderedActivitiesInDOM(rendered_activities: string, parent_element: Element): void {
    const purified_activities = sanitize(rendered_activities, { RETURN_DOM_FRAGMENT: true });

    parent_element.appendChild(purified_activities);
}

function displayError(widget_content: HTMLElement): void {
    hideEverything(widget_content);
    widget_content
        .querySelector(".dashboard-widget-content-projectheartbeat-error")
        ?.classList.add("shown");
}

function displayEmptyState(
    widget_content: HTMLElement,
    json: { are_there_activities_user_cannot_see: boolean },
): void {
    hideEverything(widget_content);

    const empty_no_activity = widget_content.querySelector(
        ".dashboard-widget-content-projectheartbeat-empty-no-activity",
    );
    const empty_no_perms = widget_content.querySelector(
        ".dashboard-widget-content-projectheartbeat-empty-no-perms",
    );

    if (json.are_there_activities_user_cannot_see) {
        empty_no_perms?.classList.add("shown");
    } else {
        empty_no_activity?.classList.add("shown");
    }
    widget_content
        .querySelector(".dashboard-widget-content-projectheartbeat-empty")
        ?.classList.add("shown");
}

function hideEverything(widget_content: HTMLElement): void {
    [].forEach.call(widget_content.children, (child: HTMLElement) =>
        child.classList.remove("shown"),
    );
}
