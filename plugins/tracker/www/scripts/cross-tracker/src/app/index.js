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

import ProjectSelector from './project-selector.js';
import TrackerSelector from './tracker-selector.js';
import TrackerSelection from './tracker-selection.js';
import TrackerSelectionController from './tracker-selection-controller.js';
import CrossTrackerReport from './cross-tracker-report.js';
import ErrorDisplayer from './rest-error-displayer.js';
import LoaderDisplayer from './loader-displayer.js';

document.addEventListener('DOMContentLoaded', function () {
    const widget_cross_tracker_elements = document.getElementsByClassName('dashboard-widget-content-cross-tracker');

    for (const widget_element of widget_cross_tracker_elements) {
        const tracker_selection    = new TrackerSelection();
        const cross_tracker_report = new CrossTrackerReport();
        const error_displayer      = new ErrorDisplayer(widget_element);
        const loader_displayer     = new LoaderDisplayer(widget_element);

        new ProjectSelector(
            widget_element,
            tracker_selection,
            error_displayer,
            loader_displayer
        );
        const tracker_selector = new TrackerSelector(
            widget_element,
            tracker_selection,
            cross_tracker_report,
            error_displayer,
            loader_displayer
        );
        new TrackerSelectionController(
            widget_element,
            tracker_selection,
            cross_tracker_report,
            error_displayer,
            tracker_selector
        );
    }
});
