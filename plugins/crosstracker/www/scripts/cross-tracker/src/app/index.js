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

import Vue from 'vue';

import { gettext_provider }            from './gettext-provider.js';
import { init as initUser }            from './user-service.js';
import ReadingModeController           from './reading-mode/reading-mode-controller.js';
import WritingModeController           from './writing-mode/writing-mode-controller.js';
import ProjectSelector                 from './writing-mode/project-selector.js';
import TrackerSelector                 from './writing-mode/tracker-selector.js';
import TrackerSelection                from './writing-mode/tracker-selection.js';
import TrackerSelectionController      from './writing-mode/tracker-selection-controller.js';
import TrackerSelectionLoaderDisplayer from './writing-mode/tracker-selection-loader-displayer.js';
import QueryResultController           from './query-result-controller.js';
import ReadingCrossTrackerReport       from './reading-mode/reading-cross-tracker-report.js';
import WritingCrossTrackerReport       from './writing-mode/writing-cross-tracker-report.js';
import User                            from './user.js';
import SuccessDisplayer                from './rest-success-displayer.js';
import ErrorDisplayer                  from './rest-error-displayer.js';
import WidgetLoaderDisplayer           from './widget-loader-displayer.js';
import ModeChangeController            from './mode-change-controller.js';
import ReportMode                      from './report-mode.js';
import BackendCrossTrackerReport       from './backend-cross-tracker-report.js';
import ReportSavedState                from './report-saved-state.js';
import CrossTrackerWidget              from './CrossTrackerWidget.vue';
import QueryEditor                     from './writing-mode/QueryEditor.vue';

document.addEventListener('DOMContentLoaded', function () {
    const widget_cross_tracker_elements = document.getElementsByClassName('dashboard-widget-content-cross-tracker');

    const Widget         = Vue.extend(CrossTrackerWidget);
    const VueQueryEditor = Vue.extend(QueryEditor);

    for (const widget_element of widget_cross_tracker_elements) {
        const report_id                 = widget_element.dataset.reportId;
        const locale                    = widget_element.dataset.locale;
        const localized_php_date_format = widget_element.dataset.dateFormat;
        const is_anonymous              = (widget_element.dataset.isAnonymous == 'true');

        gettext_provider.setLocale(locale);
        initUser(is_anonymous);

        const tracker_selection            = new TrackerSelection();
        const report_mode                  = new ReportMode();
        const report_saved_state           = new ReportSavedState();
        const backend_cross_tracker_report = new BackendCrossTrackerReport(report_id);
        const reading_cross_tracker_report = new ReadingCrossTrackerReport();
        const writing_cross_tracker_report = new WritingCrossTrackerReport();
        const user                         = new User(
            locale,
            localized_php_date_format,
            is_anonymous
        );

        const vue_mount_point = widget_element.querySelector('.vue-mount-point');

        new Widget({
            propsData: {
                backendCrossTrackerReport: backend_cross_tracker_report,
                readingCrossTrackerReport: reading_cross_tracker_report,
                writingCrossTrackerReport: writing_cross_tracker_report,
                reportMode               : report_mode
            }
        }).$mount(vue_mount_point);

        const success_displayer                  = new SuccessDisplayer(widget_element);
        const error_displayer                    = new ErrorDisplayer(widget_element);
        const widget_loader_displayer            = new WidgetLoaderDisplayer(widget_element);
        const tracker_selection_loader_displayer = new TrackerSelectionLoaderDisplayer(widget_element);

        const query_editor_element = widget_element.querySelector('.cross-tracker-query-editor');
        new VueQueryEditor({
            propsData: {
                writingCrossTrackerReport: writing_cross_tracker_report
            }
        }).$mount(query_editor_element);

        const query_result_controller = new QueryResultController(
            widget_element,
            backend_cross_tracker_report,
            writing_cross_tracker_report,
            report_saved_state,
            user,
            error_displayer,
            gettext_provider
        );

        const reading_controller = new ReadingModeController(
            widget_element,
            report_mode,
            report_saved_state,
            backend_cross_tracker_report,
            writing_cross_tracker_report,
            reading_cross_tracker_report,
            query_result_controller,
            user,
            widget_loader_displayer,
            success_displayer,
            error_displayer,
            gettext_provider
        );

        const writing_controller = new WritingModeController(
            widget_element,
            report_mode,
            report_saved_state,
            writing_cross_tracker_report,
            reading_cross_tracker_report,
            query_result_controller
        );

        const project_selector = new ProjectSelector(
            widget_element,
            tracker_selection,
            user,
            error_displayer,
            tracker_selection_loader_displayer,
            gettext_provider
        );

        const mode_change_controller = new ModeChangeController(
            widget_element,
            report_mode,
            reading_controller,
            project_selector,
            success_displayer,
            error_displayer
        );

        const tracker_selector = new TrackerSelector(
            widget_element,
            tracker_selection,
            writing_cross_tracker_report,
            error_displayer,
            tracker_selection_loader_displayer,
            gettext_provider
        );

        new TrackerSelectionController(
            widget_element,
            tracker_selection,
            writing_cross_tracker_report,
            backend_cross_tracker_report,
            tracker_selector,
            error_displayer,
            gettext_provider
        );

        mode_change_controller.init();
        reading_controller.init();
        writing_controller.init();
        query_result_controller.init();
        project_selector.init();
    }
});
