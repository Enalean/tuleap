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

import { render } from 'mustache';
import { watch } from 'wrist';
import { TooManyTrackersSelectedError } from "./cross-tracker-report.js";
import selected_tracker_template from './selected-tracker.mustache';

export default class TrackerSelectionController {
    constructor(
        widget_content,
        tracker_selection,
        cross_tracker_report,
        error_displayer,
        tracker_selector
    ) {
        this.widget_content          = widget_content;
        this.tracker_selection       = tracker_selection;
        this.cross_tracker_report    = cross_tracker_report;
        this.error_displayer         = error_displayer;
        this.tracker_selector        = tracker_selector;
        this.form_trackers_selected  = this.widget_content.querySelector('.dashboard-widget-content-cross-tracker-form-trackers-selected');
        this.add_tracker_button      = this.widget_content.querySelector('.dashboard-widget-content-cross-tracker-form-trackers-add');
        this.too_many_trackers_error = this.widget_content.querySelector('.dashboard-widget-content-cross-tracker-too-many-selected-error');

        this.setDisabled();
        this.listenTrackerAdd();
        this.listenTrackerChange();
    }

    setDisabled() {
        this.add_tracker_button.disabled = true;
    }

    setEnabled() {
        this.add_tracker_button.disabled = false;
    }

    displaySelectedTrackers(trackers) {
        this.form_trackers_selected.insertAdjacentHTML('beforeEnd', render(selected_tracker_template, trackers));
        this.listenTrackerRemove();
    }

    listenTrackerChange() {
        const watcher = (property_name, old_value, new_value) => {
            if (! new_value) {
                this.setDisabled();
            } else {
                this.setEnabled();
            }
        };

        watch(this.tracker_selection, 'selected_tracker', watcher);
    }

    listenTrackerAdd() {
        this.add_tracker_button.addEventListener('click', () => {
            const { selected_tracker, selected_project } = this.tracker_selection;
            try {
                this.cross_tracker_report.addTracker(selected_project, selected_tracker);
                this.displaySelectedTrackers({
                    selected_trackers: [
                        {
                            tracker_id   : selected_tracker.id,
                            tracker_label: selected_tracker.label,
                            project_label: selected_project.label
                        }
                    ]
                });
                this.tracker_selection.clearTrackerSelection();
                this.tracker_selector.disableOption(selected_tracker);
            } catch (error) {
                if (error instanceof TooManyTrackersSelectedError) {
                    this.too_many_trackers_error.classList.add('shown');
                }
            }
        });
    }

    listenTrackerRemove() {
        const selected_tracker_remove_icons = this.widget_content.querySelectorAll('.dashboard-widget-content-cross-tracker-remove-tracker');
        for (const icon of selected_tracker_remove_icons) {
            icon.addEventListener('click', (event) => {
                const icon_clicked = event.target;
                const tracker_id   = parseInt(icon_clicked.dataset.trackerId, 10);
                this.cross_tracker_report.removeTracker(tracker_id);
                icon_clicked.closest('.dashboard-widget-content-cross-tracker-selected-tracker').remove();
                this.too_many_trackers_error.classList.remove('shown');
                this.tracker_selector.enableOption(tracker_id);
                return false;
            });
        }
    }
}
