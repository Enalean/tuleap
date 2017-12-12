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

import moment                               from 'moment';
import { render }                           from 'mustache';
import query_result_rows_template           from './query-result-rows.mustache';
import { getReportContent, getQueryResult } from './rest-querier.js';

export default class QueryResultController {
    constructor(
        widget_content,
        backend_cross_tracker_report,
        writing_cross_tracker_report,
        report_saved_state,
        user,
        error_displayer,
        gettext_provider
    ) {
        this.widget_content               = widget_content;
        this.backend_cross_tracker_report = backend_cross_tracker_report;
        this.writing_cross_tracker_report = writing_cross_tracker_report;
        this.report_saved_state           = report_saved_state;
        this.localized_date_format        = user.getUserPreferredDateFormat();
        this.error_displayer              = error_displayer;
        this.gettext_provider             = gettext_provider;

        this.table_element    = this.widget_content.querySelector('.dashboard-widget-content-cross-tracker-search-results');
        this.table_results    = this.widget_content.querySelector('.dashboard-widget-content-cross-tracker-search-artifacts');
        this.load_more_button = this.widget_content.querySelector('.dashboard-widget-content-cross-tracker-search-load-button');

        this.current_offset = 0;
        this.limit          = 30;
    }

    init() {
        this.loadFirstBatchOfArtifacts();
        this.initLoadMoreButton();
    }

    initLoadMoreButton() {
        this.load_more_button.addEventListener('click', () => {
            this.loadABatchOfArtifacts();
        });
    }

    loadFirstBatchOfArtifacts() {
        this.clearResultRows();
        this.showLoadMoreButton();
        this.current_offset = 0;
        return this.loadABatchOfArtifacts();
    }

    displayArtifacts(artifacts) {
        this.table_results.insertAdjacentHTML('beforeEnd', render(query_result_rows_template, artifacts));
        window.codendi.Tooltip.load(this.table_results);
    }

    clearResultRows() {
        [...this.table_results.children].forEach(child => child.remove());
    }

    showLoadingState() {
        this.table_element.classList.add('cross-tracker-loading');
        this.table_element.classList.remove('cross-tracker-empty');
        this.load_more_button.classList.add('cross-tracker-hide');
    }

    hideLoadingState() {
        this.table_element.classList.remove('cross-tracker-loading');
    }

    showEmptyState() {
        this.table_element.classList.add('cross-tracker-empty');
        this.load_more_button.classList.add('cross-tracker-hide');
    }

    hideEmptyState() {
        this.table_element.classList.remove('cross-tracker-empty');
        this.load_more_button.classList.remove('cross-tracker-hide');
    }

    showLoadMoreButton() {
        this.load_more_button.classList.remove('cross-tracker-hide');
    }

    hideLoadMoreButton() {
        this.load_more_button.classList.add('cross-tracker-hide');
    }

    updateArtifacts(artifacts) {
        this.displayArtifacts({ artifacts });
        if (artifacts.length > 0) {
            this.hideEmptyState();
        } else {
            this.showEmptyState();
        }
    }

    getArtifactsFromReportOrUnsavedQuery() {
        if (this.report_saved_state.isReportSaved()) {
            return getReportContent(
                this.backend_cross_tracker_report.report_id,
                this.limit,
                this.current_offset
            );
        }

        return getQueryResult(
            this.backend_cross_tracker_report.report_id,
            this.writing_cross_tracker_report.getTrackerIds(),
            this.writing_cross_tracker_report.expert_query,
            this.limit,
            this.current_offset
        );
    }

    async loadABatchOfArtifacts() {
        try {
            this.showLoadingState();
            const { artifacts, total } = await this.getArtifactsFromReportOrUnsavedQuery();
            const formatted_artifacts  = this.formatArtifacts(artifacts);
            this.updateArtifacts(formatted_artifacts);
            this.current_offset += this.limit;
            if (this.current_offset > total) {
                this.hideLoadMoreButton();
            }
        } catch (error) {
            const error_details = await error.response.json();
            if ('i18n_error_message' in error_details.error) {
                this.error_displayer.displayError(error_details.error.i18n_error_message);
            } else {
                this.error_displayer.displayError(this.gettext_provider.gettext('Error while fetching the query result'));
            }
            throw error;
        } finally {
            this.hideLoadingState();
        }
    }

    formatArtifacts(artifacts) {
        return artifacts.map(artifact => {
            artifact.formatted_last_update_date = moment(artifact.last_update_date).format(this.localized_date_format);

            return artifact;
        });
    }
}
