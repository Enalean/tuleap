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

import { get, put } from 'tlp';
import { render } from 'mustache';
import { watch } from 'wrist';
import reading_trackers_template from './reading-trackers.mustache';
import ReadingModeController from './reading-mode-controller.js';

export default class TrackerLoaderController {
    constructor(
        widget_content,
        tracker_selection,
        writing_cross_tracker_report,
        reading_cross_tracker_report,
        loader_displayer,
        success_displayer,
        error_displayer
    ) {
        this.widget_content               = widget_content;
        this.tracker_selection            = tracker_selection;
        this.writing_cross_tracker_report = writing_cross_tracker_report;
        this.reading_cross_tracker_report = reading_cross_tracker_report;
        this.loader_displayer             = loader_displayer;
        this.success_displayer            = success_displayer;
        this.error_displayer              = error_displayer;

        this.reading_mode_trackers       = this.widget_content.querySelector('.dashboard-widget-content-cross-tracker-reading-mode-trackers');
        this.reading_mode_trackers_empty = this.widget_content.querySelector('.dashboard-widget-content-cross-tracker-reading-mode-trackers-empty');
        this.reading_mode_fields         = this.widget_content.querySelector('.dashboard-widget-content-cross-tracker-reading-mode-fields');
        this.reading_mode_save_report    = this.widget_content.querySelector('.dashboard-widget-content-cross-tracker-reading-mode-actions-save');
        this.reading_mode_cancel_report  = this.widget_content.querySelector('.dashboard-widget-content-cross-tracker-reading-mode-actions-cancel');
        this.reading_mode_actions        = this.widget_content.querySelector('.dashboard-widget-content-cross-tracker-reading-mode-actions');

        this.translated_fetch_cross_tracker_report_message       = this.widget_content.querySelector('.reading-mode-fetch-error').textContent;
        this.translated_put_cross_tracker_report_message_success = this.widget_content.querySelector('.reading-mode-put-success').textContent;
        this.translated_put_cross_tracker_report_message_error   = this.widget_content.querySelector('.reading-mode-put-error').textContent;

        this.loadTrackersReport().then(() => {
            this.reading_cross_tracker_report.trackers_loaded = true;
            this.listenSaveReport();
            this.listenCancelReport();
            this.listenChangeMode();
        });
    }

    listenChangeMode() {
        const watcher = (property_name, old_value, new_value) => {
            if (new_value) {
                if (ReadingModeController.areDifferentMap(this.reading_cross_tracker_report.trackers, this.writing_cross_tracker_report.trackers)) {
                    this.updateTrackersReading();
                }
            }
        };

        watch(this.reading_cross_tracker_report, 'reading_mode', watcher);
    }

    listenSaveReport() {
        this.reading_mode_save_report.addEventListener('click', () => {
            this.updateReport();
        });
    }

    listenCancelReport() {
        this.reading_mode_cancel_report.addEventListener('click', () => {
            this.reading_mode_actions.classList.add('cross-tracker-hide');
            this.writing_cross_tracker_report.clearTrackers();
            this.writing_cross_tracker_report.duplicateFromReadingReport(this.reading_cross_tracker_report);
            this.updateTrackersReading();
        });
    }

    displayReadingTrackers(trackers) {
        this.reading_mode_trackers.insertAdjacentHTML('beforeEnd', render(reading_trackers_template, trackers));
    }

    removeTrackersReading() {
        while(this.reading_mode_trackers.hasChildNodes()) {
            this.reading_mode_trackers.removeChild(this.reading_mode_trackers.lastChild);
        }
    }

    resetAllListsOfTrackers() {
        this.reading_cross_tracker_report.clearTrackers();
        this.writing_cross_tracker_report.clearTrackers();
    }

    initTrackers(trackers) {
        this.reading_cross_tracker_report.initTrackers(trackers);
        this.writing_cross_tracker_report.duplicateFromReadingReport(this.reading_cross_tracker_report);
    }

    updateTrackersReading() {
        if (this.writing_cross_tracker_report.trackers.size <= 0) {
            this.reading_mode_trackers_empty.classList.remove('cross-tracker-hide');
        } else {
            this.reading_mode_trackers_empty.classList.add('cross-tracker-hide');
        }

        this.removeTrackersReading();

        const trackers = { selected_trackers: [] };
        for (const {tracker, project} of this.writing_cross_tracker_report.trackers.values()) {
            trackers.selected_trackers.push(
                {
                    tracker_id   : tracker.id,
                    tracker_label: tracker.label,
                    project_label: project.label
                }
            )
        }
        this.displayReadingTrackers(trackers);
    }

    setDisabled() {
        this.reading_mode_trackers_empty.classList.remove('cross-tracker-hide');
        this.reading_mode_fields.classList.add('reading-mode-disabled');
    }

    async loadTrackersReport() {
        try {
            this.loader_displayer.show();
            const response     = await get('/api/v1/cross_tracker_reports/' + this.reading_cross_tracker_report.report_id);
            const { trackers } = await response.json();
            this.resetAllListsOfTrackers();
            if (trackers) {
                this.initTrackers(trackers);
            }
            this.updateTrackersReading();
        } catch (error) {
            this.setDisabled();
            this.error_displayer.displayError(this.translated_fetch_cross_tracker_report_message);
        } finally {
            this.loader_displayer.hide();
        }
    }

    async updateReport() {
        try {
            const response = await put('/api/v1/cross_tracker_reports/' + this.reading_cross_tracker_report.report_id, {
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({trackers_id: [...this.writing_cross_tracker_report.trackers.keys()]})
            });
            const { trackers } = await response.json();
            this.resetAllListsOfTrackers();
            if (trackers) {
                this.initTrackers(trackers);
            }
            this.updateTrackersReading();
            this.success_displayer.displaySuccess(this.translated_put_cross_tracker_report_message_success);
            this.reading_mode_actions.classList.add('cross-tracker-hide');
        } catch (error) {
            this.error_displayer.displayError(this.translated_put_cross_tracker_report_message_error);
        }
    }
}
