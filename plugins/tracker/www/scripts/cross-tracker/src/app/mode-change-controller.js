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

export default class ModeChangeController {
    constructor(
        widget_content,
        report_mode,
        success_displayer,
        error_displayer
    ) {
        this.widget_content    = widget_content;
        this.report_mode       = report_mode;
        this.success_displayer = success_displayer;
        this.error_displayer   = error_displayer;

        this.reading_mode_view = this.widget_content.querySelector('.dashboard-widget-content-cross-tracker-reading-mode');
        this.writing_mode_view = this.widget_content.querySelector('.dashboard-widget-content-cross-tracker-writing-mode');

        this.listenChangeMode();
    }

    listenChangeMode() {
        const watcher = (property_name, old_value, new_value) => {
            if (new_value === true) {
                this.switchToReadingView();
                this.hideFeedbacks();
            } else {
                this.switchToWritingView();
                this.hideFeedbacks();
            }
        };

        watch(this.report_mode, 'reading_mode', watcher);
    }

    switchToReadingView() {
        this.reading_mode_view.classList.remove('cross-tracker-hide');
        this.writing_mode_view.classList.add('cross-tracker-hide');
    }

    switchToWritingView() {
        this.reading_mode_view.classList.add('cross-tracker-hide');
        this.writing_mode_view.classList.remove('cross-tracker-hide');
    }

    hideFeedbacks() {
        this.success_displayer.hideSuccess();
        this.error_displayer.hideError();
    }
}
