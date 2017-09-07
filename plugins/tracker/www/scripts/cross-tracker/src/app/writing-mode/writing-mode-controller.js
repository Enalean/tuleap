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

export default class WritingModeController {
    constructor(
        widget_content,
        report_mode,
        writing_cross_tracker_report,
        reading_cross_tracker_report,
        query_result_controller,
        tracker_selection,
        rest_querier,
        error_displayer,
        translated_fetch_artifacts_error_message
    ) {
        this.widget_content               = widget_content;
        this.report_mode                  = report_mode;
        this.reading_cross_tracker_report = reading_cross_tracker_report;
        this.writing_cross_tracker_report = writing_cross_tracker_report;
        this.query_result_controller      = query_result_controller;

        this.tracker_selection            = tracker_selection;
        this.rest_querier                 = rest_querier;
        this.error_displayer              = error_displayer;

        this.translated_fetch_artifacts_error_message = translated_fetch_artifacts_error_message;

        this.writing_mode_cancel = this.widget_content.querySelector('.dashboard-widget-content-cross-tracker-writing-mode-actions-cancel');
        this.writing_mode_search = this.widget_content.querySelector('.dashboard-widget-content-cross-tracker-writing-mode-actions-search');

        this.listenCancel();
        this.listenSearch();
    }

    listenCancel() {
        this.writing_mode_cancel.addEventListener('click', () => {
            this.writing_cross_tracker_report.clearTrackers();
            this.writing_cross_tracker_report.duplicateFromReadingReport(this.reading_cross_tracker_report);
            this.changeMode();
        });
    }

    listenSearch() {
        this.writing_mode_search.addEventListener('click', () => {
            this.changeMode();
        });
    }

    changeMode() {
        this.report_mode.switchToReadingMode();
    }
}
