/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

import { get } from 'tlp';
import { render } from 'mustache';
import { sanitize } from 'dompurify';
import moment from 'moment';

export default init;

function init() {
    const heartbeat_widgets = document.querySelectorAll('.dashboard-widget-content-projectheartbeat-content');

    [].forEach.call(heartbeat_widgets, async (widget_content) => {
        try {
            const response = await get('/api/v1/projects/' + widget_content.dataset.projectId + '/heartbeats');
            const json     = await response.json();
            if (json.entries.length > 0) {
                displayActivities(widget_content, json.entries);
            } else {
                displayEmptyState(widget_content);
            }
        } catch (error) {
            displayError(widget_content);
        }
    });
}

function displayActivities(widget_content, entries) {
    hideEverything(widget_content);
    const activities = widget_content.querySelector('.dashboard-widget-content-projectheartbeat-activities');
    const template   = widget_content.querySelector('.dashboard-widget-content-projectheartbeat-placeholder').textContent;

    const rendered_activities = render(template, getGroupedEntries(widget_content, entries));
    insertRenderedActivitiesInDOM(rendered_activities, activities);

    activities.classList.add('shown');
}

function getGroupedEntries(widget_content, entries) {
    moment.locale(widget_content.dataset.locale);

    let today_entries     = [],
        yesterday_entries = [],
        recently_entries  = [];

    const today = moment(), yesterday = moment().subtract(1, 'day');

    entries.forEach((entry) => {
        const updated_at = moment(entry.updated_at);

        entry.updated_at = updated_at.fromNow();

        if (updated_at.isSame(today, 'day')) {
            today_entries.push(entry);
        } else if (updated_at.isSame(yesterday, 'day')) {
            yesterday_entries.push(entry);
        } else {
            recently_entries.push(entry);
        }
    });

    return {
        groups: [
            {
                label  : widget_content.dataset.today,
                entries: today_entries
            },
            {
                label: widget_content.dataset.yesterday,
                entries: yesterday_entries
            },
            {
                label: widget_content.dataset.recently,
                entries: recently_entries
            }
        ]
    };
}

function insertRenderedActivitiesInDOM(rendered_activities, parent_element) {
    const purified_activities = sanitize(rendered_activities, { RETURN_DOM_FRAGMENT: true });

    parent_element.appendChild(purified_activities);
}

function displayError(widget_content) {
    hideEverything(widget_content);
    widget_content.querySelector('.dashboard-widget-content-projectheartbeat-error').classList.add('shown');
}

function displayEmptyState(widget_content) {
    hideEverything(widget_content);
    widget_content.querySelector('.dashboard-widget-content-projectheartbeat-empty').classList.add('shown');
}

function hideEverything(widget_content) {
    [].forEach.call(widget_content.children, (child) => child.classList.remove('shown'));
}
