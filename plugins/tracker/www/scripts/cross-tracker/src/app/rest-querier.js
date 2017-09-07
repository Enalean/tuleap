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

import { get, put, recursiveGet } from 'tlp';

const MAXIMUM_NUMBER_OF_ARTIFACTS_DISPLAYED = 30;

export default class RestQuerier {
    constructor(loader_displayer) {
        this.loader_displayer = loader_displayer;
    }

    async getReport(report_id) {
        try {
            this.loader_displayer.show();
            const response = await get('/api/v1/cross_tracker_reports/' + report_id);
            return await response.json();
        } finally {
            this.loader_displayer.hide();
        }
    }

    async getReportContent(report_id) {
        try {
            this.loader_displayer.show();
            const response = await get('/api/v1/cross_tracker_reports/' + report_id + '/content', {
                params: {
                    limit: MAXIMUM_NUMBER_OF_ARTIFACTS_DISPLAYED
                }
            });
            const { artifacts } = await response.json();

            return artifacts;
        } finally {
            this.loader_displayer.hide();
        }
    }

    async updateReport(report_id, trackers_id) {
        try {
            this.loader_displayer.show();
            const response = await put('/api/v1/cross_tracker_reports/' + report_id, {
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ trackers_id })
            });
            return await response.json();
        } finally {
            this.loader_displayer.hide();
        }
    }

    async getSortedProjectsIAmMemberOf() {
        try {
            this.loader_displayer.show();
            const json = await recursiveGet('/api/v1/projects/', {
                params: {
                    limit: 50,
                    query: {'is_member_of': true }
                }
            });

            return json.sort(
                ({ label: label_a }, { label: label_b }) => label_a.localeCompare(label_b)
            );
        } finally {
            this.loader_displayer.hide();
        }
    }

    async getTrackersOfProject(project_id) {
        try {
            this.loader_displayer.show();
            return await recursiveGet('/api/v1/projects/' + project_id + '/trackers', {
                params: {
                    limit: 50
                }
            });
        } finally {
            this.loader_displayer.hide();
        }
    }
}
