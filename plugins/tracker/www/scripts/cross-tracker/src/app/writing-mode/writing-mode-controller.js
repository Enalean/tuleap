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

export default class WritingModeController {
    constructor(
        widget_content,
        writing_cross_tracker_report,
        reading_cross_tracker_report,
        tracker_selection,
        success_displayer,
        error_displayer
    ) {
        this.widget_content               = widget_content;
        this.reading_cross_tracker_report = reading_cross_tracker_report;
        this.writing_cross_tracker_report = writing_cross_tracker_report;
        this.tracker_selection            = tracker_selection;
        this.success_displayer            = success_displayer;
        this.error_displayer              = error_displayer;

        this.reading_mode        = this.widget_content.querySelector('.dashboard-widget-content-cross-tracker-reading-mode');
        this.writing_mode        = this.widget_content.querySelector('.dashboard-widget-content-cross-tracker-writing-mode');
        this.writing_mode_cancel = this.widget_content.querySelector('.dashboard-widget-content-cross-tracker-writing-mode-actions-cancel');
        this.writing_mode_search = this.widget_content.querySelector('.dashboard-widget-content-cross-tracker-writing-mode-actions-search');

        this.listenCancel();
        this.listenSearch();
        this.listenChangeMode();
    }

    listenChangeMode() {
        const updateMode = (property_name, old_value, new_value) => {
            if (! new_value) {
                this.reading_mode.classList.add('cross-tracker-hide');
                this.writing_mode.classList.remove('cross-tracker-hide');
            }
        };

        watch(this.reading_cross_tracker_report, 'reading_mode', updateMode);
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
        this.reading_cross_tracker_report.reading_mode = true;
        this.success_displayer.hideSuccess();
        this.error_displayer.hideError();
    }
}
