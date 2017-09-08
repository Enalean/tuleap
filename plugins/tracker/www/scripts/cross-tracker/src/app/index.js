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

import ReadingTrackersController from './reading-mode/reading-trackers-controller.js';
import ReadingModeController from './reading-mode/reading-mode-controller.js';
import WritingModeController from './writing-mode/writing-mode-controller.js';
import ProjectSelector from './writing-mode/project-selector.js';
import TrackerSelector from './writing-mode/tracker-selector.js';
import TrackerSelection from './writing-mode/tracker-selection.js';
import TrackerSelectionController from './writing-mode/tracker-selection-controller.js';
import QueryResultController from './query-result-controller.js';
import ReadingCrossTrackerReport from './reading-mode/reading-cross-tracker-report.js';
import WritingCrossTrackerReport from './writing-mode/writing-cross-tracker-report.js';
import UserLocaleStore from './user-locale-store.js';
import SuccessDisplayer from './rest-success-displayer.js';
import ErrorDisplayer from './rest-error-displayer.js';
import LoaderDisplayer from './loader-displayer.js';
import RestQuerier from './rest-querier.js';
import ModeChangeController from './mode-change-controller.js';
import ReportMode from './report-mode.js';

document.addEventListener('DOMContentLoaded', function () {
    const widget_cross_tracker_elements = document.getElementsByClassName('dashboard-widget-content-cross-tracker');

    for (const widget_element of widget_cross_tracker_elements) {
        const report_id = widget_element.dataset.reportId;
        const locale    = widget_element.dataset.locale;
        const localized_php_date_format = widget_element.dataset.dateFormat;

        const tracker_selection            = new TrackerSelection();
        const report_mode                  = new ReportMode();
        const reading_cross_tracker_report = new ReadingCrossTrackerReport(report_id);
        const writing_cross_tracker_report = new WritingCrossTrackerReport();
        const success_displayer            = new SuccessDisplayer(widget_element);
        const error_displayer              = new ErrorDisplayer(widget_element);
        const loader_displayer             = new LoaderDisplayer(widget_element);
        const user_locale_store            = new UserLocaleStore(locale, localized_php_date_format);
        const rest_querier                 = new RestQuerier(loader_displayer);

        const translated_fetch_artifacts_error_message = widget_element.querySelector('.query-fetch-error').textContent;

        const query_result_controller = new QueryResultController(
            widget_element,
            reading_cross_tracker_report,
            user_locale_store,
            rest_querier,
            error_displayer,
            translated_fetch_artifacts_error_message
        );

        const reading_trackers_controller = new ReadingTrackersController(
            widget_element,
            tracker_selection,
            report_mode,
            writing_cross_tracker_report,
            reading_cross_tracker_report
        );

        new ModeChangeController(
            widget_element,
            report_mode,
            success_displayer,
            error_displayer
        );

        new ReadingModeController(
            widget_element,
            report_mode,
            writing_cross_tracker_report,
            reading_cross_tracker_report,
            rest_querier,
            reading_trackers_controller,
            query_result_controller,
            success_displayer,
            error_displayer
        );

        new WritingModeController(
            widget_element,
            report_mode,
            writing_cross_tracker_report,
            reading_cross_tracker_report,
            query_result_controller,
            tracker_selection,
            rest_querier,
            error_displayer,
            translated_fetch_artifacts_error_message
        );

        new ProjectSelector(
            widget_element,
            tracker_selection,
            report_mode,
            rest_querier,
            error_displayer
        );

        const tracker_selector = new TrackerSelector(
            widget_element,
            tracker_selection,
            writing_cross_tracker_report,
            rest_querier,
            error_displayer
        );

        new TrackerSelectionController(
            widget_element,
            tracker_selection,
            writing_cross_tracker_report,
            reading_cross_tracker_report,
            tracker_selector
        );
    }
});
