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
import reading_trackers_template from './reading-trackers.mustache';
import ReadingModeController from './reading-mode-controller.js';

export default class ReadingTrackersController {
    constructor(
        widget_content,
        tracker_selection,
        report_mode,
        writing_cross_tracker_report,
        reading_cross_tracker_report
    ) {
        this.widget_content               = widget_content;
        this.tracker_selection            = tracker_selection;
        this.report_mode                  = report_mode;
        this.writing_cross_tracker_report = writing_cross_tracker_report;
        this.reading_cross_tracker_report = reading_cross_tracker_report;

        this.reading_mode_trackers       = this.widget_content.querySelector('.dashboard-widget-content-cross-tracker-reading-mode-trackers');
        this.reading_mode_trackers_empty = this.widget_content.querySelector('.dashboard-widget-content-cross-tracker-reading-mode-trackers-empty');
        this.reading_mode_fields         = this.widget_content.querySelector('.dashboard-widget-content-cross-tracker-reading-mode-fields');

        this.listenChangeMode();
        this.listenReportLoaded();
    }

    listenChangeMode() {
        const watcher = (property_name, old_value, new_value) => {
            if (
                new_value
                && this.reading_cross_tracker_report.loaded === true
                && ReadingModeController.areDifferentMap(this.reading_cross_tracker_report.trackers, this.writing_cross_tracker_report.trackers)
            ) {
                this.updateTrackersReading();
            }
        };

        watch(this.report_mode, 'reading_mode', watcher);
    }

    listenReportLoaded() {
        const watcher = (property_name, old_value, new_value) => {
            if (new_value) {
                this.updateTrackersReading();
                this.setEnabled();
            } else {
                this.setDisabled();
            }
        };

        watch(this.reading_cross_tracker_report, 'loaded', watcher);
    }

    displayReadingTrackers(trackers) {
        this.reading_mode_trackers.insertAdjacentHTML('beforeEnd', render(reading_trackers_template, trackers));
    }

    removeTrackersReading() {
        while(this.reading_mode_trackers.hasChildNodes()) {
            this.reading_mode_trackers.removeChild(this.reading_mode_trackers.lastChild);
        }
    }

    updateTrackersReading() {
        if (this.writing_cross_tracker_report.areTrackersEmpty()) {
            this.reading_mode_trackers_empty.classList.remove('cross-tracker-hide');
        } else {
            this.reading_mode_trackers_empty.classList.add('cross-tracker-hide');
        }

        this.removeTrackersReading();

        const trackers = { selected_trackers: []};
        for (const { tracker, project } of this.writing_cross_tracker_report.getTrackers()) {
            trackers.selected_trackers.push(
                {
                    tracker_id   : tracker.id,
                    tracker_label: tracker.label,
                    project_label: project.label
                }
            );
        }
        this.displayReadingTrackers(trackers);
    }

    setEnabled() {
        this.reading_mode_fields.classList.remove('reading-mode-disabled');
    }

    setDisabled() {
        this.reading_mode_trackers_empty.classList.remove('cross-tracker-hide');
        this.reading_mode_fields.classList.add('reading-mode-disabled');
    }
}
