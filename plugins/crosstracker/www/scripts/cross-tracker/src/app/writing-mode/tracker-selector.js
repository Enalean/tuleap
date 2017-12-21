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

import { render }               from 'mustache';
import { watch }                from 'wrist';
import { getTrackersOfProject } from '../rest-querier.js';
import tracker_option_template  from './tracker-option.mustache';
import { gettext_provider }     from '../gettext-provider.js';

export default class TrackerSelector {
    constructor(
        widget_content,
        tracker_selection,
        writing_cross_tracker_report,
        error_displayer,
        tracker_selection_loader_displayer,
    ) {
        this.widget_content               = widget_content;
        this.tracker_selection            = tracker_selection;
        this.writing_cross_tracker_report = writing_cross_tracker_report;
        this.error_displayer              = error_displayer;
        this.loader_displayer             = tracker_selection_loader_displayer;

        this.trackers = new Map();
    }

    init() {
        this.form_trackers  = this.widget_content.querySelector('.dashboard-widget-content-cross-tracker-form-trackers');
        this.trackers_input = this.widget_content.querySelector('.dashboard-widget-content-cross-tracker-form-trackers-input');

        this.disableSelect();
        this.listenProjectChange();
        this.listenTrackerChange();
        this.listenInputChange();
        this.listenTrackersUpdated();
    }

    async loadTrackers(project_id) {
        try {
            this.loader_displayer.show();
            const trackers = await getTrackersOfProject(project_id);
            for (const {id, label} of trackers) {
                const is_already_selected = this.writing_cross_tracker_report.hasTrackerWithId(id);
                this.trackers.set(id, {
                    id,
                    label,
                    disabled: is_already_selected
                });
            }
        } catch (error) {
            this.error_displayer.displayError(gettext_provider.gettext('Error while fetching the list of trackers of this project'));
            throw error;
        } finally {
            this.loader_displayer.hide();
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
        const trackers            = [...this.trackers.values()];
        const please_choose_label = gettext_provider.gettext('Please choose...');

        this.clearOptions();
        this.trackers_input.insertAdjacentHTML('beforeEnd', render(tracker_option_template, {
            please_choose_label,
            trackers
        }));
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
