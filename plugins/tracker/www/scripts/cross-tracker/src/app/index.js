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

import TrackerLoaderController from './reading-mode/tracker-loader-controller.js';
import ReadingModeController from './reading-mode/reading-mode-controller.js';
import WritingModeController from './writing-mode/writing-mode-controller.js';
import ProjectSelector from './writing-mode/project-selector.js';
import TrackerSelector from './writing-mode/tracker-selector.js';
import TrackerSelection from './writing-mode/tracker-selection.js';
import TrackerSelectionController from './writing-mode/tracker-selection-controller.js';
import ReadingCrossTrackerReport from './reading-mode/reading-cross-tracker-report.js';
import WritingCrossTrackerReport from './writing-mode/writing-cross-tracker-report.js';
import SuccessDisplayer from './rest-success-displayer.js';
import ErrorDisplayer from './rest-error-displayer.js';
import LoaderDisplayer from './loader-displayer.js';

document.addEventListener('DOMContentLoaded', function () {
    const widget_cross_tracker_elements = document.getElementsByClassName('dashboard-widget-content-cross-tracker');

    for (const widget_element of widget_cross_tracker_elements) {
        const report_id = widget_element.dataset.reportId;

        const tracker_selection            = new TrackerSelection();
        const reading_cross_tracker_report = new ReadingCrossTrackerReport(report_id);
        const writing_cross_tracker_report = new WritingCrossTrackerReport();
        const success_displayer            = new SuccessDisplayer(widget_element);
        const error_displayer              = new ErrorDisplayer(widget_element);
        const loader_displayer             = new LoaderDisplayer(widget_element);

        new TrackerLoaderController(
            widget_element,
            tracker_selection,
            writing_cross_tracker_report,
            reading_cross_tracker_report,
            loader_displayer,
            success_displayer,
            error_displayer
        );
        new ReadingModeController(
            widget_element,
            tracker_selection,
            writing_cross_tracker_report,
            reading_cross_tracker_report,
            success_displayer,
            error_displayer
        );
        new WritingModeController(
            widget_element,
            writing_cross_tracker_report,
            reading_cross_tracker_report,
            tracker_selection,
            success_displayer,
            error_displayer
        );
        new ProjectSelector(
            widget_element,
            reading_cross_tracker_report,
            tracker_selection,
            error_displayer,
            loader_displayer
        );
        const tracker_selector = new TrackerSelector(
            widget_element,
            tracker_selection,
            writing_cross_tracker_report,
            error_displayer,
            loader_displayer
        );
        new TrackerSelectionController(
            widget_element,
            tracker_selection,
            writing_cross_tracker_report,
            reading_cross_tracker_report,
            error_displayer,
            tracker_selector
        );
    }
});
