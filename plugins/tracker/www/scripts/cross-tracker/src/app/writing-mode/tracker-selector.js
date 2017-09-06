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

import { recursiveGet } from 'tlp';
import { render } from 'mustache';
import { watch } from 'wrist';

export default class TrackerSelector {
    constructor(
        widget_content,
        tracker_selection,
        writing_cross_tracker_report,
        error_displayer,
        loader_displayer
    ) {
        this.widget_content               = widget_content;
        this.tracker_selection            = tracker_selection;
        this.writing_cross_tracker_report = writing_cross_tracker_report;
        this.error_displayer              = error_displayer;
        this.loader_displayer             = loader_displayer;
        this.form_trackers                = this.widget_content.querySelector('.dashboard-widget-content-cross-tracker-form-trackers');
        this.trackers_input               = this.widget_content.querySelector('.dashboard-widget-content-cross-tracker-form-trackers-input');
        this.trackers                     = new Map();
        this.translated_too_many_trackers_message = this.widget_content.querySelector('.tracker-selector-error').textContent;

        this.disableSelect();
        this.listenProjectChange();
        this.listenTrackerChange();
        this.listenInputChange();
        this.listenTrackersUpdated();
    }

    async loadTrackers(project_id) {
        try {
            this.loader_displayer.show();
            const json = await recursiveGet('/api/v1/projects/' + project_id + '/trackers', {
                params: {
                    limit: 50
                }
            });
            for (const {id, label} of json) {
                const is_already_selected = this.writing_cross_tracker_report.hasTrackerWithId(id);
                this.trackers.set(id, {
                    id,
                    label,
                    disabled: is_already_selected
                });
            }
        } catch (error) {
            this.error_displayer.displayError(this.translated_too_many_trackers_message);
        }
    }

    enableOption(tracker_id_to_enable) {
        const tracker    = this.trackers.get(tracker_id_to_enable);
        if (tracker) {
            tracker.disabled = false;
        }
        this.displayOptions();
    }

    disableSelect() {
        this.trackers_input.disabled = true;
        this.form_trackers.classList.add('tlp-form-element-disabled');
    }

    enableSelect() {
        this.trackers_input.disabled = false;
        this.form_trackers.classList.remove('tlp-form-element-disabled');
    }

    displayOptions() {
        const trackers = [...this.trackers.values()];
        const template = this.widget_content.querySelector('.dashboard-widget-content-cross-tracker-form-trackers-placeholder').textContent;

        this.clearOptions();
        this.trackers_input.insertAdjacentHTML('beforeEnd', render(template, { trackers }));
    }

    clearOptions() {
        [...this.trackers_input.children].forEach((child) => child.remove());
    }

    updateOptions() {
        this.clearOptions();

        for (const tracker of this.trackers.values()) {
            tracker.disabled = this.writing_cross_tracker_report.hasTrackerWithId(tracker.id);
        }

        this.displayOptions();
    }

    listenProjectChange() {
        const projectChanged = (property_name, old_value, new_value) => {
            this.tracker_selection.clearTrackerSelection();
            this.trackers.clear();

            this.loadTrackers(new_value.id).then(() => {
                this.updateOptions();
                this.enableSelect();
                this.loader_displayer.hide();
            });
        };

        watch(this.tracker_selection, 'selected_project', projectChanged);
    }

    listenTrackerChange() {
        const trackerChanged = (property_name, old_value, new_value) => {
            if (new_value === null) {
                this.trackers_input.value = '';
            }
        };

        watch(this.tracker_selection, 'selected_tracker', trackerChanged);
    }

    listenInputChange() {
        const inputChanged = (property_name, old_value, new_value) => {
            if (new_value === '') {
                this.tracker_selection.clearTrackerSelection();
                return;
            }
            this.tracker_selection.selected_tracker = this.trackers.get(parseInt(new_value, 10));
        };

        watch(this.trackers_input, 'value', inputChanged);
    }

    listenTrackersUpdated() {
        const watcher = () => {
            this.updateOptions();
        };
        watch(this.writing_cross_tracker_report, 'number_of_tracker', watcher);
    }
}
