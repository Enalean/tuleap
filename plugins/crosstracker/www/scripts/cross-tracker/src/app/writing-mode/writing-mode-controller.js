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
        report_saved_state,
        writing_cross_tracker_report,
        reading_cross_tracker_report,
        query_result_controller
    ) {
        this.widget_content               = widget_content;
        this.report_mode                  = report_mode;
        this.report_saved_state           = report_saved_state;
        this.writing_cross_tracker_report = writing_cross_tracker_report;
        this.reading_cross_tracker_report = reading_cross_tracker_report;
        this.query_result_controller      = query_result_controller;

        this.writing_mode_cancel = this.widget_content.querySelector('.dashboard-widget-content-cross-tracker-writing-mode-actions-cancel');
        this.writing_mode_search = this.widget_content.querySelector('.dashboard-widget-content-cross-tracker-writing-mode-actions-search');
    }

    init() {
        this.listenCancel();
        this.listenSearch();
    }

    listenCancel() {
        this.writing_mode_cancel.addEventListener('click', () => {
            this.writing_cross_tracker_report.duplicateFromReport(this.reading_cross_tracker_report);
            this.report_saved_state.switchToSavedState();
            this.changeMode();
        });
    }

    listenSearch() {
        this.writing_mode_search.addEventListener('click', () => {
            this.reading_cross_tracker_report.duplicateFromReport(this.writing_cross_tracker_report);
            this.report_saved_state.switchToUnsavedState();
            this.changeMode();
            this.query_result_controller.loadFirstBatchOfArtifacts();
        });
    }

    changeMode() {
        this.report_mode.switchToReadingMode();
    }
}
