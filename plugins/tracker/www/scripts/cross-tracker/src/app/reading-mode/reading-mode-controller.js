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

import { watch } from 'wrist';

export default class ReadingModeController {
    constructor(
        widget_content,
        report_mode,
        writing_cross_tracker_report,
        reading_cross_tracker_report,
        rest_querier,
        reading_trackers_controller,
        query_resut_controller,
        success_displayer,
        error_displayer
    ) {
        this.widget_content               = widget_content;
        this.report_mode                  = report_mode;
        this.writing_cross_tracker_report = writing_cross_tracker_report;
        this.reading_cross_tracker_report = reading_cross_tracker_report;
        this.rest_querier                 = rest_querier;
        this.reading_trackers_controller  = reading_trackers_controller;
        this.query_result_controller      = query_resut_controller;
        this.success_displayer            = success_displayer;
        this.error_displayer              = error_displayer;

        this.reading_mode                = this.widget_content.querySelector('.dashboard-widget-content-cross-tracker-reading-mode');
        this.reading_mode_trackers_empty = this.widget_content.querySelector('.dashboard-widget-content-cross-tracker-reading-mode-trackers-empty');
        this.reading_mode_fields         = this.widget_content.querySelector('.dashboard-widget-content-cross-tracker-reading-mode-fields');
        this.reading_mode_actions        = this.widget_content.querySelector('.dashboard-widget-content-cross-tracker-reading-mode-actions');
        this.reading_mode_save_report    = this.widget_content.querySelector('.dashboard-widget-content-cross-tracker-reading-mode-actions-save');
        this.reading_mode_cancel_report  = this.widget_content.querySelector('.dashboard-widget-content-cross-tracker-reading-mode-actions-cancel');

        this.translated_fetch_cross_tracker_report_message       = this.widget_content.querySelector('.reading-mode-fetch-error').textContent;
        this.translated_put_cross_tracker_report_message_success = this.widget_content.querySelector('.reading-mode-put-success').textContent;
        this.translated_put_cross_tracker_report_message_error   = this.widget_content.querySelector('.reading-mode-put-error').textContent;

        this.loadBackendReport().then(() => {
            this.listenEditClick();
            this.listenChangeMode();
            this.listenSaveReport();
            this.listenCancelReport();
        });
    }

    listenEditClick() {
        this.reading_mode_fields.addEventListener('click', () => {
            this.report_mode.switchToWritingMode();
        });
    }

    listenChangeMode() {
        const watcher = (property_name, old_value, new_value) => {
            if (new_value) {
                if (ReadingModeController.areDifferentMap(this.reading_cross_tracker_report.trackers, this.writing_cross_tracker_report.trackers)) {
                    this.showReportActions();
                } else {
                    this.hideReportActions();
                }
            }
        };

        watch(this.report_mode, 'reading_mode', watcher);
    }

    listenSaveReport() {
        this.reading_mode_save_report.addEventListener('click', () => {
            this.updateReport();
        });
    }

    listenCancelReport() {
        this.reading_mode_cancel_report.addEventListener('click', () => {
            this.hideReportActions();
            this.writing_cross_tracker_report.duplicateFromReadingReport(this.reading_cross_tracker_report);
            this.reading_trackers_controller.updateTrackersReading();
        });
    }

    static areDifferentMap(first_map, second_map) {
        if (first_map.size !== second_map.size) {
            return true;
        }
        for (const first_map_key of first_map.keys()) {
            if (! second_map.has(first_map_key)) {
                return true;
            }
        }
        return false;
    }

    async updateReport() {
        this.reading_cross_tracker_report.loaded = false;
        try {
            const tracker_ids  = [...this.writing_cross_tracker_report.trackers.keys()];
            const { trackers } = await this.rest_querier.updateReport(this.reading_cross_tracker_report.report_id, tracker_ids);

            this.resetAllListsOfTrackers();
            if (trackers) {
                this.initTrackers(trackers);
            }
            this.success_displayer.displaySuccess(this.translated_put_cross_tracker_report_message_success);
            this.hideReportActions();
            this.reading_cross_tracker_report.loaded = true;
        } catch (error) {
            this.error_displayer.displayError(this.translated_put_cross_tracker_report_message_error);
            throw error;
        }
    }

    showReportActions() {
        this.reading_mode_actions.classList.remove('cross-tracker-hide');
    }

    hideReportActions() {
        this.reading_mode_actions.classList.add('cross-tracker-hide');
    }

    resetAllListsOfTrackers() {
        this.reading_cross_tracker_report.clearTrackers();
        this.writing_cross_tracker_report.clearTrackers();
    }

    initTrackers(trackers) {
        this.reading_cross_tracker_report.initTrackers(trackers);
        this.writing_cross_tracker_report.duplicateFromReadingReport(this.reading_cross_tracker_report);
    }

    async loadBackendReport() {
        this.reading_cross_tracker_report.loaded = false;
        try {
            const { trackers } = await this.rest_querier.getReport(this.reading_cross_tracker_report.report_id);
            this.resetAllListsOfTrackers();
            if (trackers) {
                this.initTrackers(trackers);
            }
            this.reading_cross_tracker_report.loaded = true;
        } catch (error) {
            this.error_displayer.displayError(this.translated_fetch_cross_tracker_report_message);
            throw error;
        }
    }
}
