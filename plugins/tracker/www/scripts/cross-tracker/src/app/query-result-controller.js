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

import moment from 'moment';
import { render } from 'mustache';
import query_result_rows_template from './query-result-rows.mustache';

export default class QueryResultController {
    constructor(
        widget_content,
        backend_cross_tracker_report,
        user_locale_store,
        rest_querier,
        error_displayer,
        translated_fetch_artifacts_error_message
    ) {
        this.widget_content               = widget_content;
        this.backend_cross_tracker_report = backend_cross_tracker_report;
        this.rest_querier                 = rest_querier;
        this.localized_date_format        = user_locale_store.getDateFormat();
        this.error_displayer              = error_displayer;

        this.translated_fetch_artifacts_error_message = translated_fetch_artifacts_error_message;

        this.table_results     = this.widget_content.querySelector('.dashboard-widget-content-cross-tracker-search-artifacts');
        this.table_empty_state = this.widget_content.querySelector('.dashboard-widget-content-cross-tracker-search-empty');
        this.table_footer      = this.widget_content.querySelector('.dashboard-widget-content-cross-tracker-search-footer');

        this.loadReportContent();
    }

    displayArtifacts(artifacts) {
        this.table_results.insertAdjacentHTML('beforeEnd', render(query_result_rows_template, artifacts));
        window.codendi.Tooltip.load(this.table_results);
    }

    clearResultRows() {
        [...this.table_results.children].forEach((child) => child.remove());
    }

    showEmptyState() {
        this.table_results.classList.add('cross-tracker-hide');
        this.table_empty_state.classList.remove('cross-tracker-hide');
        this.table_footer.classList.add('cross-tracker-hide');
    }

    hideEmptyState() {
        this.table_results.classList.remove('cross-tracker-hide');
        this.table_empty_state.classList.add('cross-tracker-hide');
        this.table_footer.classList.remove('cross-tracker-hide');
    }

    updateArtifacts(artifacts) {
        this.clearResultRows();
        this.displayArtifacts({ artifacts });
        if (artifacts.length > 0) {
            this.hideEmptyState();
        } else {
            this.showEmptyState();
        }
    }

    async loadReportContent() {
        try {
            const artifacts           = await this.rest_querier.getReportContent(this.backend_cross_tracker_report.report_id);
            const formatted_artifacts = this.formatArtifacts(artifacts);
            this.updateArtifacts(formatted_artifacts);
        } catch (error) {
            this.error_displayer.displayError(this.translated_fetch_artifacts_error_message);
            throw error;
        }
    }

    formatArtifacts(artifacts) {
        return artifacts.map((artifact) => {
           artifact.formatted_last_update_date = moment(artifact.last_update_date).format(this.localized_date_format);

           return artifact;
        });
    }
}
