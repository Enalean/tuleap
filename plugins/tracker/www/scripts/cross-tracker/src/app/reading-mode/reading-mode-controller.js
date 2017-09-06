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
        tracker_selection,
        writing_cross_tracker_report,
        reading_cross_tracker_report,
        success_displayer,
        error_displayer
    ) {
        this.widget_content               = widget_content;
        this.tracker_selection            = tracker_selection;
        this.writing_cross_tracker_report = writing_cross_tracker_report;
        this.reading_cross_tracker_report = reading_cross_tracker_report;
        this.success_displayer            = success_displayer;
        this.error_displayer              = error_displayer;

        this.reading_mode                = this.widget_content.querySelector('.dashboard-widget-content-cross-tracker-reading-mode');
        this.reading_mode_trackers_empty = this.widget_content.querySelector('.dashboard-widget-content-cross-tracker-reading-mode-trackers-empty');
        this.reading_mode_fields         = this.widget_content.querySelector('.dashboard-widget-content-cross-tracker-reading-mode-fields');
        this.reading_mode_actions        = this.widget_content.querySelector('.dashboard-widget-content-cross-tracker-reading-mode-actions');
        this.writing_mode                = this.widget_content.querySelector('.dashboard-widget-content-cross-tracker-writing-mode');

        this.listenTrackersReadingLoaded();
    }

    listenTrackersReadingLoaded() {
        const watcher = () => {
            this.listenClick();
            this.listenChangeMode();
        };

        watch(this.reading_cross_tracker_report, 'trackers_loaded', watcher);
    }

    listenClick() {
        this.reading_mode_fields.addEventListener('click', () => {
            this.reading_cross_tracker_report.reading_mode = false;
            this.success_displayer.hideSuccess();
            this.error_displayer.hideError();
        });
    }

    listenChangeMode() {
        const watcher = (property_name, old_value, new_value) => {
            if (new_value) {
                this.reading_mode.classList.remove('cross-tracker-hide');
                this.writing_mode.classList.add('cross-tracker-hide');

                if (ReadingModeController.areDifferentMap(this.reading_cross_tracker_report.trackers, this.writing_cross_tracker_report.trackers)) {
                    this.reading_mode_actions.classList.remove('cross-tracker-hide');
                } else {
                    this.reading_mode_actions.classList.add('cross-tracker-hide');
                }
            }
        };

        watch(this.reading_cross_tracker_report, 'reading_mode', watcher);
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
}
